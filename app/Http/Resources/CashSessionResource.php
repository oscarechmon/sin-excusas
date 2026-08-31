<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'opening_amount' => (float) $this->opening_amount,
            'expected_amount' => $this->expected_amount !== null ? (float) $this->expected_amount : null,
            'counted_amount' => $this->counted_amount !== null ? (float) $this->counted_amount : null,
            'difference' => $this->difference !== null ? (float) $this->difference : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'opened_by' => $this->whenLoaded('openedBy', fn () => $this->openedBy?->name),
            'closed_by' => $this->whenLoaded('closedBy', fn () => $this->closedBy?->name),
            'notes' => $this->notes,
            'movements' => CashMovementResource::collection($this->whenLoaded('movements')),
            // Lo inyecta el controlador: depende de CashService, no del modelo.
            'current_expected' => $this->additional['current_expected'] ?? null,
        ];
    }
}
