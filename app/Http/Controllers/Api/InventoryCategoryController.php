<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryCategoryRequest;
use App\Http\Resources\InventoryCategoryResource;
use App\Models\InventoryCategory;
use Illuminate\Http\JsonResponse;

class InventoryCategoryController extends Controller
{
    use ApiResponses;

    public function index(): JsonResponse
    {
        $categories = InventoryCategory::withCount('items')->orderBy('name')->get();

        return $this->ok(InventoryCategoryResource::collection($categories));
    }

    public function store(StoreInventoryCategoryRequest $request): JsonResponse
    {
        $category = InventoryCategory::create($request->validated());

        return $this->created(new InventoryCategoryResource($category), 'Categoría creada correctamente.');
    }

    public function update(StoreInventoryCategoryRequest $request, InventoryCategory $inventoryCategory): JsonResponse
    {
        $inventoryCategory->update($request->validated());

        return $this->ok(new InventoryCategoryResource($inventoryCategory), 'Categoría actualizada correctamente.');
    }

    public function destroy(InventoryCategory $inventoryCategory): JsonResponse
    {
        if ($inventoryCategory->items()->exists()) {
            return $this->failed('No se puede eliminar una categoría que tiene productos asociados.');
        }

        $inventoryCategory->delete();

        return $this->ok(null, 'Categoría eliminada correctamente.');
    }
}
