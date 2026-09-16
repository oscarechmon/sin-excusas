<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePublicationRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\PackageResource;
use App\Http\Resources\ServiceResource;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

/**
 * Switch "publicado" del catálogo web.
 *
 * Endpoint propio en lugar de reutilizar `update`: el switch envía un solo
 * campo y los FormRequest de edición exigen el formulario completo.
 */
class PublicationController extends Controller
{
    use ApiResponses;

    public function service(UpdatePublicationRequest $request, Service $service): JsonResponse
    {
        $service->update(['is_published' => $request->boolean('is_published')]);

        return $this->ok(
            new ServiceResource($service->load('category')),
            $this->message($service->is_published)
        );
    }

    public function package(UpdatePublicationRequest $request, Package $package): JsonResponse
    {
        $package->update(['is_published' => $request->boolean('is_published')]);

        return $this->ok(
            new PackageResource($package->load('services')),
            $this->message($package->is_published)
        );
    }

    public function inventoryItem(UpdatePublicationRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        // Un insumo de uso interno no tiene precio de venta que mostrar.
        if ($request->boolean('is_published') && ! $inventoryItem->is_sellable) {
            return $this->failed('Solo los productos marcados como vendibles pueden publicarse en la web.');
        }

        $inventoryItem->update(['is_published' => $request->boolean('is_published')]);

        return $this->ok(
            new InventoryItemResource($inventoryItem->load('category')),
            $this->message($inventoryItem->is_published)
        );
    }

    private function message(bool $published): string
    {
        return $published ? 'Publicado en la web.' : 'Retirado de la web.';
    }
}
