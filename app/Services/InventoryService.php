<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Único punto donde cambia el stock (§24).
 *
 * Ningún otro código debe escribir `inventory_items.stock` directamente: cada
 * cambio pasa por aquí y deja su movimiento, de modo que el saldo siempre es
 * reconstruible y auditable.
 */
class InventoryService
{
    /**
     * Registra un movimiento y actualiza el saldo.
     *
     * @param  float  $quantity  Siempre positiva; el signo lo decide el tipo.
     *                           En ADJUSTMENT representa el saldo objetivo.
     */
    public function registerMovement(
        InventoryItem $item,
        InventoryMovementType $type,
        float $quantity,
        ?Model $source = null,
        ?int $userId = null,
        ?string $notes = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($item, $type, $quantity, $source, $userId, $notes) {
            // Bloqueo pesimista: dos atenciones simultáneas sobre el mismo
            // insumo no deben leer el mismo stock y descontar dos veces.
            $locked = InventoryItem::whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            $delta = $this->resolveDelta($locked, $type, $quantity);
            $stockAfter = round((float) $locked->stock + $delta, 2);

            if ($stockAfter < 0) {
                throw InsufficientStockException::for($locked, abs($delta));
            }

            $locked->forceFill(['stock' => $stockAfter])->save();

            $movement = new InventoryMovement([
                'inventory_item_id' => $locked->id,
                'type' => $type,
                'quantity' => $delta,
                'stock_after' => $stockAfter,
                'unit_cost' => $locked->cost,
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            if ($source !== null) {
                $movement->source()->associate($source);
            }

            $movement->save();

            $item->setAttribute('stock', $stockAfter);

            return $movement;
        });
    }

    /**
     * Descuenta varios insumos de una sola vez.
     *
     * @param  array<int,array{item:InventoryItem,quantity:float}>  $lines
     * @return array<int,InventoryMovement>
     */
    public function consumeMany(array $lines, Model $source, ?int $userId = null): array
    {
        $movements = [];

        foreach ($lines as $line) {
            if ((float) $line['quantity'] <= 0) {
                continue;
            }

            $movements[] = $this->registerMovement(
                $line['item'],
                InventoryMovementType::SERVICE_USAGE,
                (float) $line['quantity'],
                $source,
                $userId,
            );
        }

        return $movements;
    }

    /** Traduce tipo + cantidad al delta con signo que se aplica al saldo. */
    private function resolveDelta(InventoryItem $item, InventoryMovementType $type, float $quantity): float
    {
        if ($type === InventoryMovementType::ADJUSTMENT) {
            // En un ajuste la cantidad es el saldo real contado, no un delta.
            return round($quantity - (float) $item->stock, 2);
        }

        $magnitude = abs($quantity);

        return $type->isIncoming() ? $magnitude : -$magnitude;
    }
}
