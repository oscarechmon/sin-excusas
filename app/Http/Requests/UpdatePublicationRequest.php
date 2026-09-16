<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Publicar o retirar un elemento del catálogo de la web pública. */
class UpdatePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_published' => ['required', 'boolean'],
        ];
    }
}
