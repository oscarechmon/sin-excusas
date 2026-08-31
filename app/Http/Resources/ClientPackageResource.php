<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'package_id' => $this->package_id,
            'package_name' => $this->package_name,
            'price' => (float) $this->price,
            'total_sessions' => $this->total_sessions,
            'used_sessions' => $this->used_sessions,
            'remaining_sessions' => $this->remainingSessions(),
            'purchased_at' => $this->purchased_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'is_expired' => $this->isExpired(),
            'can_consume' => $this->canConsumeSession(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'sessions' => $this->whenLoaded('sessions'),
            'created_at' => $this->created_at,
        ];
    }
}
