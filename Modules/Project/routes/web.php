<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\Http\Controllers\ProjectController;
use Modules\Project\Http\Controllers\ProjectReportController;

Route::prefix('projects')->name('projects.')->middleware(['web', 'auth'])->group(function () {
    // Projects list and create (static routes first)
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/create', [ProjectController::class, 'create'])->name('create');
    Route::post('/', [ProjectController::class, 'store'])->name('store');

    // Jira Sync
    Route::post('/sync-jira', [ProjectController::class, 'syncFromJira'])->name('sync-jira');

    // Reports (must come BEFORE /{project} dynamic route)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ProjectReportController::class, 'index'])->name('index');
        Route::get('/create', [ProjectReportController::class, 'create'])->name('create');
        Route::post('/generate', [ProjectReportController::class, 'generate'])->name('generate');
        Route::post('/', [ProjectReportController::class, 'store'])->name('store');
        Route::get('/{report}', [ProjectReportController::class, 'show'])->name('show');
        Route::get('/{report}/edit', [ProjectReportController::class, 'edit'])->name('edit');
        Route::put('/{report}', [ProjectReportController::class, 'update'])->name('update');
        Route::get('/{report}/pdf', [ProjectReportController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/{report}/excel', [ProjectReportController::class, 'exportExcel'])->name('export-excel');
        Route::delete('/{report}', [ProjectReportController::class, 'destroy'])->name('destroy');
    });

    // Project CRUD with dynamic {project} parameter (must come AFTER static routes)
    Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
    Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
    Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
    Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
    Route::post('/{project}/toggle-monthly-report', [ProjectController::class, 'toggleMonthlyReport'])->name('toggle-monthly-report');
});
