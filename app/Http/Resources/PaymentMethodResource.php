<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'requires_reference' => $this->requires_reference,
            'is_cash' => $this->is_cash,
            'active' => $this->active,
            'sort_order' => $this->sort_order,
        ];
    }
}
