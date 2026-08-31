<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'phone' => $this->phone,
            'document_number' => $this->document_number,
            'user_id' => $this->user_id,
            'has_system_access' => $this->user_id !== null,
            'active' => $this->active,
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
