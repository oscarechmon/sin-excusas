<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cash_session_id' => $this->cash_session_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'type_color' => $this->type->color(),
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => $this->paymentMethod?->name),
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
            'created_at' => $this->created_at,
        ];
    }
}
