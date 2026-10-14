<?php

namespace App\Support;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Shop\StoreCatalog;
use Illuminate\Support\Collection;

/**
 * Datos compartidos por el menú y el pie de la web pública. Se guardan en la
 * petición porque los usan el menú, el pie y la página a la vez.
 */
final class SiteMenu
{
    /**
     * Categorías de productos con algo publicado, con sus productos cargados.
     *
     * @return Collection<int,InventoryCategory>
     */
    public static function productCategories(): Collection
    {
        return self::remember('site.product_categories', fn () => app(StoreCatalog::class)->productCategories());
    }

    /**
     * Categorías de servicios con algo publicado, con sus servicios cargados.
     *
     * @return Collection<int,ServiceCategory>
     */
    public static function serviceCategories(): Collection
    {
        return self::remember('site.service_categories', fn () => app(StoreCatalog::class)->serviceCategories());
    }

    /** @return Collection<int,Package> Paquetes publicados. */
    public static function packages(): Collection
    {
        return self::remember('site.packages', fn () => app(StoreCatalog::class)->packages());
    }

    /** Si el menú muestra «Promociones» (solo cuando hay alguna). */
    public static function hasPromotions(): bool
    {
        return app(StoreCatalog::class)->hasPromotions();
    }

    /**
     * Servicios publicados en promoción, de lo mismo que arma /servicios y en
     * su mismo orden.
     *
     * @return Collection<int,Service>
     */
    public static function promotedServices(): Collection
    {
        return self::remember('site.promoted_services', fn () => self::serviceCategories()
            ->flatMap(fn (ServiceCategory $category) => $category->services)
            ->filter(fn (Service $service) => $service->onPromotion())
            ->values());
    }

    /**
     * Productos publicados en promoción (con o sin categoría), de lo mismo que
     * arma /productos y en su mismo orden.
     *
     * @return Collection<int,InventoryItem>
     */
    public static function promotedProducts(): Collection
    {
        return self::remember('site.promoted_products', fn () => self::productCategories()
            ->flatMap(fn (InventoryCategory $category) => $category->items)
            ->concat(app(StoreCatalog::class)->uncategorizedProducts())
            ->filter(fn (InventoryItem $item) => $item->onPromotion())
            ->values());
    }

    private static function remember(string $key, callable $resolve): Collection
    {
        $attributes = request()->attributes;

        if (! $attributes->has($key)) {
            $attributes->set($key, $resolve());
        }

        return $attributes->get($key);
    }
}
