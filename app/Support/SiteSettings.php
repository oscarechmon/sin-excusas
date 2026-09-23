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
    /** Días de la semana, en el orden en que se muestran. */
    public const DAYS = [
        'mon' => ['label' => 'Lunes', 'short' => 'Lun'],
        'tue' => ['label' => 'Martes', 'short' => 'Mar'],
        'wed' => ['label' => 'Miércoles', 'short' => 'Mié'],
        'thu' => ['label' => 'Jueves', 'short' => 'Jue'],
        'fri' => ['label' => 'Viernes', 'short' => 'Vie'],
        'sat' => ['label' => 'Sábado', 'short' => 'Sáb'],
        'sun' => ['label' => 'Domingo', 'short' => 'Dom'],
    ];

    /** @return array<string,array<string,mixed>> */
    public static function all(): array
    {
        $attributes = request()->attributes;

        if (! $attributes->has('site.settings')) {
            $saved = StoreSetting::query()->pluck('value', 'key');
            $values = [];

            foreach (config('site_settings') as $group) {
                foreach ($group['fields'] as $key => $field) {
                    if ($field['type'] === 'hours') {
                        $stored = json_decode((string) ($saved[$key] ?? ''), true);
                        $values[$key] = self::normalizeHours(is_array($stored) ? $stored : ($field['default'] ?? []));

                        continue;
                    }

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
        $value = is_string($value) ? $value : '';

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

    /** Palabras clave listas para la etiqueta: sin espacios sueltos ni repetidas. */
    public static function keywords(): string
    {
        return collect(explode(',', self::get('seo_keywords')))
            ->map(fn (string $word) => trim(preg_replace('/\s+/', ' ', $word)))
            ->filter()
            ->unique()
            ->implode(', ');
    }

    /**
     * Horario completo, con los siete días siempre presentes.
     *
     * @return array<string,array{open:bool,from:string,to:string}>
     */
    public static function hours(): array
    {
        $hours = self::all()['contact_hours'] ?? [];

        return is_array($hours) ? $hours : [];
    }

    /**
     * Deja el horario en una forma predecible: los siete días, en orden, con
     * horas válidas. Así da igual lo que llegue guardado de antes.
     *
     * @param  array<string,mixed>  $hours
     * @return array<string,array{open:bool,from:string,to:string}>
     */
    public static function normalizeHours(array $hours): array
    {
        $clean = [];

        foreach (array_keys(self::DAYS) as $day) {
            $entry = is_array($hours[$day] ?? null) ? $hours[$day] : [];
            $clean[$day] = [
                'open' => filter_var($entry['open'] ?? false, FILTER_VALIDATE_BOOL),
                'from' => self::time($entry['from'] ?? null, '09:00'),
                'to' => self::time($entry['to'] ?? null, '20:00'),
            ];
        }

        return $clean;
    }

    /**
     * Horario agrupado para mostrarlo: los días seguidos con las mismas horas
     * se juntan en un solo renglón ("Lun a Vie · 9:00 a 20:00").
     *
     * @return array<int,array{days:string,hours:string}>
     */
    public static function scheduleGroups(): array
    {
        $groups = [];
        $current = null;

        foreach (self::hours() as $day => $entry) {
            if (! $entry['open']) {
                $current = null;

                continue;
            }

            $range = self::hour($entry['from']).' a '.self::hour($entry['to']);

            // Solo se extiende el grupo si el día anterior tenía el mismo horario.
            if ($current !== null && $groups[$current]['hours'] === $range) {
                $groups[$current]['last'] = $day;

                continue;
            }

            $groups[] = ['first' => $day, 'last' => $day, 'hours' => $range];
            $current = array_key_last($groups);
        }

        return array_map(fn (array $group) => [
            'days' => $group['first'] === $group['last']
                ? self::DAYS[$group['first']]['label']
                : self::DAYS[$group['first']]['short'].' a '.self::DAYS[$group['last']]['short'],
            'hours' => $group['hours'],
        ], $groups);
    }

    /** Una línea para la barra superior: "Lun a Vie · 9:00 a 20:00". */
    public static function scheduleSummary(): string
    {
        return collect(self::scheduleGroups())
            ->map(fn (array $group) => $group['days'].' '.$group['hours'])
            ->implode(' · ');
    }

    private static function time(mixed $value, string $default): string
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : $default;
    }

    /** 09:00 se lee mejor como 9:00. */
    private static function hour(string $time): string
    {
        return ltrim($time, '0') ?: '0:00';
    }
}
