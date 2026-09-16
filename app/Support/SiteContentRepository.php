<?php

namespace App\Support;

use App\Models\SiteContent;
use Illuminate\Support\Collection;

/**
 * Resuelve los espacios de contenido de la web: combina la definición de
 * config/site_contents.php con lo que el administrador editó.
 *
 * Se consulta una vez por petición porque la usan varias secciones de la
 * misma página.
 */
final class SiteContentRepository
{
    /** @return Collection<string,array<string,mixed>> */
    public static function all(): Collection
    {
        $attributes = request()->attributes;

        if (! $attributes->has('site.contents')) {
            $saved = SiteContent::query()->get()->keyBy('key');

            $attributes->set('site.contents', collect(config('site_contents'))
                ->map(fn (array $slot, string $key) => self::resolve($key, $slot, $saved->get($key))));
        }

        return $attributes->get('site.contents');
    }

    /** @return array<string,mixed> */
    public static function get(string $key): array
    {
        return self::all()->get($key, []);
    }

    /** Solo los espacios de un grupo, en el orden de la configuración. */
    public static function group(string $prefix): Collection
    {
        return self::all()->filter(fn ($slot, string $key) => str_starts_with($key, $prefix));
    }

    /**
     * @param  array<string,mixed>  $slot
     * @return array<string,mixed>
     */
    private static function resolve(string $key, array $slot, ?SiteContent $saved): array
    {
        return [
            'key' => $key,
            'label' => $slot['label'],
            'group' => $slot['group'],
            'hint' => $slot['hint'] ?? null,
            'fields' => $slot['fields'],
            // Proporción con la que se recorta la foto al subirla.
            'aspect' => $slot['aspect'] ?? 4 / 3,
            'anchor' => $slot['anchor'] ?? null,
            // El valor editado manda; si está vacío se usa el de la configuración.
            'title' => $saved?->title ?: ($slot['default']['title'] ?? null),
            'text' => $saved?->text ?: ($slot['default']['text'] ?? null),
            'image_url' => $saved?->imageUrl(),
        ];
    }
}
