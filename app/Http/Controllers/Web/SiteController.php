<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\ServiceCategory;
use App\Support\SiteContentRepository;
use App\Support\SiteMenu;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Web pública.
 *
 * Inicio y Nosotros toman sus fotos y textos de "Contenido web"; Servicios y
 * Productos se arman con lo que el administrador publicó desde el ERP.
 */
class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home', ['content' => SiteContentRepository::all()]);
    }

    public function about(): View
    {
        return view('site.about', ['content' => SiteContentRepository::all()]);
    }

    public function services(): View
    {
        // Una pestaña por categoría, y solo categorías con algo publicado.
        $categories = ServiceCategory::query()
            ->where('active', true)
            ->whereHas('services', fn ($q) => $q->published())
            ->with(['services' => fn ($q) => $q->published()->orderBy('name')])
            ->orderBy('id')
            ->get();

        $packages = Package::published()
            ->with(['services' => fn ($q) => $q->orderBy('name')])
            ->orderBy('price')
            ->get();

        return view('site.services', compact('categories', 'packages'));
    }

    /** Todos los productos, o los de una categoría (/productos/vitaminas). */
    public function products(?string $category = null): View
    {
        $categories = SiteMenu::productCategories();

        $current = $category === null
            ? null
            : ($categories->first(fn ($c) => Str::slug($c->name) === $category) ?? abort(404));

        $groups = $current ? collect([$current]) : $categories;

        $uncategorized = $current
            ? collect()
            : InventoryItem::published()->whereNull('category_id')->orderBy('name')->get();

        return view('site.products', compact('categories', 'current', 'groups', 'uncategorized'));
    }
}
