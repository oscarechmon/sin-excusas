<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadCatalogImageRequest;
use App\Models\SiteContent;
use App\Support\SiteContentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Fotos y textos editables de la web pública (Inicio y Nosotros). */
class SiteContentController extends Controller
{
    use ApiResponses;

    public function index(): JsonResponse
    {
        return $this->ok(SiteContentRepository::all()->values());
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $slot = $this->slot($key);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'text' => ['nullable', 'string', 'max:500'],
        ]);

        // Solo se guarda lo que ese espacio permite editar.
        SiteContent::query()->updateOrCreate(
            ['key' => $key],
            array_intersect_key($data, array_flip($slot['fields']))
        );

        return $this->ok($this->fresh($key), 'Contenido actualizado.');
    }

    public function storeImage(UploadCatalogImageRequest $request, string $key): JsonResponse
    {
        $this->slot($key);
        $this->replaceImage($key, $request->file('image'));

        return $this->ok($this->fresh($key), 'Foto actualizada.');
    }

    public function destroyImage(string $key): JsonResponse
    {
        $this->slot($key);
        $this->replaceImage($key, null);

        return $this->ok($this->fresh($key), 'Foto eliminada.');
    }

    /** @return array<string,mixed> */
    private function slot(string $key): array
    {
        // Las claves llevan puntos ('home.hero'), así que no se usa la notación
        // de puntos de config(): buscaría un array anidado que no existe.
        return config('site_contents')[$key] ?? abort(404, 'Ese espacio de contenido no existe.');
    }

    /** Guarda la nueva foto (o ninguna) y borra la anterior para no dejar archivos huérfanos. */
    private function replaceImage(string $key, ?UploadedFile $file): void
    {
        $content = SiteContent::query()->firstOrNew(['key' => $key]);
        $previous = $content->image_path;

        $content->fill(['image_path' => $file?->store('catalog/site', 'public')])->save();

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }
    }

    /** @return array<string,mixed> */
    private function fresh(string $key): array
    {
        request()->attributes->remove('site.contents');

        return SiteContentRepository::get($key);
    }
}
