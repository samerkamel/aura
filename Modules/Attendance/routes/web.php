<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceController;
use Modules\Attendance\Http\Controllers\AttendanceSettingsController;
use Modules\Attendance\Http\Controllers\AttendanceRuleController;
use Modules\Attendance\Http\Controllers\PublicHolidayController;
use Modules\Attendance\Http\Controllers\PermissionOverrideController;
use Modules\Attendance\Http\Controllers\PermissionUsageController;
use Modules\Attendance\Http\Controllers\AttendanceImportController;
use Modules\Attendance\Http\Controllers\ManualAttendanceController;
use Modules\Attendance\Http\Controllers\MissingAttendanceController;
use Modules\Attendance\Http\Controllers\IncompleteAttendanceController;

Route::middleware(['auth', 'verified'])->group(function () {
  Route::resource('attendances', AttendanceController::class)->names('attendance');

  // Attendance Settings Routes
  Route::prefix('attendance')->name('attendance.')->group(function () {
    // Attendance Records with Filtering
    Route::get('records', [AttendanceController::class, 'records'])->name('records');

    // Attendance Summary (yearly view)
    Route::get('summary', [AttendanceController::class, 'summary'])->name('summary');
    Route::get('settings', [AttendanceSettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [AttendanceSettingsController::class, 'update'])->name('settings.update');

    // CSV Import Routes
    Route::get('import', [AttendanceImportController::class, 'create'])->name('import.create');
    Route::post('import', [AttendanceImportController::class, 'store'])->name('import.store');

    // ZKTeco Fingerprint Import Routes
    Route::get('import/zkteco', [AttendanceImportController::class, 'zktecoCreate'])->name('import.zkteco');
    Route::post('import/zkteco/preview', [AttendanceImportController::class, 'zktecoPreview'])->name('import.zkteco.preview');
    Route::post('import/zkteco/store', [AttendanceImportController::class, 'zktecoStore'])->name('import.zkteco.store');

    // Attendance Rules Routes
    Route::get('rules', [AttendanceRuleController::class, 'index'])->name('rules.index');
    Route::get('rules/create', [AttendanceRuleController::class, 'create'])->name('rules.create');
    Route::post('rules', [AttendanceRuleController::class, 'store'])->name('rules.store');
    Route::delete('rules/{attendanceRule}', [AttendanceRuleController::class, 'destroy'])->name('rules.destroy');

    // Public Holidays Routes
    Route::get('public-holidays', [PublicHolidayController::class, 'index'])->name('public-holidays.index');
    Route::post('public-holidays', [PublicHolidayController::class, 'store'])->name('public-holidays.store');
    Route::put('public-holidays/{publicHoliday}', [PublicHolidayController::class, 'update'])->name('public-holidays.update');
    Route::delete('public-holidays/{publicHoliday}', [PublicHolidayController::class, 'destroy'])->name('public-holidays.destroy');

    // Permission Override Routes (Super Admin only)
    Route::post('permission-overrides', [PermissionOverrideController::class, 'store'])->name('permission-overrides.store');
    Route::get('permission-overrides/{employeeId}', [PermissionOverrideController::class, 'getEmployeeOverrides'])->name('permission-overrides.get');

    // Permission Usage Routes (for tracking individual permission usage per date)
    Route::post('permission-usage/status', [PermissionUsageController::class, 'status'])->name('permission-usage.status');
    Route::post('permission-usage', [PermissionUsageController::class, 'store'])->name('permission-usage.store');
    Route::delete('permission-usage', [PermissionUsageController::class, 'destroy'])->name('permission-usage.destroy');

    // Manual Attendance Routes (Super Admin only)
    Route::post('manual-attendance', [ManualAttendanceController::class, 'store'])->name('manual-attendance.store');
    Route::put('manual-attendance/update', [ManualAttendanceController::class, 'update'])->name('manual-attendance.update');
    Route::delete('manual-attendance/delete', [ManualAttendanceController::class, 'destroy'])->name('manual-attendance.destroy');

    // Quick WFH Routes
    Route::post('quick-wfh', [ManualAttendanceController::class, 'storeWfh'])->name('quick-wfh.store');
    Route::delete('quick-wfh', [ManualAttendanceController::class, 'destroyWfh'])->name('quick-wfh.destroy');

    // Quick Leave Routes
    Route::post('quick-leave', [ManualAttendanceController::class, 'storeLeave'])->name('quick-leave.store');
    Route::delete('quick-leave', [ManualAttendanceController::class, 'destroyLeave'])->name('quick-leave.destroy');

    // Missing Attendance Routes
    Route::get('missing-attendance', [MissingAttendanceController::class, 'index'])->name('missing-attendance.index');
    Route::post('missing-attendance/add-holiday', [MissingAttendanceController::class, 'addHoliday'])->name('missing-attendance.add-holiday');
    Route::post('missing-attendance/set-wfh', [MissingAttendanceController::class, 'setWfhForAll'])->name('missing-attendance.set-wfh');

    // Incomplete Attendance Routes (employees with missing check-in/out)
    Route::get('incomplete-attendance', [IncompleteAttendanceController::class, 'index'])->name('incomplete-attendance.index');
    Route::post('incomplete-attendance/add-leave', [IncompleteAttendanceController::class, 'addLeave'])->name('incomplete-attendance.add-leave');
    Route::post('incomplete-attendance/add-wfh', [IncompleteAttendanceController::class, 'addWfh'])->name('incomplete-attendance.add-wfh');
    Route::post('incomplete-attendance/bulk-leave', [IncompleteAttendanceController::class, 'bulkAddLeave'])->name('incomplete-attendance.bulk-leave');
  });
});
