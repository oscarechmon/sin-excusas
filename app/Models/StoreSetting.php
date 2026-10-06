<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuración de la tienda online, como pares clave/valor. Con el sistema
 * conectado, los costos de envío se cambian allá (ShippingRates); estos
 * quedan para cuando la web funciona sola.
 */
class StoreSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Lo que se cobra si nadie lo cambió todavía. */
    public const DEFAULT_FEES = ['delivery' => '10.00', 'province' => '12.00'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->find($key)?->value ?? $default;
    }

    public static function put(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Costos de envío de aquí: delivery en Lima y envío a provincia.
     *
     * @return array<string, array{enabled: bool, fee: float}>
     */
    public static function shippingRates(): array
    {
        $rates = [];

        foreach (self::DEFAULT_FEES as $zone => $default) {
            $rates[$zone] = [
                'enabled' => filter_var(static::get("{$zone}_enabled", '1'), FILTER_VALIDATE_BOOL),
                'fee' => round((float) static::get("{$zone}_fee", $default), 2),
            ];
        }

        return $rates;
    }
}
