<?php

use Illuminate\Support\Facades\Route;
use Modules\AssetManager\Http\Controllers\AssetManagerController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('assetmanagers', AssetManagerController::class)->names('assetmanager');
});
