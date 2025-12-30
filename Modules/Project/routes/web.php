<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\Http\Controllers\ProjectController;
use Modules\Project\Http\Controllers\ProjectDashboardController;
use Modules\Project\Http\Controllers\ProjectFinanceController;
use Modules\Project\Http\Controllers\ProjectReportController;

Route::prefix('projects')->name('projects.')->middleware(['web', 'auth'])->group(function () {
    // Projects list and create (static routes first)
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/create', [ProjectController::class, 'create'])->name('create');
    Route::post('/', [ProjectController::class, 'store'])->name('store');

    // Jira Sync
    Route::post('/sync-jira', [ProjectController::class, 'syncFromJira'])->name('sync-jira');

    // Follow-ups (must come BEFORE /{project} dynamic route)
    Route::get('/followups', [ProjectController::class, 'followups'])->name('followups');

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
    Route::get('/{project}/worklogs', [ProjectController::class, 'worklogs'])->name('worklogs');

    // Employee assignment routes
    Route::get('/{project}/employees', [ProjectController::class, 'manageEmployees'])->name('manage-employees');
    Route::post('/{project}/employees/assign', [ProjectController::class, 'assignEmployee'])->name('assign-employee');
    Route::put('/{project}/employees/update-role', [ProjectController::class, 'updateEmployeeRole'])->name('update-employee-role');
    Route::delete('/{project}/employees/unassign', [ProjectController::class, 'unassignEmployee'])->name('unassign-employee');
    Route::post('/{project}/employees/sync-worklogs', [ProjectController::class, 'syncEmployeesFromWorklogs'])->name('sync-employees-worklogs');

    // Project follow-up routes
    Route::post('/{project}/followups', [ProjectController::class, 'storeFollowup'])->name('store-followup');
    Route::get('/{project}/followups/history', [ProjectController::class, 'getProjectFollowups'])->name('get-followups');

    // Project Jira issues/tasks routes
    Route::get('/{project}/tasks', [ProjectController::class, 'tasks'])->name('tasks');
    Route::post('/{project}/tasks/sync', [ProjectController::class, 'syncIssues'])->name('sync-issues');
    Route::get('/{project}/tasks/issues', [ProjectController::class, 'getProjectIssues'])->name('get-issues');

    // Project Dashboard routes
    Route::get('/{project}/dashboard', [ProjectDashboardController::class, 'index'])->name('dashboard');
    Route::post('/{project}/dashboard/refresh-health', [ProjectDashboardController::class, 'refreshHealth'])->name('refresh-health');
    Route::get('/{project}/dashboard/health-trend', [ProjectDashboardController::class, 'healthTrend'])->name('health-trend');
    Route::get('/{project}/dashboard/activity', [ProjectDashboardController::class, 'activityFeed'])->name('activity-feed');
    Route::get('/{project}/dashboard/team', [ProjectDashboardController::class, 'teamPerformance'])->name('team-performance');

    // Project Finance routes
    Route::prefix('{project}/finance')->name('finance.')->group(function () {
        // Financial Dashboard
        Route::get('/', [ProjectFinanceController::class, 'index'])->name('index');

        // Budgets
        Route::get('/budgets', [ProjectFinanceController::class, 'budgets'])->name('budgets');
        Route::post('/budgets', [ProjectFinanceController::class, 'storeBudget'])->name('budgets.store');
        Route::put('/budgets/{budget}', [ProjectFinanceController::class, 'updateBudget'])->name('budgets.update');
        Route::delete('/budgets/{budget}', [ProjectFinanceController::class, 'destroyBudget'])->name('budgets.destroy');

        // Costs
        Route::get('/costs', [ProjectFinanceController::class, 'costs'])->name('costs');
        Route::post('/costs', [ProjectFinanceController::class, 'storeCost'])->name('costs.store');
        Route::put('/costs/{cost}', [ProjectFinanceController::class, 'updateCost'])->name('costs.update');
        Route::delete('/costs/{cost}', [ProjectFinanceController::class, 'destroyCost'])->name('costs.destroy');
        Route::post('/costs/generate-labor', [ProjectFinanceController::class, 'generateLaborCosts'])->name('costs.generate-labor');

        // Revenues
        Route::get('/revenues', [ProjectFinanceController::class, 'revenues'])->name('revenues');
        Route::post('/revenues', [ProjectFinanceController::class, 'storeRevenue'])->name('revenues.store');
        Route::put('/revenues/{revenue}', [ProjectFinanceController::class, 'updateRevenue'])->name('revenues.update');
        Route::delete('/revenues/{revenue}', [ProjectFinanceController::class, 'destroyRevenue'])->name('revenues.destroy');
        Route::post('/revenues/{revenue}/payment', [ProjectFinanceController::class, 'recordPayment'])->name('revenues.record-payment');

        // Profitability
        Route::get('/profitability', [ProjectFinanceController::class, 'profitability'])->name('profitability');

        // API endpoints
        Route::get('/api/summary', [ProjectFinanceController::class, 'apiSummary'])->name('api.summary');
        Route::get('/api/trend', [ProjectFinanceController::class, 'apiMonthlyTrend'])->name('api.trend');
    });
});
