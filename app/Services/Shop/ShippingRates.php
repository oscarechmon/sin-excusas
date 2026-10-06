<?php

namespace App\Services\Shop;

use App\Enums\FulfillmentType;
use App\Exceptions\ErpException;
use App\Models\StoreSetting;
use App\Services\Erp\ErpClient;
use Illuminate\Support\Facades\Cache;

/**
 * Cuánto cuesta cada forma de recibir el pedido: delivery en Lima, envío a
 * provincia por agencia Shalom o recojo en el centro (gratis).
 *
 * Con el sistema conectado los costos se cambian allá (Pedidos online →
 * Costos de envío) y aquí se leen: para mostrarlos se reutilizan un minuto;
 * al armar el pedido se preguntan en el momento. Si el sistema no contesta
 * se usa lo último que respondió. Sin el sistema, salen de StoreSetting.
 */
class ShippingRates
{
    private const CACHE_KEY = 'erp.shipping';

    /** Lo último que respondió el sistema, para cuando no conteste. */
    private const LAST_KEY = 'erp.shipping.last';

    public function __construct(private readonly ErpClient $erp) {}

    /**
     * @param  bool  $fresh  Preguntarle al sistema ahora (al cobrar), sin reutilizar.
     * @return array<string, array{enabled: bool, fee: float}>
     */
    public function all(bool $fresh = false): array
    {
        if (! $this->erp->enabled()) {
            return StoreSetting::shippingRates();
        }

        if (! $fresh && ($cached = Cache::get(self::CACHE_KEY)) !== null) {
            return $cached;
        }

        try {
            $rates = $this->normalize($this->erp->shippingRates());
        } catch (ErpException) {
            return Cache::get(self::CACHE_KEY) ?? Cache::get(self::LAST_KEY) ?? StoreSetting::shippingRates();
        }

        Cache::put(self::CACHE_KEY, $rates, (int) config('erp.catalog_ttl', 60));
        Cache::forever(self::LAST_KEY, $rates);

        return $rates;
    }

    public function enabled(FulfillmentType $fulfillment, bool $fresh = false): bool
    {
        return $fulfillment === FulfillmentType::PICKUP || ($this->all($fresh)[$fulfillment->value]['enabled'] ?? false);
    }

    public function fee(FulfillmentType $fulfillment, bool $fresh = false): float
    {
        return $fulfillment === FulfillmentType::PICKUP ? 0.0 : (float) ($this->all($fresh)[$fulfillment->value]['fee'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $rates
     * @return array<string, array{enabled: bool, fee: float}>
     */
    private function normalize(array $rates): array
    {
        $local = StoreSetting::shippingRates();

        foreach ($local as $zone => $default) {
            $local[$zone] = [
                'enabled' => (bool) ($rates[$zone]['enabled'] ?? $default['enabled']),
                'fee' => round((float) ($rates[$zone]['fee'] ?? $default['fee']), 2),
            ];
        }

        return $local;
    }
}
