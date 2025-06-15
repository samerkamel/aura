<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Http\Controllers\EmployeeController;
use Modules\HR\Http\Controllers\EmployeeDocumentController;

Route::prefix('hr')->name('hr.')->group(function () {
  Route::resource('employees', EmployeeController::class);

  // Nested document routes
  Route::prefix('employees/{employee}')->name('employees.')->group(function () {
    Route::post('documents', [EmployeeDocumentController::class, 'store'])->name('documents.store');
    Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');

    // Off-boarding routes
    Route::get('offboarding', [EmployeeController::class, 'showOffboarding'])->name('offboarding.show');
    Route::post('offboarding', [EmployeeController::class, 'processOffboarding'])->name('offboarding.process');
  });
});
