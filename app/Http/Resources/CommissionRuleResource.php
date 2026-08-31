<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'service_id' => $this->service_id,
            'service' => new ServiceResource($this->whenLoaded('service')),
            // Ayuda a explicar en la UI por qué una regla gana sobre otra.
            'scope' => match (true) {
                $this->employee_id && $this->service_id => 'Especialista y servicio',
                (bool) $this->employee_id => 'Especialista',
                (bool) $this->service_id => 'Servicio',
                default => 'General',
            },
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'value' => (float) $this->value,
            'active' => $this->active,
        ];
    }
}
