<?php

namespace App\Services\Erp;

use App\Enums\OnlineOrderStatus;
use App\Models\OnlineOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aplica aquí un paso del seguimiento que el personal hizo en el sistema, para
 * que el cliente lo vea en "Mis pedidos". No toca stock ni ventas: de eso ya
 * se encargó el sistema. El mismo paso dos veces no se duplica.
 */
class OrderStatusApplier
{
    public function apply(
        OnlineOrder $order,
        string $status,
        ?string $note,
        ?string $actor,
        ?string $happenedAt,
        bool $recordHistory = true,
    ): void {
        $to = OnlineOrderStatus::tryFrom($status);
        if (! $to) {
            return;
        }

        DB::transaction(function () use ($order, $to, $note, $actor, $happenedAt, $recordHistory) {
            /** @var OnlineOrder $locked */
            $locked = OnlineOrder::query()->lockForUpdate()->findOrFail($order->id);
            $at = $happenedAt ? Carbon::parse($happenedAt) : now();

            if ($recordHistory) {
                $duplicate = $locked->histories()
                    ->where('from_erp', true)
                    ->where('status', $to->value)
                    ->where('created_at', $at->copy()->setTimezone(config('app.timezone'))->toDateTimeString())
                    ->exists();

                if (! $duplicate) {
                    $locked->histories()->create([
                        'status' => $to,
                        'note' => $note,
                        'internal' => false,
                        'from_erp' => true,
                        'actor_name' => $actor,
                        'created_at' => $at,
                    ]);
                }
            }

            if ($locked->status !== $to) {
                $locked->update(['status' => $to]);
            }
        });
    }
}
