<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Foto de un servicio o producto para la web pública. */
class UploadCatalogImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Selecciona una imagen.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'La imagen debe ser JPG, PNG o WebP.',
            'image.max' => 'La imagen no puede pesar más de 4 MB.',
        ];
    }
}
