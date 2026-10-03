<?php

namespace App\Services\Erp;

use App\Enums\OnlineOrderStatus;
use App\Exceptions\ErpException;
use App\Models\OnlineOrder;
use App\Models\OnlineOrderItem;
use App\Models\OnlineOrderStatusHistory;
use Illuminate\Support\Facades\Log;

/**
 * Lleva al sistema los pedidos de la tienda online, que allá se gestionan:
 * cada pedido pagado es una venta (canal web), que es la que descuenta el
 * stock, y su seguimiento (preparación, envío, entrega) lo mueve el personal
 * allá. El código del pedido lo identifica, así que reintentar nunca duplica.
 */
class OnlineOrderRegistrar
{
    public function __construct(
        private readonly ErpClient $erp,
        private readonly LiveCatalog $live,
        private readonly OrderStatusApplier $statuses,
    ) {}

    /** Pedido pagado: el sistema registra su venta, que descuenta el stock. */
    public function register(OnlineOrder $order): void
    {
        $this->erp->pushOrder($this->snapshot($order));

        $this->live->forget();
        $order->forceFill(['erp_sale_status' => OnlineOrder::ERP_REGISTERED])->save();
    }

    /**
     * Pedido aún sin cobrar (recién hecho o con el pago rechazado): se manda
     * para que el sistema lo vea, sin bloquear al cliente si no contesta.
     */
    public function push(OnlineOrder $order): void
    {
        if (! $this->erp->enabled()) {
            return;
        }

        try {
            $this->erp->pushOrder($this->snapshot($order));
        } catch (ErpException $e) {
            Log::warning('No se pudo mandar el pedido al sistema.', ['pedido' => $order->code, 'error' => $e->getMessage()]);
        }
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
            OnlineOrder::ERP_REGISTERED => $this->cancelInErp($order, $reason),
            OnlineOrder::ERP_PENDING => $order->forceFill(['erp_sale_status' => null])->save(),
            default => $order->recordStatus(
                OnlineOrderStatus::CANCELLED,
                'El pedido es anterior a la conexión con el sistema: devuelve sus unidades con un ajuste de stock allá.',
                $userId,
                internal: true,
            ),
        };
    }

    private function cancelInErp(OnlineOrder $order, ?string $reason): void
    {
        $this->erp->cancelSale($order->code, $reason);
        $this->live->forget();
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

    /**
     * Trae del sistema el seguimiento de los pedidos en curso, por si algún
     * aviso no llegó.
     *
     * @return int Pedidos que cambiaron.
     */
    public function pullStatuses(): int
    {
        $open = [OnlineOrderStatus::PAID, OnlineOrderStatus::PREPARING, OnlineOrderStatus::SHIPPED, OnlineOrderStatus::READY_FOR_PICKUP];
        $codes = OnlineOrder::whereIn('status', $open)->pluck('code')->all();
        $changed = 0;

        foreach (array_chunk($codes, 100) as $chunk) {
            foreach ($this->erp->orderStatuses($chunk) as $remote) {
                $order = OnlineOrder::where('code', $remote['code'])->first();
                if (! $order) {
                    continue;
                }

                $before = $order->status;
                foreach ($remote['history'] ?? [] as $step) {
                    $this->statuses->apply($order, $step['status'], $step['note'] ?? null, $step['user_name'] ?? null, $step['happened_at'] ?? null);
                }
                $this->statuses->apply($order->fresh(), $remote['status'], null, null, null, recordHistory: false);

                $changed += $order->fresh()->status !== $before ? 1 : 0;
            }
        }

        return $changed;
    }

    /**
     * El pedido con la forma que espera el sistema.
     *
     * @return array<string, mixed>
     */
    public function snapshot(OnlineOrder $order, bool $historical = false): array
    {
        $order->loadMissing('items.itemable', 'clientUser', 'client', 'histories.user');
        $client = $order->client;

        return [
            'historical' => $historical,
            'web_id' => $order->id,
            'code' => $order->code,
            'status' => $order->status->value,
            'fulfillment' => $order->fulfillment->value,
            'recipient_name' => $order->recipient_name,
            'phone' => $order->phone,
            'address' => $order->address,
            'district' => $order->district,
            'reference' => $order->reference,
            'notes' => $order->notes,
            'subtotal' => (float) $order->subtotal,
            'delivery_fee' => (float) $order->delivery_fee,
            'total' => (float) $order->total,
            'gateway' => $order->payments()->where('status', 'paid')->value('gateway') ?? 'izipay',
            'payment_reference' => $order->payment_reference,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'ordered_at' => $order->created_at?->toIso8601String(),
            'customer' => [
                'web_id' => $client?->id,
                'code' => $client?->code,
                'name' => $order->clientUser?->name ?? $client?->full_name ?? $order->recipient_name,
                'document_number' => $order->clientUser?->document_number ?? $client?->document_number,
                'email' => $order->clientUser?->email ?? $client?->email,
                'phone' => $order->phone ?? $order->clientUser?->phone,
            ],
            'items' => $order->items->map(fn (OnlineOrderItem $item) => [
                'product_id' => $item->itemable?->erp_id,
                'item_type' => $item->item_type,
                'name' => $item->name,
                'unit_price' => (float) $item->unit_price,
                'quantity' => (float) $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])->values()->all(),
            // Lo que llegó del sistema no se le devuelve.
            'history' => $order->histories->reject(fn (OnlineOrderStatusHistory $h) => $h->from_erp)->map(fn (OnlineOrderStatusHistory $h) => [
                'status' => $h->status->value,
                'note' => $h->note,
                'internal' => $h->internal,
                'user_name' => $h->user?->name,
                'happened_at' => $h->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
