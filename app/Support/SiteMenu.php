<?php

namespace App\Support;

use App\Models\InventoryCategory;
use App\Models\Package;
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

    private static function remember(string $key, callable $resolve): Collection
    {
        $attributes = request()->attributes;

        if (! $attributes->has($key)) {
            $attributes->set($key, $resolve());
        }

        return $attributes->get($key);
    }
}
