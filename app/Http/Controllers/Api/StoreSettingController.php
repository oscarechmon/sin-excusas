<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Services\Erp\ErpClient;
use App\Services\Shop\ShippingRates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Costos de envío de la tienda online: delivery en Lima y envío a provincia.
 * Con el sistema conectado se cambian allá (Pedidos online → Costos de
 * envío) y aquí solo se consultan.
 */
class StoreSettingController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ShippingRates $rates,
        private readonly ErpClient $erp,
    ) {}

    public function show(): JsonResponse
    {
        return $this->ok($this->settings());
    }

    public function update(Request $request): JsonResponse
    {
        abort_if($this->erp->enabled(), 409, 'Los costos de envío se cambian en el sistema: Pedidos online → Costos de envío.');

        $data = $request->validate([
            'delivery.enabled' => ['required', 'boolean'],
            'delivery.fee' => ['required', 'numeric', 'min:0', 'max:9999'],
            'province.enabled' => ['required', 'boolean'],
            'province.fee' => ['required', 'numeric', 'min:0', 'max:9999'],
        ]);

        foreach (['delivery', 'province'] as $zone) {
            StoreSetting::put("{$zone}_enabled", $data[$zone]['enabled'] ? '1' : '0');
            StoreSetting::put("{$zone}_fee", number_format((float) $data[$zone]['fee'], 2, '.', ''));
        }

        return $this->ok($this->settings(), 'Costos de envío guardados.');
    }

    private function settings(): array
    {
        return $this->rates->all() + ['managed_by_erp' => $this->erp->enabled()];
    }
}
