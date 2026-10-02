<?php

namespace App\Services\Erp;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copia en esta base lo que el sistema administra de cada ítem: nombre,
 * categoría, precio, costo, stock y si está activo.
 *
 * Lo que es de la web no se toca: imagen, descripción, si se publica, la
 * duración y quién atiende un servicio. Un ítem nuevo llega sin publicar; el
 * personal decide qué mostrar.
 *
 * El stock de la copia no deja movimientos aquí: el kardex vive en el sistema.
 */
class CatalogSync
{
    public function __construct(private readonly ErpClient $erp) {}

    /**
     * Aplica ítems con la forma del catálogo del sistema.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function apply(array $items): int
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $item['type'] === 'service' ? $this->applyService($item) : $this->applyProduct($item);
            }
        });

        return count($items);
    }

    /**
     * Trae el catálogo completo. Lo que tiene enlace pero el sistema ya no
     * devuelve (se borró allá) queda inactivo aquí, nunca se borra: sus
     * atenciones y ventas lo siguen referenciando.
     *
     * @return array{actualizados:int, desactivados:int}
     */
    public function pull(): array
    {
        $items = $this->erp->catalog();
        $updated = $this->apply($items);

        $ids = array_column($items, 'id');
        $deactivated = InventoryItem::whereNotNull('erp_id')->whereNotIn('erp_id', $ids)->where('active', true)->update(['active' => false])
            + Service::whereNotNull('erp_id')->whereNotIn('erp_id', $ids)->where('active', true)->update(['active' => false]);

        return ['actualizados' => $updated, 'desactivados' => $deactivated];
    }

    /**
     * Stock que devolvió el sistema tras una operación (venta, anulación,
     * consumo), indexado por su id de producto.
     *
     * @param  array<int|string, float|int|string>  $stock
     */
    public function applyStock(array $stock): void
    {
        foreach ($stock as $erpId => $quantity) {
            InventoryItem::where('erp_id', (int) $erpId)->update(['stock' => (float) $quantity]);
        }
    }

    /** Código con el que un ítem de esta web se da de alta en el sistema (WEB-P-12, WEB-S-5). */
    public static function webCode(InventoryItem|Service $local): string
    {
        return ($local instanceof Service ? 'WEB-S-' : 'WEB-P-').$local->getKey();
    }

    /**
     * La fila de aquí que corresponde a un ítem del sistema: la ya enlazada o,
     * si el ítem nació aquí (código WEB-…), la original aún sin enlazar. Así
     * el aviso del sistema y el comando de alta inicial terminan en la misma
     * fila aunque lleguen a la vez, en vez de crear un duplicado.
     *
     * @param  class-string<InventoryItem|Service>  $model
     * @param  array<string, mixed>  $item
     */
    private function localFor(string $model, array $item): InventoryItem|Service
    {
        $prefix = $model === Service::class ? 'WEB-S-' : 'WEB-P-';
        $code = (string) ($item['code'] ?? '');

        return $model::where('erp_id', $item['id'])->first()
            ?? (str_starts_with($code, $prefix)
                ? $model::whereKey((int) substr($code, strlen($prefix)))->whereNull('erp_id')->first()
                : null)
            ?? new $model;
    }

    /** @param  array<string, mixed>  $item */
    private function applyProduct(array $item): void
    {
        $local = $this->localFor(InventoryItem::class, $item);

        if (! $local->exists) {
            $local->fill([
                'description' => Str::limit((string) ($item['description'] ?? ''), 497) ?: null,
                'is_sellable' => true,
                'is_published' => false,
            ]);
        }

        $local->fill([
            'name' => $item['name'],
            'category_id' => $this->categoryId(InventoryCategory::class, $item['category'] ?? null),
            'unit' => Str::limit((string) ($item['unit'] ?? $local->unit ?? 'unidad'), 20, '') ?: 'unidad',
            'cost' => $item['cost'] ?? 0,
            'sale_price' => ($item['price'] ?? 0) > 0 ? $item['price'] : null,
            'min_stock' => $item['stock_min'] ?? 0,
            'active' => (bool) $item['active'],
        ]);
        $local->forceFill(['erp_id' => $item['id'], 'stock' => (float) ($item['stock'] ?? 0)])->save();
    }

    /** @param  array<string, mixed>  $item */
    private function applyService(array $item): void
    {
        $local = $this->localFor(Service::class, $item);

        if (! $local->exists) {
            $local->fill([
                'description' => $item['description'] ?? null,
                'duration_minutes' => 60,
                'is_published' => false,
            ]);
        }

        $local->fill([
            'name' => $item['name'],
            // Un servicio aquí siempre necesita categoría (la agenda las usa).
            'category_id' => $this->categoryId(ServiceCategory::class, $item['category'] ?? null) ?? $this->categoryId(ServiceCategory::class, 'Otros'),
            'price' => $item['price'] ?? 0,
            'active' => (bool) $item['active'],
        ]);
        $local->forceFill(['erp_id' => $item['id']])->save();
    }

    /** Las categorías se enlazan por nombre: es lo que comparten las dos bases. */
    private function categoryId(string $model, ?string $name): ?int
    {
        $name = trim((string) $name);

        return $name === '' ? null : $model::firstOrCreate(['name' => $name], ['active' => true])->id;
    }
}
