<?php

namespace App\Actions\Shop;

use App\Enums\FulfillmentType;
use App\Enums\OnlineOrderStatus;
use App\Models\ClientUser;
use App\Models\OnlineOrder;
use App\Services\Erp\OnlineOrderRegistrar;
use App\Services\Shop\CartLine;
use App\Services\Shop\ShippingRates;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Convierte el carrito en un pedido pendiente de pago.
 *
 * El stock no se descuenta aquí sino al confirmarse el cobro: un pedido que
 * nunca se paga no debe bloquear unidades.
 */
class PlaceOnlineOrderAction
{
    public function __construct(
        private readonly OnlineOrderRegistrar $registrar,
        private readonly ShippingRates $shipping,
    ) {}

    /**
     * @param  Collection<int,CartLine>  $lines
     * @param  array<string,mixed>  $data
     */
    public function execute(ClientUser $customer, Collection $lines, array $data): OnlineOrder
    {
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Tu carrito está vacío.']);
        }

        $fulfillment = FulfillmentType::from($data['fulfillment']);
        $hasProducts = $lines->contains(fn (CartLine $line) => $line->isProduct());

        // Los servicios se atienden en el centro: los envíos son solo para productos.
        if ($fulfillment->isShipped() && ! $hasProducts) {
            throw ValidationException::withMessages([
                'fulfillment' => 'Los envíos solo están disponibles para pedidos con productos.',
            ]);
        }

        // El costo se pregunta ahora: es lo que se cobra, no lo que se mostró.
        if (! $this->shipping->enabled($fulfillment, fresh: true)) {
            throw ValidationException::withMessages([
                'fulfillment' => "{$fulfillment->label()} no está disponible por el momento. Elige otra forma de recibir tu pedido.",
            ]);
        }
        $deliveryFee = $this->shipping->fee($fulfillment);

        foreach ($lines as $line) {
            if ($line->exceedsAvailable()) {
                throw ValidationException::withMessages([
                    'cart' => "Solo quedan {$line->maxQuantity} unidad(es) de {$line->name()}. Ajusta tu carrito.",
                ]);
            }
        }

        $order = DB::transaction(function () use ($customer, $lines, $data, $fulfillment, $deliveryFee) {
            $isDelivery = $fulfillment === FulfillmentType::DELIVERY;
            $isProvince = $fulfillment === FulfillmentType::PROVINCE;
            $subtotal = round($lines->sum(fn (CartLine $line) => $line->subtotal()), 2);

            $order = OnlineOrder::create([
                'code' => OnlineOrder::generateCode(),
                'client_user_id' => $customer->id,
                'client_id' => $customer->client_id,
                'status' => OnlineOrderStatus::PENDING_PAYMENT,
                'fulfillment' => $fulfillment,
                'recipient_name' => $data['recipient_name'],
                'document_type' => $isProvince ? $data['document_type'] : null,
                'document_number' => $isProvince ? $data['document_number'] : null,
                'phone' => $data['phone'],
                'address' => $isDelivery ? $data['address'] : null,
                'district' => $isDelivery ? $data['district'] : null,
                'reference' => $isDelivery ? ($data['reference'] ?? null) : null,
                'department' => $isProvince ? $data['department'] : null,
                'province' => $isProvince ? $data['province'] : null,
                'agency' => $isProvince ? $data['agency'] : null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => round($subtotal + $deliveryFee, 2),
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'itemable_type' => $line->model->getMorphClass(),
                    'itemable_id' => $line->model->getKey(),
                    'item_type' => $line->type,
                    'name' => $line->name(),
                    'unit_price' => $line->unitPrice(),
                    'quantity' => $line->quantity,
                    'subtotal' => $line->subtotal(),
                ]);
            }

            $order->recordStatus(OnlineOrderStatus::PENDING_PAYMENT, 'Pedido creado desde la web.');

            return $order;
        });

        // Con el sistema conectado, el pedido se gestiona allá: se le manda ya
        // respondida la página, para no hacer esperar al cliente.
        defer(fn () => $this->registrar->push($order));

        return $order;
    }
}
