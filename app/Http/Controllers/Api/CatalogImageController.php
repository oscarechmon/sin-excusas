<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadCatalogImageRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\ServiceResource;
use App\Models\InventoryItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Fotos de servicios y productos que se muestran en la web pública. */
class CatalogImageController extends Controller
{
    use ApiResponses;

    public function storeService(UploadCatalogImageRequest $request, Service $service): JsonResponse
    {
        $this->replace($service, $request->file('image'), 'services');

        return $this->ok(new ServiceResource($service->load('category')), 'Foto actualizada.');
    }

    public function destroyService(Service $service): JsonResponse
    {
        $this->replace($service, null, 'services');

        return $this->ok(new ServiceResource($service->load('category')), 'Foto eliminada.');
    }

    public function storeInventoryItem(UploadCatalogImageRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        $this->replace($inventoryItem, $request->file('image'), 'products');

        return $this->ok(new InventoryItemResource($inventoryItem->load('category')), 'Foto actualizada.');
    }

    public function destroyInventoryItem(InventoryItem $inventoryItem): JsonResponse
    {
        $this->replace($inventoryItem, null, 'products');

        return $this->ok(new InventoryItemResource($inventoryItem->load('category')), 'Foto eliminada.');
    }

    /** Guarda la nueva foto (o ninguna) y borra la anterior para no dejar archivos huérfanos. */
    private function replace(Model $model, ?UploadedFile $file, string $folder): void
    {
        $previous = $model->image_path;

        $model->update([
            'image_path' => $file?->store("catalog/{$folder}", 'public'),
        ]);

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }
    }
}
