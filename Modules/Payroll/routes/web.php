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
        Route::post('billable-hours/sync-jira', [BillableHoursController::class, 'syncJira'])->name('billable-hours.sync-jira');

        // Jira Worklog Import Routes
        Route::get('billable-hours/jira-worklogs', [BillableHoursController::class, 'jiraWorklogs'])->name('billable-hours.jira-worklogs');
        Route::get('billable-hours/import-jira-worklogs', [BillableHoursController::class, 'importJiraWorklogsForm'])->name('billable-hours.import-jira-worklogs');
        Route::post('billable-hours/import-jira-worklogs', [BillableHoursController::class, 'importJiraWorklogs'])->name('billable-hours.import-jira-worklogs.store');

        // Jira User Mapping Routes
        Route::get('billable-hours/jira-user-mapping', [BillableHoursController::class, 'jiraUserMapping'])->name('billable-hours.jira-user-mapping');
        Route::post('billable-hours/jira-user-mapping', [BillableHoursController::class, 'saveJiraUserMapping'])->name('billable-hours.jira-user-mapping.save');

        // Manual Jira Sync
        Route::post('billable-hours/manual-jira-sync', [BillableHoursController::class, 'manualJiraSync'])->name('billable-hours.manual-jira-sync');

        // Debug Jira Sync
        Route::get('billable-hours/debug-jira-sync', [BillableHoursController::class, 'debugJiraSync'])->name('billable-hours.debug-jira-sync');

        // Payroll Settings Routes
        Route::get('settings', [PayrollSettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [PayrollSettingController::class, 'store'])->name('settings.store');
        Route::post('settings/period', [PayrollSettingController::class, 'storePeriodSettings'])->name('settings.period.store');
        Route::post('settings/labor-cost-multiplier', [PayrollSettingController::class, 'storeLaborCostMultiplier'])->name('settings.labor-cost-multiplier.store');
        Route::post('settings/jira', [PayrollSettingController::class, 'storeJiraSettings'])->name('settings.jira.store');
        Route::post('settings/jira/test', [PayrollSettingController::class, 'testJiraConnection'])->name('settings.jira.test');

        // Payroll Run & Review Routes
        Route::get('run-review', [PayrollRunController::class, 'review'])->name('run.review');
        Route::post('run-finalize', [PayrollRunController::class, 'finalizeAndExport'])->name('run.finalize');

        // Payroll Adjustments Routes
        Route::get('run-adjustments', [PayrollRunController::class, 'adjustments'])->name('run.adjustments');
        Route::post('run-adjustments', [PayrollRunController::class, 'saveAdjustments'])->name('run.adjustments.save');
        Route::post('run-finalize-adjusted', [PayrollRunController::class, 'finalizeFromAdjustments'])->name('run.finalize-adjusted');
        Route::post('run-recalculate', [PayrollRunController::class, 'recalculate'])->name('run.recalculate');
    });
});
