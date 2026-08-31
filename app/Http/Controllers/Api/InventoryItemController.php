<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inventory\AdjustInventoryAction;
use App\Enums\InventoryMovementType;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustInventoryRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\InventoryMovementResource;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $items = InventoryItem::query()
            ->with('category')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->when($request->boolean('sellable'), fn ($q) => $q->where('is_sellable', true))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($items, InventoryItemResource::class);
    }

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        return $this->ok(new InventoryItemResource($inventoryItem->load('category')));
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = InventoryItem::create($request->validated());

        // El stock inicial se registra como movimiento para que el saldo tenga
        // origen y no aparezca "de la nada" (§24).
        if ((float) $item->stock > 0) {
            $item->movements()->create([
                'type' => InventoryMovementType::PURCHASE,
                'quantity' => (float) $item->stock,
                'stock_after' => (float) $item->stock,
                'unit_cost' => $item->cost,
                'user_id' => $request->user()->id,
                'notes' => 'Stock inicial',
            ]);
        }

        return $this->created(
            new InventoryItemResource($item->load('category')),
            'Producto registrado correctamente.'
        );
    }

    public function update(StoreInventoryItemRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        $inventoryItem->update($request->safe()->except('stock'));

        return $this->ok(
            new InventoryItemResource($inventoryItem->load('category')),
            'Producto actualizado correctamente.'
        );
    }

    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        if ($inventoryItem->movements()->exists()) {
            $inventoryItem->update(['active' => false]);

            return $this->ok(null, 'El producto tiene movimientos, por lo que se desactivó en lugar de eliminarse.');
        }

        $inventoryItem->delete();

        return $this->ok(null, 'Producto eliminado correctamente.');
    }

    /** Historial de movimientos de un producto. */
    public function movements(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $movements = $inventoryItem->movements()
            ->with('user')
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return $this->paginated($movements, InventoryMovementResource::class);
    }

    /** Entrada, salida o ajuste manual de stock. */
    public function adjust(
        AdjustInventoryRequest $request,
        InventoryItem $inventoryItem,
        AdjustInventoryAction $action,
    ): JsonResponse {
        $movement = $action->execute(
            item: $inventoryItem,
            type: InventoryMovementType::from($request->string('type')->value()),
            quantity: (float) $request->input('quantity'),
            userId: $request->user()->id,
            notes: $request->input('notes'),
        );

        return $this->created(
            new InventoryMovementResource($movement->load('item')),
            'Movimiento registrado correctamente.'
        );
    }
}
