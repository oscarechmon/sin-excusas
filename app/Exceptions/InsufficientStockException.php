<?php

namespace App\Exceptions;

use App\Models\InventoryItem;

class InsufficientStockException extends BusinessException
{
    public static function for(InventoryItem $item, float $requested): self
    {
        $exception = new self(sprintf(
            'Stock insuficiente de "%s": se solicitaron %s %s y solo hay %s.',
            $item->name,
            rtrim(rtrim(number_format($requested, 2, '.', ''), '0'), '.'),
            $item->unit,
            rtrim(rtrim(number_format((float) $item->stock, 2, '.', ''), '0'), '.')
        ));

        $exception->context = [
            'inventory_item_id' => $item->id,
            'requested' => $requested,
            'available' => (float) $item->stock,
        ];

        return $exception;
    }
}
