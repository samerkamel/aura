<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\Http\Controllers\LeaveController;
use Modules\Leave\Http\Controllers\LeaveRecordController;

Route::middleware(['auth:web'])->prefix('v1')->group(function () {
    Route::apiResource('leaves', LeaveController::class)->names('leave');

    // Employee leave records routes (nested under employee)
    Route::prefix('employees/{employee}')->group(function () {
        Route::post('leave-records', [LeaveRecordController::class, 'store'])->name('employee.leave-records.store');
        Route::put('leave-records/{leaveRecord}', [LeaveRecordController::class, 'update'])->name('employee.leave-records.update');
        Route::delete('leave-records/{leaveRecord}', [LeaveRecordController::class, 'destroy'])->name('employee.leave-records.destroy');
        Route::post('leave-records/{leaveRecord}/cancel', [LeaveRecordController::class, 'cancel'])->name('employee.leave-records.cancel');
    });
});
