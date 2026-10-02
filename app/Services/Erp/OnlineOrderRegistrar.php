<?php

namespace App\Services\Erp;

use App\Enums\OnlineOrderStatus;
use App\Exceptions\ErpException;
use App\Models\OnlineOrder;
use App\Models\OnlineOrderItem;

/**
 * Lleva al sistema los pedidos de la tienda online: cada pedido pagado es una
 * venta allá (canal web), que es la que descuenta el stock. El código del
 * pedido identifica la venta, así que reintentar nunca la duplica.
 */
class OnlineOrderRegistrar
{
    public function __construct(
        private readonly ErpClient $erp,
        private readonly CatalogSync $catalog,
    ) {}

    public function register(OnlineOrder $order): void
    {
        $order->loadMissing('items.itemable', 'clientUser');

        $result = $this->erp->registerSale([
            'reference' => $order->code,
            'customer' => [
                'name' => $order->clientUser?->name ?? $order->recipient_name,
                'document_number' => $order->clientUser?->document_number,
                'email' => $order->clientUser?->email,
                'phone' => $order->phone ?? $order->clientUser?->phone,
            ],
            'items' => $order->items->map(fn (OnlineOrderItem $item) => [
                'product_id' => $item->itemable?->erp_id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'price' => (float) $item->unit_price,
            ])->all(),
            'delivery_fee' => (float) $order->delivery_fee,
            'payments' => [[
                'method' => 'izipay',
                'amount' => (float) $order->total,
                'reference' => $order->payment_reference,
            ]],
        ]);

        $this->catalog->applyStock($result['stock'] ?? []);
        $order->forceFill(['erp_sale_status' => OnlineOrder::ERP_REGISTERED])->save();
    }

    /**
     * Anula la venta en el sistema, que devuelve el stock.
     *
     * Un pedido que nunca llegó a registrarse no tiene nada que devolver. Uno
     * anterior a la integración descontó su stock aquí, antes de que el sistema
     * existiera: eso se corrige a mano allá, y queda anotado.
     */
    public function cancel(OnlineOrder $order, ?string $reason, int $userId): void
    {
        match ($order->erp_sale_status) {
            OnlineOrder::ERP_REGISTERED => $this->catalog->applyStock(
                $this->erp->cancelSale($order->code, $reason)['stock'] ?? []
            ),
            OnlineOrder::ERP_PENDING => $order->forceFill(['erp_sale_status' => null])->save(),
            default => $order->recordStatus(
                OnlineOrderStatus::CANCELLED,
                'El pedido es anterior a la conexión con el sistema: devuelve sus unidades con un ajuste de stock allá.',
                $userId,
                internal: true,
            ),
        };
    }

    /**
     * Pedidos pagados que quedaron sin registrar porque el sistema no contestó.
     *
     * @return array{registrados:int, pendientes:int}
     */
    public function retryPending(): array
    {
        $registered = 0;
        $pending = 0;

        OnlineOrder::where('erp_sale_status', OnlineOrder::ERP_PENDING)->each(function (OnlineOrder $order) use (&$registered, &$pending) {
            try {
                $this->register($order);
                $registered++;
            } catch (ErpException) {
                $pending++;
            }
        });

        return ['registrados' => $registered, 'pendientes' => $pending];
    }
}
