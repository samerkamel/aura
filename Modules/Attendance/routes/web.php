<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceController;
use Modules\Attendance\Http\Controllers\AttendanceSettingsController;
use Modules\Attendance\Http\Controllers\AttendanceRuleController;
use Modules\Attendance\Http\Controllers\PublicHolidayController;
use Modules\Attendance\Http\Controllers\PermissionOverrideController;
use Modules\Attendance\Http\Controllers\AttendanceImportController;

Route::middleware(['auth', 'verified'])->group(function () {
  Route::resource('attendances', AttendanceController::class)->names('attendance');

  // Attendance Settings Routes
  Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('settings', [AttendanceSettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [AttendanceSettingsController::class, 'update'])->name('settings.update');

    // CSV Import Routes
    Route::get('import', [AttendanceImportController::class, 'create'])->name('import.create');
    Route::post('import', [AttendanceImportController::class, 'store'])->name('import.store');

    // Attendance Rules Routes
    Route::get('rules', [AttendanceRuleController::class, 'index'])->name('rules.index');
    Route::get('rules/create', [AttendanceRuleController::class, 'create'])->name('rules.create');
    Route::post('rules', [AttendanceRuleController::class, 'store'])->name('rules.store');
    Route::delete('rules/{attendanceRule}', [AttendanceRuleController::class, 'destroy'])->name('rules.destroy');

    // Public Holidays Routes
    Route::get('public-holidays', [PublicHolidayController::class, 'index'])->name('public-holidays.index');
    Route::post('public-holidays', [PublicHolidayController::class, 'store'])->name('public-holidays.store');
    Route::delete('public-holidays/{publicHoliday}', [PublicHolidayController::class, 'destroy'])->name('public-holidays.destroy');

    // Permission Override Routes (Super Admin only)
    Route::post('permission-overrides', [PermissionOverrideController::class, 'store'])->name('permission-overrides.store');
    Route::get('permission-overrides/{employeeId}', [PermissionOverrideController::class, 'getEmployeeOverrides'])->name('permission-overrides.get');
  });
});
