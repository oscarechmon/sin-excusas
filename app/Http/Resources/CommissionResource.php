<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'attendance_id' => $this->attendance_id,
            'sale_id' => $this->sale_id,
            'service' => new ServiceResource($this->whenLoaded('service')),
            'base_amount' => (float) $this->base_amount,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'value' => (float) $this->value,
            'amount' => (float) $this->amount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'generated_at' => $this->generated_at?->toDateString(),
            'paid_at' => $this->paid_at,
        ];
    }
}
