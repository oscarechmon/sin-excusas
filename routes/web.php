<?php

use App\Http\Controllers\Web\DeployController;
use App\Http\Controllers\Web\Shop\AccountController;
use App\Http\Controllers\Web\Shop\CartController;
use App\Http\Controllers\Web\Shop\CheckoutController;
use App\Http\Controllers\Web\Shop\CustomerAuthController;
use App\Http\Controllers\Web\Shop\EmailVerificationController;
use App\Http\Controllers\Web\Shop\GoogleAuthController;
use App\Http\Controllers\Web\Shop\IzipayNotificationController;
use App\Http\Controllers\Web\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web pública
|--------------------------------------------------------------------------
| Páginas Blade con URLs limpias. Servicios y productos se leen de la base
| de datos y solo muestran lo publicado desde el ERP.
*/
Route::controller(SiteController::class)->name('site.')->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/nosotros', 'about')->name('about');
    Route::get('/servicios', 'services')->name('services');
    Route::get('/productos/{category?}', 'products')->name('products');
});

// URLs antiguas: 301 para no romper enlaces ni perder posicionamiento.
Route::permanentRedirect('/index.html', '/');
Route::permanentRedirect('/nosotros.html', '/nosotros');
Route::permanentRedirect('/servicios.html', '/servicios');
Route::permanentRedirect('/suplementos.html', '/productos');
Route::permanentRedirect('/suplementos', '/productos');
Route::permanentRedirect('/catalogo', '/servicios');

/*
|--------------------------------------------------------------------------
| Tienda online
|--------------------------------------------------------------------------
*/
Route::controller(CartController::class)->prefix('carrito')->name('shop.cart.')->group(function () {
    Route::get('/', 'show')->name('show');
    Route::post('/', 'add')->name('add');
    Route::patch('/{key}', 'update')->name('update');
    Route::delete('/{key}', 'remove')->name('remove');
});

Route::middleware('guest:customer')->group(function () {
    Route::controller(CustomerAuthController::class)->group(function () {
        Route::get('/cuenta/ingresar', 'showLogin')->name('shop.login');
        Route::post('/cuenta/ingresar', 'login')->middleware('throttle:10,1');
        Route::get('/cuenta/registro', 'showRegister')->name('shop.register');
        Route::post('/cuenta/registro', 'register')->middleware('throttle:5,1');
    });

    Route::get('/cuenta/google', [GoogleAuthController::class, 'redirect'])->name('shop.google.redirect');
    Route::get('/cuenta/google/callback', [GoogleAuthController::class, 'callback'])->name('shop.google.callback');
});

Route::middleware('auth:customer')->group(function () {
    Route::post('/cuenta/salir', [CustomerAuthController::class, 'logout'])->name('shop.logout');

    // Verificación del correo (una sola vez, al registrarse con correo).
    Route::controller(EmailVerificationController::class)->prefix('cuenta/verificar')->group(function () {
        Route::get('/', 'show')->name('shop.verification.notice');
        Route::post('/', 'verify')->middleware('throttle:10,1')->name('shop.verification.verify');
        Route::post('/reenviar', 'resend')->middleware('throttle:5,1')->name('shop.verification.resend');
    });
});

Route::middleware(['auth:customer', 'customer.verified'])->group(function () {
    Route::get('/cuenta/pedidos', [AccountController::class, 'orders'])->name('shop.account.orders');
    Route::get('/cuenta/pedidos/{code}', [AccountController::class, 'order'])->name('shop.account.order');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('shop.checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('shop.checkout.store');
    Route::get('/checkout/pagar/{code}', [CheckoutController::class, 'pay'])->name('shop.checkout.pay');
});

// Respuestas de la pasarela: validan la firma HMAC en lugar de sesión y CSRF.
Route::post('/checkout/resultado', [CheckoutController::class, 'return'])->name('shop.checkout.return');
Route::post('/pagos/izipay/notificacion', IzipayNotificationController::class)
    ->middleware('throttle:60,1')
    ->name('shop.izipay.notification');

// Gancho de despliegue: lo llama GitHub Actions tras subir por FTP. Se valida
// con el token de DEPLOY_TOKEN, no con sesión ni CSRF.
Route::post('/deploy/optimize', DeployController::class)->middleware('throttle:6,1')->name('deploy.optimize');

// ERP (SPA en Vue).
Route::view('/login', 'app');
Route::view('/admin', 'app');
Route::view('/admin/{any}', 'app')->where('any', '.*');
