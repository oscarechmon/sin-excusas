<?php

namespace App\Actions\Shop;

use App\Enums\InventoryMovementType;
use App\Enums\OnlineOrderStatus;
use App\Exceptions\ErpException;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\OnlineOrder;
use App\Models\OnlineOrderItem;
use App\Services\Erp\ErpClient;
use App\Services\Erp\OnlineOrderRegistrar;
use App\Services\InventoryService;
use App\Services\Payments\PaymentResult;
use Illuminate\Support\Facades\DB;

/**
 * Aplica el resultado de la pasarela a un pedido.
 *
 * Idempotente: Izipay avisa dos veces (retorno del navegador e IPN), y el
 * cliente puede recargar la página. Solo la primera confirmación marca el
 * pedido como pagado y descuenta stock.
 *
 * Con el sistema (ERP) conectado, el stock es suyo: el pedido pagado se
 * registra allá como venta y es esa venta la que lo descuenta.
 */
class ConfirmOnlinePaymentAction
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ErpClient $erp,
        private readonly OnlineOrderRegistrar $registrar,
    ) {}

    public function execute(PaymentResult $result, string $gateway): ?OnlineOrder
    {
        return DB::transaction(function () use ($result, $gateway) {
            $order = OnlineOrder::query()->where('code', $result->orderCode)->lockForUpdate()->first();

            if (! $order) {
                return null;
            }

            // No se confía en el estado sin verificar que se cobró el total exacto.
            $amountMatches = $result->amountInCents === $order->totalInCents();
            $approved = $result->paid && $amountMatches;

            $this->recordPayment($order, $result, $gateway, $approved);

            if ($order->status->isPaid() || $order->status === OnlineOrderStatus::CANCELLED) {
                return $order;
            }

            if (! $approved) {
                if ($order->status !== OnlineOrderStatus::PAYMENT_FAILED) {
                    $order->update(['status' => OnlineOrderStatus::PAYMENT_FAILED]);
                    $order->recordStatus(
                        OnlineOrderStatus::PAYMENT_FAILED,
                        $result->paid ? 'El monto cobrado no coincide con el total del pedido.' : 'La pasarela no aprobó el pago.'
                    );
                }

                return $order;
            }

            $order->update([
                'status' => OnlineOrderStatus::PAID,
                'paid_at' => now(),
                'payment_reference' => $result->transactionId,
            ]);
            $order->recordStatus(OnlineOrderStatus::PAID, 'Pago confirmado.');

            $this->erp->enabled() ? $this->registerInErp($order) : $this->discountStock($order);

            return $order;
        });
    }

    private function recordPayment(OnlineOrder $order, PaymentResult $result, string $gateway, bool $approved): void
    {
        if ($result->transactionId && $order->payments()->where('transaction_id', $result->transactionId)->exists()) {
            return;
        }

        $order->payments()->create([
            'gateway' => $gateway,
            'status' => $approved ? 'paid' : 'refused',
            'amount' => $result->amountInCents / 100,
            'transaction_id' => $result->transactionId,
            'payload' => $result->payload,
        ]);
    }

    /**
     * El cobro ya ocurrió: si el sistema no contesta, el pedido sigue pagado,
     * queda como pendiente y la sincronización lo vuelve a intentar.
     */
    private function registerInErp(OnlineOrder $order): void
    {
        $order->forceFill(['erp_sale_status' => OnlineOrder::ERP_PENDING])->save();

        try {
            $this->registrar->register($order);
        } catch (ErpException $e) {
            $order->recordStatus(
                OnlineOrderStatus::PAID,
                'No se pudo registrar la venta en el sistema ('.$e->getMessage().'). Se reintentará al sincronizar.',
                internal: true,
            );
        }
    }

    /**
     * El cobro ya ocurrió: si justo se agotó un producto, el pedido sigue
     * pagado y queda una nota interna para que el personal lo resuelva.
     */
    private function discountStock(OnlineOrder $order): void
    {
        $items = $order->items()->where('item_type', OnlineOrderItem::TYPE_PRODUCT)->with('itemable')->get();

        foreach ($items as $item) {
            if (! $item->itemable instanceof InventoryItem) {
                continue;
            }

            try {
                $this->inventory->registerMovement(
                    $item->itemable,
                    InventoryMovementType::SALE,
                    (float) $item->quantity,
                    $order,
                    null,
                    "Venta online {$order->code}",
                );
            } catch (InsufficientStockException $e) {
                $order->recordStatus(OnlineOrderStatus::PAID, 'Revisar stock: '.$e->getMessage(), internal: true);
            }
        }
    }
}
