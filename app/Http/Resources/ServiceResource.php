<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'category' => new ServiceCategoryResource($this->whenLoaded('category')),
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'description' => $this->description,
            'image_url' => $this->imageUrl(),
            'active' => $this->active,
            'is_published' => (bool) $this->is_published,
            'employees' => $this->whenLoaded('employees'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
