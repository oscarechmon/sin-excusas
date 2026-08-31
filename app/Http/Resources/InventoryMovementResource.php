<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_item_id' => $this->inventory_item_id,
            'item' => new InventoryItemResource($this->whenLoaded('item')),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'type_color' => $this->type->color(),
            'quantity' => (float) $this->quantity,
            'stock_after' => (float) $this->stock_after,
            'unit_cost' => $this->unit_cost !== null ? (float) $this->unit_cost : null,
            'source_type' => $this->source_type ? class_basename($this->source_type) : null,
            'source_id' => $this->source_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
