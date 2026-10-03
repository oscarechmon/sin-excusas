<?php

namespace App\Http\Resources;

use App\Services\Erp\LiveCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Con el sistema conectado, precio, sesiones, vigencia, servicios y
        // estado se muestran como están allá ahora (la base no guarda copia).
        app(LiveCatalog::class)->hydrate($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'total_sessions' => $this->total_sessions,
            'validity_days' => $this->validity_days,
            'active' => $this->active,
            'is_published' => (bool) $this->is_published,
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
