<?php

namespace App\Http\Resources;

use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->resolveType(),
            'itemable_id' => $this->itemable_id,
            'description' => $this->description,
            'unit_price' => (float) $this->unit_price,
            'quantity' => (float) $this->quantity,
            'discount' => (float) $this->discount,
            'subtotal' => (float) $this->subtotal,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
        ];
    }

    /** Traduce la clase del morph al vocabulario que usa el frontend. */
    private function resolveType(): string
    {
        return match ($this->itemable_type) {
            Service::class => 'service',
            InventoryItem::class => 'product',
            Package::class => 'package',
            default => 'unknown',
        };
    }
}
