<?php

namespace App\Services\Erp;

use App\Exceptions\ErpException;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * El catálogo del sistema leído en vivo por su API: nombre, categoría, precio,
 * stock, si sigue activo y, en un paquete, sus sesiones, vigencia y servicios.
 *
 * La web no guarda nada de eso. Sus filas de productos, servicios y paquetes
 * son la ficha web de cada ítem (imagen, descripción, si se publica), enlazada
 * por `erp_id`, y al mostrarlas se completan con lo que dice el sistema
 * (`hydrate`) sin escribir en la base.
 *
 * Para no consultar al sistema en cada página, lo leído se reutiliza unos
 * segundos (`erp.catalog_ttl`) y cada aviso de cambio del sistema lo invalida.
 * Si el sistema no contesta, se sigue con lo último que respondió. El carrito
 * y el pago no se fían de eso: consultan el stock en el momento (`fresh`).
 *
 * Sin el sistema conectado no hace nada: los datos de la base son los reales.
 */
class LiveCatalog
{
    private const CACHE_KEY = 'erp.catalog';

    /** Lo último que respondió el sistema, para cuando no conteste. */
    private const LAST_KEY = 'erp.catalog.last';

    /** @var array<int, array<string, mixed>>|null */
    private ?array $snapshot = null;

    /** @var array<class-string, array<string, Model>> Categorías de aquí por nombre. */
    private array $categories = [];

    public function __construct(private readonly ErpClient $erp) {}

    /**
     * Todo el catálogo del sistema, por id de allá.
     *
     * @return array<int, array<string, mixed>>
     */
    public function snapshot(): array
    {
        return $this->snapshot ??= Cache::remember(self::CACHE_KEY, $this->ttl(), fn () => $this->fetch());
    }

    /**
     * Estos ítems tal como están ahora en el sistema, sin reutilizar nada.
     * Lanza ErpException si el sistema no contesta.
     *
     * @param  array<int, int|null>  $erpIds
     * @return array<int, array<string, mixed>>
     */
    public function fresh(array $erpIds): array
    {
        $erpIds = array_values(array_unique(array_filter($erpIds)));

        return $erpIds === [] ? [] : $this->keyed($this->erp->catalog($erpIds));
    }

    /**
     * Catálogo completo recién traído (al sincronizar): queda como el vigente.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function put(array $items): void
    {
        $keyed = $this->keyed($items);

        Cache::put(self::CACHE_KEY, $keyed, $this->ttl());
        Cache::forever(self::LAST_KEY, $keyed);
        $this->snapshot = $keyed;
        $this->categories = [];
    }

    /** Algo cambió en el sistema: la próxima lectura lo vuelve a pedir. */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->snapshot = null;
        $this->categories = [];
    }

    /**
     * Completa las fichas web con lo que dice el sistema, solo en memoria. Lo
     * que el sistema no tiene (o lo que nunca se enlazó) queda inactivo: no se
     * muestra ni se vende.
     *
     * @template TModels of Model|iterable<Model>
     *
     * @param  TModels  $models
     * @param  array<int, array<string, mixed>>|null  $items  Lo leído con `fresh`; si no, el catálogo vigente.
     * @return TModels
     */
    public function hydrate(Model|iterable $models, ?array $items = null): Model|iterable
    {
        if (! $this->erp->enabled()) {
            return $models;
        }

        $list = $models instanceof Model ? [$models] : $models;
        // Lo que nunca se enlazó no está en el sistema: no hace falta preguntarle.
        $linked = collect($list)->contains(fn (Model $model) => $model->erp_id !== null);
        $items ??= $linked ? $this->snapshot() : [];
        $includedServices = $this->includedServices($list, $items);

        foreach ($list as $model) {
            $item = $model->erp_id !== null ? ($items[$model->erp_id] ?? null) : null;

            match (true) {
                $model instanceof InventoryItem => $this->product($model, $item),
                $model instanceof Service => $this->service($model, $item),
                $model instanceof Package => $this->package($model, $item, $includedServices),
            };
        }

        return $models;
    }

    private function product(InventoryItem $model, ?array $item): void
    {
        if ($item === null) {
            $this->overlay($model, ['active' => false, 'stock' => 0]);

            return;
        }

        $category = $this->category(InventoryCategory::class, $item['category'] ?? null);

        $this->overlay($model, [
            'name' => $item['name'],
            'category_id' => $category?->getKey(),
            'unit' => $item['unit'] ?? $model->unit,
            'cost' => $item['cost'] ?? 0,
            'sale_price' => ($item['price'] ?? 0) > 0 ? $item['price'] : null,
            'stock' => (float) ($item['stock'] ?? 0),
            'min_stock' => $item['stock_min'] ?? 0,
            'active' => (bool) $item['active'],
        ]);
        $model->setRelation('category', $category);
    }

    private function service(Service $model, ?array $item): void
    {
        if ($item === null) {
            $this->overlay($model, ['active' => false]);

            return;
        }

        // La web agrupa los servicios por categoría: sin una, van a "Otros".
        $category = $this->category(ServiceCategory::class, $item['category'] ?? null)
            ?? $this->category(ServiceCategory::class, 'Otros');

        $this->overlay($model, [
            'name' => $item['name'],
            'category_id' => $category->getKey(),
            'price' => $item['price'] ?? 0,
            'active' => (bool) $item['active'],
        ]);
        $model->setRelation('category', $category);
    }

    /** @param  array<int, Service>  $includedServices  Servicios de aquí por id del sistema. */
    private function package(Package $model, ?array $item, array $includedServices): void
    {
        if ($item === null) {
            $this->overlay($model, ['active' => false]);

            return;
        }

        $details = (array) ($item['package'] ?? []);

        $this->overlay($model, [
            'name' => $item['name'],
            'price' => $item['price'] ?? 0,
            'total_sessions' => max(1, (int) ($details['total_sessions'] ?? $model->total_sessions ?? 1)),
            'validity_days' => $details['validity_days'] ?? null,
            'active' => (bool) $item['active'],
        ]);

        if (array_key_exists('service_ids', $details)) {
            $model->setRelation('services', $model->newCollection(array_values(array_filter(array_map(
                fn ($erpId) => $includedServices[(int) $erpId] ?? null,
                (array) $details['service_ids'],
            ))))->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values());
        }
    }

    /**
     * Los servicios que incluyen los paquetes de esta tanda, en una sola
     * consulta y ya completados con lo del sistema.
     *
     * @param  iterable<Model>  $models
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, Service>
     */
    private function includedServices(iterable $models, array $items): array
    {
        $erpIds = [];
        foreach ($models as $model) {
            if ($model instanceof Package && $model->erp_id !== null) {
                array_push($erpIds, ...(array) ($items[$model->erp_id]['package']['service_ids'] ?? []));
            }
        }

        if ($erpIds === []) {
            return [];
        }

        $services = Service::whereIn('erp_id', array_unique($erpIds))->orderBy('name')->get();

        return $this->hydrate($services, $items + $this->snapshot())->keyBy('erp_id')->all();
    }

    /**
     * Pone los datos del sistema en la ficha sin marcarlos como cambios: si
     * algo guarda después la ficha, no los escribe en la base.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function overlay(Model $model, array $attributes): void
    {
        $model->forceFill($attributes)->syncOriginalAttributes(array_keys($attributes));
    }

    /**
     * La categoría de aquí con ese nombre (las categorías se enlazan por
     * nombre). Si todavía no existe, una sin guardar, solo para agrupar.
     *
     * @param  class-string<InventoryCategory|ServiceCategory>  $class
     */
    private function category(string $class, ?string $name): ?Model
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $this->categories[$class] ??= $class::all()->keyBy(fn (Model $category) => mb_strtolower($category->name))->all();

        return $this->categories[$class][mb_strtolower($name)] ??= new $class(['name' => $name, 'active' => true]);
    }

    /** @return array<int, array<string, mixed>> */
    private function fetch(): array
    {
        try {
            $items = $this->keyed($this->erp->catalog());
            Cache::forever(self::LAST_KEY, $items);

            return $items;
        } catch (ErpException $e) {
            Log::warning('No se pudo leer el catálogo del sistema; se usa lo último que respondió.', ['error' => $e->getMessage()]);

            return Cache::get(self::LAST_KEY, []);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function keyed(array $items): array
    {
        return collect($items)->keyBy(fn (array $item) => (int) $item['id'])->all();
    }

    private function ttl(): int
    {
        return max(1, (int) config('erp.catalog_ttl', 60));
    }
}
