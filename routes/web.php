<?php

use App\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('web');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/{any}', [DashboardController::class, 'index'])->where('any', 'admin/.*');
});

require __DIR__ . '/auth.php';
