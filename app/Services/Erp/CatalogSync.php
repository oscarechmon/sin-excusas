<?php

namespace App\Services\Erp;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Enlaza cada ítem del sistema con su ficha web: la fila de aquí que guarda lo
 * que es de la web (imagen, descripción, si se publica, la duración y quién
 * atiende un servicio). Un ítem nuevo llega sin publicar; el personal decide
 * qué mostrar.
 *
 * Nombre, categoría, precio, stock, si sigue activo y lo de cada paquete no se
 * copian: la web los lee en vivo del sistema (LiveCatalog). Aquí solo se
 * actualiza el nombre, como etiqueta para buscar en el panel; los demás campos
 * que la tabla trae de antes quedan sin uso mientras el sistema esté
 * conectado. Una ficha nueva nace con los valores que la tabla exige.
 *
 * Las categorías se crean aquí por nombre porque son de las dos partes: el
 * sistema decide a cuál va cada ítem y la web guarda su descripción.
 */
class CatalogSync
{
    public function __construct(
        private readonly ErpClient $erp,
        private readonly LiveCatalog $live,
    ) {}

    /**
     * Enlaza ítems con la forma del catálogo del sistema. Como algo cambió
     * allá, la web vuelve a leer el catálogo en la próxima página.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function apply(array $items): int
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                match ($item['type']) {
                    'service' => $this->linkService($item),
                    'package' => $this->linkPackage($item),
                    default => $this->linkProduct($item),
                };
            }
        });

        $this->live->forget();

        return count($items);
    }

    /**
     * Trae el catálogo completo, enlaza lo nuevo y lo deja como el catálogo
     * vigente de la web.
     *
     * Lo enlazado que el sistema ya no devuelve (se borró allá) deja de
     * mostrarse solo, porque no está en lo que se lee de allá. La fila nunca se
     * borra: sus pedidos y ventas la siguen referenciando.
     *
     * @return array{actualizados:int, fuera_del_sistema:int}
     */
    public function pull(): array
    {
        $items = $this->erp->catalog();
        $updated = $this->apply($items);
        $this->live->put($items);

        $ids = array_column($items, 'id');
        $gone = InventoryItem::whereNotNull('erp_id')->whereNotIn('erp_id', $ids)->count()
            + Service::whereNotNull('erp_id')->whereNotIn('erp_id', $ids)->count()
            + Package::whereNotNull('erp_id')->whereNotIn('erp_id', $ids)->count();

        return ['actualizados' => $updated, 'fuera_del_sistema' => $gone];
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
    private function linkProduct(array $item): void
    {
        $local = $this->localFor(InventoryItem::class, $item);

        if (! $local->exists) {
            $local->fill([
                'description' => Str::limit((string) ($item['description'] ?? ''), 497) ?: null,
                'unit' => Str::limit((string) ($item['unit'] ?? 'unidad'), 20, '') ?: 'unidad',
                'is_sellable' => true,
                'is_published' => false,
            ]);
        }

        // El producto no guarda su categoría, pero la categoría sí debe existir
        // aquí: la web guarda su descripción.
        $this->categoryId(InventoryCategory::class, $item['category'] ?? null);
        $local->fill(['name' => $item['name']]);
        $local->forceFill(['erp_id' => $item['id']])->save();
    }

    /** @param  array<string, mixed>  $item */
    private function linkService(array $item): void
    {
        $local = $this->localFor(Service::class, $item);
        $categoryId = $this->categoryId(ServiceCategory::class, $item['category'] ?? null)
            ?? $this->categoryId(ServiceCategory::class, 'Otros');

        if (! $local->exists) {
            $local->fill([
                'description' => $item['description'] ?? null,
                'duration_minutes' => 60,
                'is_published' => false,
                // La tabla los exige; con el sistema conectado se leen de allá.
                'category_id' => $categoryId,
                'price' => $item['price'] ?? 0,
            ]);
        }

        $local->fill(['name' => $item['name']]);
        $local->forceFill(['erp_id' => $item['id']])->save();
    }

    /**
     * Paquete del sistema. Sesiones, vigencia, precio y servicios incluidos se
     * leen de allá; aquí quedan si se publica y su descripción.
     *
     * @param  array<string, mixed>  $item
     */
    private function linkPackage(array $item): void
    {
        // Un paquete que nació aquí llega con código WEB-K-{id}: es esa fila.
        $code = (string) ($item['code'] ?? '');
        $local = Package::where('erp_id', $item['id'])->first()
            ?? (str_starts_with($code, 'WEB-K-') ? Package::whereKey((int) substr($code, 6))->whereNull('erp_id')->first() : null)
            ?? new Package;

        if (! $local->exists) {
            $local->fill([
                'description' => $item['description'] ?? null,
                'is_published' => false,
                // La tabla los exige; con el sistema conectado se leen de allá.
                'price' => $item['price'] ?? 0,
                'total_sessions' => max(1, (int) ($item['package']['total_sessions'] ?? 1)),
            ]);
        }

        $local->fill(['name' => $item['name']]);
        $local->forceFill(['erp_id' => $item['id']])->save();
    }

    /** Las categorías se enlazan por nombre: es lo que comparten las dos bases. */
    private function categoryId(string $model, ?string $name): ?int
    {
        $name = trim((string) $name);

        return $name === '' ? null : $model::firstOrCreate(['name' => $name], ['active' => true])->id;
    }
}
