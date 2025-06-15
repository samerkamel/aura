<?php

use Illuminate\Support\Facades\Route;
use Modules\LetterGenerator\Http\Controllers\LetterGeneratorController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('lettergenerators', LetterGeneratorController::class)->names('lettergenerator');
});
