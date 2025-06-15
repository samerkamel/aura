<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceController;
use Modules\Attendance\Http\Controllers\WfhRecordController;

Route::middleware(['auth:web'])->prefix('v1')->group(function () {
    Route::apiResource('attendances', AttendanceController::class)->names('attendance');

    // Employee WFH records routes (nested under employee)
    Route::prefix('employees/{employee}')->group(function () {
        Route::post('wfh-records', [WfhRecordController::class, 'store'])->name('employee.wfh-records.store');
        Route::put('wfh-records/{wfhRecord}', [WfhRecordController::class, 'update'])->name('employee.wfh-records.update');
        Route::delete('wfh-records/{wfhRecord}', [WfhRecordController::class, 'destroy'])->name('employee.wfh-records.destroy');
    });
});
