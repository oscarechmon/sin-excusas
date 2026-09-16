<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->imageUrl(),
            'category_id' => $this->category_id,
            'category' => new InventoryCategoryResource($this->whenLoaded('category')),
            'unit' => $this->unit,
            'stock' => (float) $this->stock,
            'min_stock' => (float) $this->min_stock,
            // Se calcula en el backend para que la regla viva en un solo sitio.
            'is_low_stock' => $this->isLowStock(),
            'cost' => (float) $this->cost,
            'sale_price' => $this->sale_price !== null ? (float) $this->sale_price : null,
            'supplier' => $this->supplier,
            'is_sellable' => $this->is_sellable,
            'active' => $this->active,
            'is_published' => (bool) $this->is_published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
