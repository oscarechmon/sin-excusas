<?php

namespace App\Support;

use App\Models\StoreSetting;
use Illuminate\Support\HtmlString;

/**
 * Ajustes del sitio (contacto, redes y scripts de terceros).
 *
 * Combina la definición de config/site_settings.php con los valores guardados
 * en `store_settings`. Se consulta una vez por petición porque la usan la
 * cabecera, el pie y el layout de la misma página.
 */
final class SiteSettings
{
    /** @return array<string,string> */
    public static function all(): array
    {
        $attributes = request()->attributes;

        if (! $attributes->has('site.settings')) {
            $saved = StoreSetting::query()->pluck('value', 'key');
            $values = [];

            foreach (config('site_settings') as $group) {
                foreach ($group['fields'] as $key => $field) {
                    $values[$key] = (string) ($saved[$key] ?? $field['default'] ?? '');
                }
            }

            $attributes->set('site.settings', $values);
        }

        return $attributes->get('site.settings');
    }

    public static function get(string $key, string $default = ''): string
    {
        $value = self::all()[$key] ?? '';

        return $value !== '' ? $value : $default;
    }

    /**
     * Enlace de WhatsApp, o null si no hay número configurado (entonces la web
     * oculta esos botones en lugar de enlazar a un número inexistente).
     */
    public static function whatsappUrl(?string $message = null): ?string
    {
        $number = self::get('contact_whatsapp');

        if ($number === '') {
            return null;
        }

        return 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '');
    }

    /**
     * Redes configuradas, en el orden de la configuración.
     *
     * @return array<int,array{key:string,label:string,url:string}>
     */
    public static function socialLinks(): array
    {
        $links = [];

        foreach (config('site_settings.social.fields') as $key => $field) {
            $url = self::get($key);

            if ($url !== '') {
                $links[] = ['key' => str_replace('social_', '', $key), 'label' => $field['label'], 'url' => $url];
            }
        }

        return $links;
    }

    /**
     * Código de terceros para uno de los tres puntos de la página.
     *
     * Se devuelve como HtmlString porque es HTML a propósito: solo lo edita
     * quien administra el sitio y únicamente se imprime en la web pública.
     */
    public static function script(string $slot): HtmlString
    {
        return new HtmlString(self::get("scripts_{$slot}"));
    }
}
