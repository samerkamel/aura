<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-auth', function () {
    return view('test-auth');
})->middleware('auth');
