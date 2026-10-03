<?php

namespace App\Services\Shop;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\OnlineOrder;
use App\Models\OnlineOrderItem;
use App\Models\Package;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Erp\ErpClient;
use App\Services\Erp\LiveCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Lo que la tienda muestra y vende.
 *
 * Sin el sistema conectado se lee de esta base, como siempre. Con el sistema,
 * de aquí sale solo lo de la web (qué se publica, foto, descripción) y lo
 * demás (nombre, precio, stock, categoría, si sigue activo) se lee en vivo
 * del sistema (LiveCatalog).
 */
class StoreCatalog
{
    public function __construct(
        private readonly ErpClient $erp,
        private readonly LiveCatalog $live,
    ) {}

    /**
     * Categorías de productos con algo publicado, cada una con sus productos.
     *
     * @return Collection<int, InventoryCategory>
     */
    public function productCategories(): Collection
    {
        if (! $this->erp->enabled()) {
            return InventoryCategory::query()
                ->where('active', true)
                ->whereHas('items', fn ($q) => $q->published())
                ->with(['items' => fn ($q) => $q->published()->orderBy('name')])
                ->orderBy('id')
                ->get();
        }

        return $this->grouped($this->publishedProducts()->filter(fn (InventoryItem $item) => $item->category !== null), 'items');
    }

    /** @return Collection<int, InventoryItem> Productos publicados sin categoría. */
    public function uncategorizedProducts(): Collection
    {
        if (! $this->erp->enabled()) {
            return InventoryItem::published()->whereNull('category_id')->orderBy('name')->get();
        }

        return $this->publishedProducts()->filter(fn (InventoryItem $item) => $item->category === null)->values();
    }

    /**
     * Categorías de servicios con algo publicado, cada una con sus servicios.
     *
     * @return Collection<int, ServiceCategory>
     */
    public function serviceCategories(): Collection
    {
        if (! $this->erp->enabled()) {
            return ServiceCategory::query()
                ->where('active', true)
                ->whereHas('services', fn ($q) => $q->published())
                ->with(['services' => fn ($q) => $q->published()->orderBy('name')])
                ->orderBy('id')
                ->get();
        }

        $services = $this->live->hydrate(Service::where('is_published', true)->get())
            ->where('active', true)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);

        return $this->grouped($services, 'services');
    }

    /** @return Collection<int, Package> Paquetes publicados con sus servicios, del más barato al más caro. */
    public function packages(): Collection
    {
        $packages = Package::query()->with(['services' => fn ($q) => $q->orderBy('name')]);

        if (! $this->erp->enabled()) {
            return $packages->published()->orderBy('price')->get();
        }

        return $this->live->hydrate($packages->where('is_published', true)->get())
            ->where('active', true)
            ->sortBy(fn (Package $package) => (float) $package->price)
            ->values();
    }

    /**
     * Productos que se pueden comprar en línea (publicados y con precio), por id.
     *
     * Con `$fresh`, precio y stock se le preguntan al sistema en el momento;
     * si no contesta, lanza ErpException.
     *
     * @param  list<int>  $ids
     * @return Collection<int, InventoryItem>
     */
    public function purchasableProducts(array $ids, bool $fresh = false): Collection
    {
        if (! $this->erp->enabled()) {
            return InventoryItem::published()->where('sale_price', '>', 0)->whereIn('id', $ids)->get()->keyBy('id');
        }

        return $this->live(InventoryItem::where('is_published', true)->where('is_sellable', true)->whereIn('id', $ids)->get(), $fresh)
            ->filter(fn (InventoryItem $item) => $item->active && (float) $item->sale_price > 0)
            ->keyBy('id');
    }

    /**
     * Servicios que se pueden comprar en línea (publicados y con precio), por id.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Service>
     */
    public function purchasableServices(array $ids, bool $fresh = false): Collection
    {
        if (! $this->erp->enabled()) {
            return Service::published()->where('price', '>', 0)->whereIn('id', $ids)->get()->keyBy('id');
        }

        return $this->live(Service::where('is_published', true)->whereIn('id', $ids)->get(), $fresh)
            ->filter(fn (Service $service) => $service->active && (float) $service->price > 0)
            ->keyBy('id');
    }

    /**
     * Revisa, justo antes de cobrar, que todavía alcance el stock del pedido.
     * Con el sistema conectado se le pregunta en el momento; si no contesta,
     * lanza ErpException (mejor no cobrar que vender lo que no hay).
     *
     * @return string|null Qué falta, para decírselo al cliente; null si alcanza.
     */
    public function missingStock(OnlineOrder $order): ?string
    {
        $lines = $order->items->where('item_type', OnlineOrderItem::TYPE_PRODUCT)
            ->where('itemable_type', (new InventoryItem)->getMorphClass());

        if ($lines->isEmpty()) {
            return null;
        }

        $products = $this->live(InventoryItem::whereIn('id', $lines->pluck('itemable_id'))->get(), fresh: true)->keyBy('id');

        foreach ($lines as $line) {
            $product = $products->get($line->itemable_id);
            $available = $product?->active ? max(0, (int) floor((float) $product->stock)) : 0;

            if ((float) $line->quantity > $available) {
                return $available > 0
                    ? "Solo quedan {$available} unidad(es) de {$line->name}, así que no podemos cobrar este pedido. No se realizó ningún cargo: puedes hacer un nuevo pedido con lo disponible."
                    : "{$line->name} se agotó, así que no podemos cobrar este pedido. No se realizó ningún cargo.";
            }
        }

        return null;
    }

    /** @return Collection<int, InventoryItem> Productos publicados que el sistema tiene activos, por nombre. */
    private function publishedProducts(): Collection
    {
        return $this->live->hydrate(InventoryItem::where('is_published', true)->where('is_sellable', true)->get())
            ->where('active', true)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Completa con lo del sistema: lo vigente o, con `$fresh`, lo de este
     * momento. Sin el sistema conectado no cambia nada.
     *
     * @template TModel of Model
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, TModel>  $models
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    private function live(Collection $models, bool $fresh = false): Collection
    {
        if (! $this->erp->enabled()) {
            return $models;
        }

        return $this->live->hydrate($models, $fresh ? $this->live->fresh($models->pluck('erp_id')->all()) : null);
    }

    /**
     * Agrupa por la categoría que dice el sistema, en el orden en que se
     * crearon aquí (las que aún no existen aquí, al final).
     *
     * @param  Collection<int, InventoryItem|Service>  $items
     * @return Collection<int, InventoryCategory|ServiceCategory>
     */
    private function grouped(Collection $items, string $relation): Collection
    {
        return $items->groupBy(fn (Model $item) => $item->category->name)
            ->map(fn (Collection $group) => $group->first()->category->setRelation($relation, $group->values()))
            ->sortBy(fn (Model $category) => $category->getKey() ?? PHP_INT_MAX)
            ->values();
    }
}
