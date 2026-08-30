<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'full_name' => $this->full_name,
            'document_number' => $this->document_number,
            'birth_date' => $this->birth_date,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'district' => $this->district,
            'address' => $this->address,
            'how_knew' => $this->how_knew,
            'observations' => $this->observations,
            'allergies' => $this->allergies,
            'restrictions' => $this->restrictions,
            'contraindications' => $this->contraindications,
            'medications' => $this->medications,
            'relevant_info' => $this->relevant_info,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
