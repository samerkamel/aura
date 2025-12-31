<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Http\Controllers\EmployeeController;
use Modules\HR\Http\Controllers\EmployeeDocumentController;
use Modules\HR\Http\Controllers\PositionController;

Route::prefix('hr')->name('hr.')->group(function () {
  // Position routes
  Route::resource('positions', PositionController::class);
  Route::post('positions/{position}/toggle-status', [PositionController::class, 'toggleStatus'])->name('positions.toggle-status');

  Route::resource('employees', EmployeeController::class);

  // Employee import routes
  Route::get('employees-import', [EmployeeController::class, 'showImport'])->name('employees.import.show');
  Route::post('employees-import', [EmployeeController::class, 'processImport'])->name('employees.import.process');

  // Nested document routes
  Route::prefix('employees/{employee}')->name('employees.')->group(function () {
    Route::post('documents', [EmployeeDocumentController::class, 'store'])->name('documents.store');
    Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');

    // Salary history routes
    Route::post('salary', [EmployeeController::class, 'updateSalary'])->name('salary.update');

    // Off-boarding routes
    Route::get('offboarding', [EmployeeController::class, 'showOffboarding'])->name('offboarding.show');
    Route::post('offboarding', [EmployeeController::class, 'processOffboarding'])->name('offboarding.process');
  });
});
