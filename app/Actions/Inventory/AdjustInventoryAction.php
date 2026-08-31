<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Services\InventoryService;

/**
 * Entrada, salida o ajuste manual de stock (§24).
 *
 * En un ajuste la cantidad es el saldo real contado; el servicio calcula el
 * delta. En entradas y salidas es la cantidad movida.
 */
class AdjustInventoryAction
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function execute(
        InventoryItem $item,
        InventoryMovementType $type,
        float $quantity,
        int $userId,
        ?string $notes = null,
    ): InventoryMovement {
        return $this->inventory->registerMovement(
            item: $item,
            type: $type,
            quantity: $quantity,
            userId: $userId,
            notes: $notes,
        );
    }
}
