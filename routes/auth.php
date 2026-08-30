<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', fn () => view('auth.login'))->name('login')->middleware('guest');
