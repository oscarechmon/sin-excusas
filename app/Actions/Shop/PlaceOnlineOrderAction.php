<?php

namespace App\Actions\Shop;

use App\Enums\FulfillmentType;
use App\Enums\OnlineOrderStatus;
use App\Models\ClientUser;
use App\Models\OnlineOrder;
use App\Models\StoreSetting;
use App\Services\Shop\CartLine;
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

        // Los servicios se atienden en el centro: el delivery es solo para productos.
        if ($fulfillment === FulfillmentType::DELIVERY && (! $hasProducts || ! StoreSetting::deliveryEnabled())) {
            throw ValidationException::withMessages([
                'fulfillment' => 'El delivery solo está disponible para pedidos con productos.',
            ]);
        }

        foreach ($lines as $line) {
            if ($line->exceedsAvailable()) {
                throw ValidationException::withMessages([
                    'cart' => "Solo quedan {$line->maxQuantity} unidad(es) de {$line->name()}. Ajusta tu carrito.",
                ]);
            }
        }

        return DB::transaction(function () use ($customer, $lines, $data, $fulfillment) {
            $isDelivery = $fulfillment === FulfillmentType::DELIVERY;
            $subtotal = round($lines->sum(fn (CartLine $line) => $line->subtotal()), 2);
            $deliveryFee = $isDelivery ? StoreSetting::deliveryFee() : 0.0;

            $order = OnlineOrder::create([
                'code' => OnlineOrder::generateCode(),
                'client_user_id' => $customer->id,
                'client_id' => $customer->client_id,
                'status' => OnlineOrderStatus::PENDING_PAYMENT,
                'fulfillment' => $fulfillment,
                'recipient_name' => $data['recipient_name'],
                'phone' => $data['phone'],
                'address' => $isDelivery ? $data['address'] : null,
                'district' => $isDelivery ? $data['district'] : null,
                'reference' => $isDelivery ? ($data['reference'] ?? null) : null,
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
    }
}
