<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Shop\StoreCatalog;
use App\Support\SiteContentRepository;
use App\Support\SiteMenu;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Web pública.
 *
 * Inicio y Nosotros toman sus fotos y textos de "Contenido web"; Servicios y
 * Productos se arman con lo que el administrador publicó, con precio y stock
 * leídos del sistema (StoreCatalog).
 */
class SiteController extends Controller
{
    public function __construct(private readonly StoreCatalog $catalog) {}

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
        return view('site.services', [
            'categories' => $this->catalog->serviceCategories(),
            'packages' => $this->catalog->packages(),
        ]);
    }

    /** Todos los productos, o los de una categoría (/productos/vitaminas). */
    public function products(?string $category = null): View
    {
        $categories = SiteMenu::productCategories();

        $current = $category === null
            ? null
            : ($categories->first(fn ($c) => Str::slug($c->name) === $category) ?? abort(404));

        $groups = $current ? collect([$current]) : $categories;

        $uncategorized = $current ? collect() : $this->catalog->uncategorizedProducts();

        return view('site.products', compact('categories', 'current', 'groups', 'uncategorized'));
    }
}
