<?php

namespace App\Services\Shop;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\OnlineOrder;
use App\Models\OnlineOrderItem;
use App\Models\Package;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Erp\CatalogSync;
use App\Services\Erp\ErpClient;
use App\Services\Erp\LiveCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Lo que la tienda muestra y vende.
 *
 * Sin el sistema conectado se lee de esta base, como siempre. Con el sistema,
 * la web es un cascarón: qué se publica y en qué orden, nombre, precio, stock,
 * categoría, imagen y descripción se leen en vivo del sistema (LiveCatalog), y
 * aquí solo queda el enlace de cada ítem para el carrito y los pedidos.
 */
class StoreCatalog
{
    public function __construct(
        private readonly ErpClient $erp,
        private readonly LiveCatalog $live,
        private readonly CatalogSync $sync,
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

        return $this->grouped($this->inWebOrder($this->published(Service::query(), 'service')), 'services');
    }

    /** @return Collection<int, Package> Paquetes publicados con sus servicios, del más barato al más caro. */
    public function packages(): Collection
    {
        $packages = Package::query()->with(['services' => fn ($q) => $q->orderBy('name')]);

        if (! $this->erp->enabled()) {
            return $packages->published()->orderBy('price')->get();
        }

        return $this->published($packages, 'package')
            ->sortBy(fn (Package $package) => (float) $package->price)
            ->values();
    }

    /**
     * Si hay algún producto o servicio publicado en promoción. Se mira en lo
     * leído del sistema, sin armar el catálogo: lo usa el menú en cada página.
     */
    public function hasPromotions(): bool
    {
        if (! $this->erp->enabled()) {
            return false;
        }

        foreach ($this->live->snapshot() as $item) {
            if (in_array($item['type'] ?? 'product', ['product', 'service'], true)
                && $item['active'] && ($item['web_published'] ?? false) && ($item['on_promotion'] ?? false)) {
                return true;
            }
        }

        return false;
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

        return $this->live(InventoryItem::whereIn('id', $ids)->get(), $fresh)
            ->filter(fn (InventoryItem $item) => $item->active && $item->is_published && (float) $item->sale_price > 0)
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

        return $this->live(Service::whereIn('id', $ids)->get(), $fresh)
            ->filter(fn (Service $service) => $service->active && $service->is_published && (float) $service->price > 0)
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

    /** @return Collection<int, InventoryItem> Productos publicados, en el orden del sistema. */
    private function publishedProducts(): Collection
    {
        return $this->inWebOrder($this->published(InventoryItem::query(), 'product'));
    }

    /**
     * En el orden que se les dio en el sistema («Orden en la web»); lo que
     * nunca se ordenó va al final, por nombre.
     *
     * @template TModel of InventoryItem|Service
     *
     * @param  Collection<int, TModel>  $items
     * @return Collection<int, TModel>
     */
    private function inWebOrder(Collection $items): Collection
    {
        $snapshot = $this->live->snapshot();
        $position = fn (Model $item): int => $snapshot[$item->erp_id]['sort_order'] ?? PHP_INT_MAX;

        return $items->sortBy([
            fn (Model $a, Model $b) => $position($a) <=> $position($b),
            fn (Model $a, Model $b) => strnatcasecmp($a->name, $b->name),
        ])->values();
    }

    /**
     * Lo que el sistema tiene activo y publicado en la web, de un tipo. Si
     * algo publicado allá todavía no tiene su enlace aquí (se perdió el
     * aviso), se enlaza en el momento: la web muestra lo que dice el sistema.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Collection<int, TModel>
     */
    private function published(Builder $query, string $type): Collection
    {
        $this->sync->ensureLinked(array_values(array_filter(
            $this->live->snapshot(),
            fn (array $item) => ($item['type'] ?? 'product') === $type && $item['active'] && ($item['web_published'] ?? false),
        )));

        return $this->live->hydrate($query->whereNotNull('erp_id')->get())
            ->filter(fn (Model $model) => $model->active && $model->is_published);
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
     * Agrupa por la categoría que dice el sistema, en el orden que se les dio
     * allá; las que nunca se ordenaron, después, en el orden en que se crearon
     * aquí (las que aún no existen aquí, como «Otros», al final).
     *
     * @param  Collection<int, InventoryItem|Service>  $items
     * @return Collection<int, InventoryCategory|ServiceCategory>
     */
    private function grouped(Collection $items, string $relation): Collection
    {
        $snapshot = $this->live->snapshot();

        return $items->groupBy(fn (Model $item) => $item->category->name)
            ->map(fn (Collection $group) => [
                'order' => $snapshot[$group->first()->erp_id]['category_order'] ?? PHP_INT_MAX,
                'category' => $group->first()->category->setRelation($relation, $group->values()),
            ])
            ->sortBy([
                fn (array $a, array $b) => $a['order'] <=> $b['order'],
                fn (array $a, array $b) => ($a['category']->getKey() ?? PHP_INT_MAX) <=> ($b['category']->getKey() ?? PHP_INT_MAX),
            ])
            ->pluck('category')
            ->values();
    }
}
