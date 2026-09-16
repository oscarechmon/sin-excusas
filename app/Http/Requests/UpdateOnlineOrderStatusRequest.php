<?php

namespace App\Http\Requests;

use App\Enums\OnlineOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOnlineOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(OnlineOrderStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
