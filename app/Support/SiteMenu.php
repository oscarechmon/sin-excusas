<?php

namespace App\Support;

use App\Models\InventoryCategory;
use Illuminate\Support\Collection;

/** Datos compartidos por el menú de la web pública. */
final class SiteMenu
{
    /**
     * Categorías de productos con algo publicado, con sus productos cargados.
     * Se guarda en la petición porque la usan el menú y la página a la vez.
     *
     * @return Collection<int,InventoryCategory>
     */
    public static function productCategories(): Collection
    {
        $attributes = request()->attributes;

        if (! $attributes->has('site.product_categories')) {
            $attributes->set('site.product_categories', InventoryCategory::query()
                ->where('active', true)
                ->whereHas('items', fn ($q) => $q->published())
                ->with(['items' => fn ($q) => $q->published()->orderBy('name')])
                ->orderBy('id')
                ->get());
        }

        return $attributes->get('site.product_categories');
    }
}
