<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            // Ficha de personal vinculada, si este usuario también atiende.
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'position' => $this->employee->position,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
