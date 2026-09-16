<?php

namespace App\Actions\Shop;

use App\Enums\InventoryMovementType;
use App\Enums\OnlineOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\InventoryItem;
use App\Models\OnlineOrder;
use App\Models\OnlineOrderItem;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

/** El personal avanza el pedido en su seguimiento (preparación, envío, entrega) o lo anula. */
class ChangeOnlineOrderStatusAction
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function execute(OnlineOrder $order, OnlineOrderStatus $to, ?string $note, int $userId): OnlineOrder
    {
        return DB::transaction(function () use ($order, $to, $note, $userId) {
            $locked = OnlineOrder::query()->lockForUpdate()->findOrFail($order->id);

            if (! in_array($to, $locked->status->nextStatuses($locked->fulfillment), true)) {
                throw InvalidOrderTransitionException::between($locked->status, $to);
            }

            $wasPaid = $locked->status->isPaid();

            $locked->update(['status' => $to]);
            $locked->recordStatus($to, $note, $userId);

            // Anular un pedido cobrado devuelve las unidades al inventario. El
            // reembolso del dinero se hace desde el panel de Izipay.
            if ($to === OnlineOrderStatus::CANCELLED && $wasPaid) {
                $this->restock($locked, $userId);
            }

            return $locked;
        });
    }

    private function restock(OnlineOrder $order, int $userId): void
    {
        $items = $order->items()->where('item_type', OnlineOrderItem::TYPE_PRODUCT)->with('itemable')->get();

        foreach ($items as $item) {
            if ($item->itemable instanceof InventoryItem) {
                $this->inventory->registerMovement(
                    $item->itemable,
                    InventoryMovementType::MANUAL_IN,
                    (float) $item->quantity,
                    $order,
                    $userId,
                    "Devolución por anulación del pedido {$order->code}",
                );
            }
        }
    }
}
