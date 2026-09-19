<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'customer.verified' => \App\Http\Middleware\EnsureCustomerEmailIsVerified::class,
        ]);

        // Tienda web (guard `customer`). El ERP usa tokens de Sanctum y recibe
        // un 401 en JSON, así que estas redirecciones no le afectan.
        $middleware->redirectGuestsTo(fn (Request $request) => route('shop.login'));
        $middleware->redirectUsersTo(fn () => route('shop.account.orders'));
        $middleware->web(append: [\App\Http\Middleware\TurboFormRedirects::class]);

        // Izipay publica el resultado desde su dominio: no puede enviar el token
        // CSRF. Ambas rutas validan en su lugar la firma HMAC de la pasarela.
        $middleware->validateCsrfTokens(except: [
            'checkout/resultado',
            'pagos/izipay/notificacion',
            'deploy/optimize',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
