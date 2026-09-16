<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Configuración de la tienda online: delivery y su costo extra. */
class StoreSettingController extends Controller
{
    use ApiResponses;

    public function show(): JsonResponse
    {
        return $this->ok($this->settings());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'delivery_enabled' => ['required', 'boolean'],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'max:9999'],
        ]);

        StoreSetting::put('delivery_enabled', $data['delivery_enabled'] ? '1' : '0');
        StoreSetting::put('delivery_fee', number_format((float) $data['delivery_fee'], 2, '.', ''));

        return $this->ok($this->settings(), 'Configuración de delivery guardada.');
    }

    private function settings(): array
    {
        return [
            'delivery_enabled' => StoreSetting::deliveryEnabled(),
            'delivery_fee' => StoreSetting::deliveryFee(),
        ];
    }
}
