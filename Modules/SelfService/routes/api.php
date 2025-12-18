<?php

use Illuminate\Support\Facades\Route;
use Modules\SelfService\Http\Controllers\SelfServiceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('selfservices', SelfServiceController::class)->names('selfservice');
});
