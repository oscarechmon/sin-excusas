<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Support\SiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Contacto, redes y scripts de la web pública. */
class SiteSettingController extends Controller
{
    use ApiResponses;

    public function index(): JsonResponse
    {
        return $this->ok($this->payload());
    }

    public function update(Request $request): JsonResponse
    {
        // Las reglas salen de la misma definición que arma el formulario: un
        // campo nuevo se valida sin tocar el controlador.
        $rules = [];
        $attributes = [];

        foreach ($this->fields() as $key => $field) {
            $rules["values.{$key}"] = $field['rules'] ?? ['nullable', 'string', 'max:255'];
            $attributes["values.{$key}"] = $field['label'];
        }

        $request->validate($rules, [], $attributes);

        // Solo se tocan los campos que llegaron: un envío parcial no borra el resto.
        $values = (array) $request->input('values', []);

        foreach (array_keys($this->fields()) as $key) {
            if (array_key_exists($key, $values)) {
                StoreSetting::put($key, trim((string) $values[$key]));
            }
        }

        request()->attributes->remove('site.settings');

        return $this->ok($this->payload(), 'Ajustes del sitio guardados.');
    }

    /** @return array<string,array<string,mixed>> */
    private function fields(): array
    {
        return collect(config('site_settings'))
            ->flatMap(fn (array $group) => $group['fields'])
            ->all();
    }

    /** @return array<string,mixed> */
    private function payload(): array
    {
        $groups = collect(config('site_settings'))
            ->map(fn (array $group, string $key) => [
                'key' => $key,
                'label' => $group['label'],
                'hint' => $group['hint'] ?? null,
                'fields' => collect($group['fields'])
                    ->map(fn (array $field, string $fieldKey) => [
                        'key' => $fieldKey,
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'hint' => $field['hint'] ?? null,
                        'placeholder' => $field['placeholder'] ?? null,
                    ])
                    ->values(),
            ])
            ->values();

        return ['groups' => $groups, 'values' => SiteSettings::all()];
    }
}
