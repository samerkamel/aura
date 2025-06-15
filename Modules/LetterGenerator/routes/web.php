<?php

use Illuminate\Support\Facades\Route;
use Modules\LetterGenerator\Http\Controllers\DocumentGeneratorController;
use Modules\LetterGenerator\Http\Controllers\LetterTemplateController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('letter-templates', LetterTemplateController::class)->names('letter-templates');

    // Document generation routes
    Route::prefix('employees/{employee}/documents')->name('documents.')->group(function () {
        Route::get('select-template', [DocumentGeneratorController::class, 'selectTemplate'])->name('select-template');
        Route::post('preview', [DocumentGeneratorController::class, 'preview'])->name('preview');
        Route::post('download', [DocumentGeneratorController::class, 'download'])->name('download');
    });
});
