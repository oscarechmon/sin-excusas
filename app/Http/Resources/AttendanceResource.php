<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'service_id' => $this->service_id,
            'service' => new ServiceResource($this->whenLoaded('service')),
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'appointment_id' => $this->appointment_id,
            'client_package_id' => $this->client_package_id,
            'client_package' => new ClientPackageResource($this->whenLoaded('clientPackage')),
            'session_number' => $this->session_number,
            'attended_at' => $this->attended_at?->toDateString(),
            'observations' => $this->observations,
            'measurements' => $this->measurements,
            'supplies' => $this->whenLoaded('supplies', fn () => $this->supplies->map(fn ($supply) => [
                'inventory_item_id' => $supply->inventory_item_id,
                'name' => $supply->item?->name,
                'unit' => $supply->item?->unit,
                'quantity' => (float) $supply->quantity,
            ])),
            'commission' => $this->whenLoaded('commission', fn () => $this->commission ? [
                'id' => $this->commission->id,
                'amount' => (float) $this->commission->amount,
                'status' => $this->commission->status->value,
            ] : null),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
