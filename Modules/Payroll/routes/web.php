<?php

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Http\Controllers\PayrollController;
use Modules\Payroll\Http\Controllers\BillableHoursController;
use Modules\Payroll\Http\Controllers\PayrollSettingController;
use Modules\Payroll\Http\Controllers\PayrollRunController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('payrolls', PayrollController::class)->names('payroll');

    // Billable Hours Management Routes
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('billable-hours', [BillableHoursController::class, 'index'])->name('billable-hours.index');
        Route::post('billable-hours', [BillableHoursController::class, 'store'])->name('billable-hours.store');

        // Payroll Settings Routes
        Route::get('settings', [PayrollSettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [PayrollSettingController::class, 'store'])->name('settings.store');

        // Payroll Run & Review Routes
        Route::get('run-review', [PayrollRunController::class, 'review'])->name('run.review');
        Route::post('run-finalize', [PayrollRunController::class, 'finalizeAndExport'])->name('run.finalize');
        Route::post('run-finalize', [PayrollRunController::class, 'finalizeAndExport'])->name('run.finalize');
    });
});
