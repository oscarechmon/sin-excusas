<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Configuración de la tienda online, como pares clave/valor. */
class StoreSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->find($key)?->value ?? $default;
    }

    public static function put(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function deliveryEnabled(): bool
    {
        return filter_var(static::get('delivery_enabled', '1'), FILTER_VALIDATE_BOOL);
    }

    /** Costo extra que el administrador cobra por el delivery. */
    public static function deliveryFee(): float
    {
        return round((float) static::get('delivery_fee', '0'), 2);
    }
}
