<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:inventory_categories,id'],
            'unit' => ['required', 'string', 'max:20'],
            // El stock inicial solo se acepta al crear; después solo cambia
            // mediante movimientos, para no romper la trazabilidad (§24).
            'stock' => ['prohibited_unless:_method,POST', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'is_sellable' => ['boolean'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'unit.required' => 'La unidad de medida es requerida.',
            'stock.prohibited_unless' => 'El stock no se edita directamente: registre un movimiento de inventario.',
        ];
    }
}
