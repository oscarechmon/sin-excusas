<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:service_categories,id',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:15',
            'description' => 'nullable|string',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer|exists:employees,id',
            'active' => 'boolean',
        ];
    }
}
