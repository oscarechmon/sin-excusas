<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashSessionController;
use App\Http\Controllers\Api\CatalogImageController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientPackageController;
use App\Http\Controllers\Api\CommissionController;
use App\Http\Controllers\Api\CommissionRuleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\InventoryCategoryController;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\OnlineOrderController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PublicationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\ServiceCategoryController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SiteContentController;
use App\Http\Controllers\Api\StoreSettingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autorización
|--------------------------------------------------------------------------
| Cada grupo declara el permiso que exige mediante el middleware
| `permission:` de spatie/laravel-permission. Los nombres provienen del enum
| PermissionName, de modo que un permiso se define una sola vez y las rutas
| son el único lugar donde se decide quién entra a cada módulo (§29).
*/

// El login es el único endpoint público; con rate limiting por tratarse de un
// endpoint sensible (§30).
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', DashboardController::class);

    // ---------------------------------------------------------------- Clientes
    Route::middleware('permission:clients.view')->group(function () {
        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/{client}', [ClientController::class, 'show']);
    });
    Route::post('/clients', [ClientController::class, 'store'])->middleware('permission:clients.create');
    Route::match(['put', 'patch'], '/clients/{client}', [ClientController::class, 'update'])
        ->middleware('permission:clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])
        ->middleware('permission:clients.delete');

    // ------------------------------------------------------------------ Agenda
    Route::middleware('permission:appointments.view')->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index']);
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
    });
    Route::post('/appointments', [AppointmentController::class, 'store'])
        ->middleware('permission:appointments.create');
    Route::match(['put', 'patch'], '/appointments/{appointment}', [AppointmentController::class, 'update'])
        ->middleware('permission:appointments.update');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])
        ->middleware('permission:appointments.delete');

    // --------------------------------------------------- Servicios y categorías
    Route::middleware('permission:services.view')->group(function () {
        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/service-categories', [ServiceCategoryController::class, 'index']);
        Route::get('/services/{service}/supplies', [AttendanceController::class, 'suppliesForService']);
    });
    Route::middleware('permission:services.manage')->group(function () {
        Route::post('/services', [ServiceController::class, 'store']);
        Route::match(['put', 'patch'], '/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
        Route::patch('/services/{service}/publish', [PublicationController::class, 'service']);
        Route::post('/services/{service}/image', [CatalogImageController::class, 'storeService']);
        Route::delete('/services/{service}/image', [CatalogImageController::class, 'destroyService']);

        Route::post('/service-categories', [ServiceCategoryController::class, 'store']);
        Route::match(['put', 'patch'], '/service-categories/{service_category}', [ServiceCategoryController::class, 'update']);
        Route::delete('/service-categories/{service_category}', [ServiceCategoryController::class, 'destroy']);
    });

    // ---------------------------------------------------------------- Personal
    Route::middleware('permission:employees.view')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
    });
    Route::middleware('permission:employees.manage')->group(function () {
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::match(['put', 'patch'], '/employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);
    });

    // -------------------------------------------------------------- Inventario
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('/inventory-items', [InventoryItemController::class, 'index']);
        Route::get('/inventory-items/{inventory_item}', [InventoryItemController::class, 'show']);
        Route::get('/inventory-items/{inventory_item}/movements', [InventoryItemController::class, 'movements']);
        Route::get('/inventory-categories', [InventoryCategoryController::class, 'index']);
    });
    Route::middleware('permission:inventory.manage')->group(function () {
        Route::post('/inventory-items', [InventoryItemController::class, 'store']);
        Route::match(['put', 'patch'], '/inventory-items/{inventory_item}', [InventoryItemController::class, 'update']);
        Route::delete('/inventory-items/{inventory_item}', [InventoryItemController::class, 'destroy']);
        Route::patch('/inventory-items/{inventory_item}/publish', [PublicationController::class, 'inventoryItem']);
        Route::post('/inventory-items/{inventory_item}/image', [CatalogImageController::class, 'storeInventoryItem']);
        Route::delete('/inventory-items/{inventory_item}/image', [CatalogImageController::class, 'destroyInventoryItem']);

        Route::post('/inventory-categories', [InventoryCategoryController::class, 'store']);
        Route::match(['put', 'patch'], '/inventory-categories/{inventory_category}', [InventoryCategoryController::class, 'update']);
        Route::delete('/inventory-categories/{inventory_category}', [InventoryCategoryController::class, 'destroy']);
    });
    // El ajuste de stock lleva su propio permiso: puede encubrir un faltante.
    Route::post('/inventory-items/{inventory_item}/adjust', [InventoryItemController::class, 'adjust'])
        ->middleware('permission:inventory.adjust');

    // ---------------------------------------------------------------- Paquetes
    Route::middleware('permission:packages.view')->group(function () {
        Route::get('/packages', [PackageController::class, 'index']);
        Route::get('/packages/{package}', [PackageController::class, 'show']);
        Route::get('/client-packages', [ClientPackageController::class, 'index']);
        Route::get('/client-packages/{client_package}', [ClientPackageController::class, 'show']);
    });
    Route::middleware('permission:packages.manage')->group(function () {
        Route::post('/packages', [PackageController::class, 'store']);
        Route::match(['put', 'patch'], '/packages/{package}', [PackageController::class, 'update']);
        Route::delete('/packages/{package}', [PackageController::class, 'destroy']);
        Route::patch('/packages/{package}/publish', [PublicationController::class, 'package']);
    });
    Route::post('/packages/sell', [PackageController::class, 'sell'])->middleware('permission:packages.sell');

    // -------------------------------------------------------------- Atenciones
    Route::middleware('permission:attendances.view')->group(function () {
        Route::get('/attendances', [AttendanceController::class, 'index']);
        Route::get('/attendances/{attendance}', [AttendanceController::class, 'show']);
    });
    Route::post('/attendances', [AttendanceController::class, 'store'])
        ->middleware('permission:attendances.create');

    // ------------------------------------------------------------------ Ventas
    Route::middleware('permission:sales.view')->group(function () {
        Route::get('/sales', [SaleController::class, 'index']);
        Route::get('/sales/{sale}', [SaleController::class, 'show']);
    });
    Route::middleware('permission:sales.create')->group(function () {
        Route::post('/sales', [SaleController::class, 'store']);
        Route::post('/sales/{sale}/payments', [SaleController::class, 'addPayment']);
    });
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->middleware('permission:sales.cancel');

    // ----------------------------------------------------------- Ventas online
    Route::middleware('permission:online_sales.view')->group(function () {
        Route::get('/online-orders', [OnlineOrderController::class, 'index']);
        Route::get('/online-orders/{online_order}', [OnlineOrderController::class, 'show']);
        Route::get('/store-settings', [StoreSettingController::class, 'show']);
    });
    Route::post('/online-orders/{online_order}/status', [OnlineOrderController::class, 'updateStatus'])
        ->middleware('permission:online_sales.manage');
    // El costo de delivery cambia lo que se cobra: es configuración, no operación.
    Route::put('/store-settings', [StoreSettingController::class, 'update'])->middleware('permission:settings.manage');

    // -------------------------------------------------------------------- Caja
    Route::middleware('permission:cash.view')->group(function () {
        Route::get('/cash-sessions', [CashSessionController::class, 'index']);
        Route::get('/cash-sessions/current', [CashSessionController::class, 'current']);
        Route::get('/cash-sessions/{cash_session}', [CashSessionController::class, 'show']);
    });
    Route::post('/cash-sessions/open', [CashSessionController::class, 'open'])->middleware('permission:cash.open');
    Route::post('/cash-sessions/close', [CashSessionController::class, 'close'])->middleware('permission:cash.close');
    Route::post('/cash-sessions/expenses', [CashSessionController::class, 'registerExpense'])
        ->middleware('permission:cash.expense');

    // -------------------------------------------------------------- Comisiones
    Route::middleware('permission:commissions.view')->group(function () {
        Route::get('/commissions', [CommissionController::class, 'index']);
        Route::get('/commission-rules', [CommissionRuleController::class, 'index']);
    });
    Route::middleware('permission:commissions.manage')->group(function () {
        Route::post('/commission-rules', [CommissionRuleController::class, 'store']);
        Route::match(['put', 'patch'], '/commission-rules/{commission_rule}', [CommissionRuleController::class, 'update']);
        Route::delete('/commission-rules/{commission_rule}', [CommissionRuleController::class, 'destroy']);
    });
    Route::post('/commissions/pay', [CommissionController::class, 'pay'])->middleware('permission:commissions.pay');

    // ---------------------------------------------------------------- Reportes
    Route::middleware('permission:reports.view')->prefix('reports')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales']);
        Route::get('/cash', [ReportController::class, 'cash']);
        Route::get('/commissions', [ReportController::class, 'commissions']);
        Route::get('/stock', [ReportController::class, 'stock']);
    });

    // ------------------------------------------------------ Usuarios y roles
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::get('/roles', [RoleController::class, 'index']);
    });
    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/users', [UserController::class, 'store']);
        Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/{user}/revoke-sessions', [UserController::class, 'revokeSessions']);
        Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
    });

    // ------------------------------------------------------- Contenido web
    // Fotos y textos de la web pública: es configuración del sitio.
    Route::middleware("permission:settings.manage")->group(function () {
        Route::get("/site-contents", [SiteContentController::class, "index"]);
        Route::match(["put", "patch"], "/site-contents/{key}", [SiteContentController::class, "update"]);
        Route::post("/site-contents/{key}/image", [SiteContentController::class, "storeImage"]);
        Route::delete("/site-contents/{key}/image", [SiteContentController::class, "destroyImage"]);
    });

    // --------------------------------------------------------- Métodos de pago
    // Cualquiera que pueda vender necesita leerlos para elegir uno al cobrar.
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::middleware('permission:settings.manage')->group(function () {
        Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
        Route::match(['put', 'patch'], '/payment-methods/{payment_method}', [PaymentMethodController::class, 'update']);
        Route::delete('/payment-methods/{payment_method}', [PaymentMethodController::class, 'destroy']);
    });
});
