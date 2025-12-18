<?php

use Illuminate\Support\Facades\Route;
use Modules\Leave\Http\Controllers\LeaveController;
use Modules\Leave\Http\Controllers\LeavePolicyController;

Route::middleware(['auth', 'verified'])->group(function () {
  Route::resource('leaves', LeaveController::class)->names('leave');

  // Leave Policy Management Routes
  Route::prefix('leave-policies')->name('leave.policies.')->group(function () {
    Route::get('/', [LeavePolicyController::class, 'index'])->name('index');
    Route::put('/pto', [LeavePolicyController::class, 'updatePtoPolicy'])->name('update-pto');
    Route::put('/sick-leave', [LeavePolicyController::class, 'updateSickLeavePolicy'])->name('update-sick-leave');
    Route::put('/emergency', [LeavePolicyController::class, 'updateEmergencyLeavePolicy'])->name('update-emergency');
  });
});
