<?php

namespace App\Providers;

use App\Services\Erp\LiveCatalog;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Una sola lectura del catálogo del sistema por petición: la usan el
        // menú, la página y el carrito a la vez.
        $this->app->scoped(LiveCatalog::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
