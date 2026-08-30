<?php

use App\Http\Controllers\Web\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home']);
Route::get('/index.html', [SiteController::class, 'home']);
Route::redirect('/nosotros', '/nosotros.html');
Route::redirect('/servicios', '/servicios.html');
Route::redirect('/suplementos', '/suplementos.html');

Route::view('/login', 'app');
Route::view('/admin', 'app');
Route::view('/admin/{any}', 'app')->where('any', '.*');
