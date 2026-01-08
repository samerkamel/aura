<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\Http\Controllers\ProjectController;
use Modules\Project\Http\Controllers\ProjectDashboardController;
use Modules\Project\Http\Controllers\ProjectFinanceController;
use Modules\Project\Http\Controllers\ProjectReportController;
use Modules\Project\Http\Controllers\ProjectPlanningController;
use Modules\Project\Http\Controllers\ProjectTemplateController;
use Modules\Project\Http\Controllers\CapacityPlanningController;
use Modules\Project\Http\Controllers\PMDashboardController;

Route::prefix('projects')->name('projects.')->middleware(['web', 'auth'])->group(function () {
    // Project Templates (must come before dynamic {project} routes)
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [ProjectTemplateController::class, 'index'])->name('index');
        Route::get('/create', [ProjectTemplateController::class, 'create'])->name('create');
        Route::post('/', [ProjectTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [ProjectTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [ProjectTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [ProjectTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [ProjectTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{template}/toggle-active', [ProjectTemplateController::class, 'toggleActive'])->name('toggle-active');
        Route::get('/{template}/create-project', [ProjectTemplateController::class, 'createProject'])->name('create-project');
        Route::post('/{template}/create-project', [ProjectTemplateController::class, 'storeProject'])->name('store-project');
    });

    // Projects list and create (static routes first)
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/create', [ProjectController::class, 'create'])->name('create');
    Route::post('/', [ProjectController::class, 'store'])->name('store');

    // Jira Sync (AJAX endpoints for batch sync)
    Route::post('/sync-jira', [ProjectController::class, 'syncFromJira'])->name('sync-jira');
    Route::post('/sync-jira/projects', [ProjectController::class, 'syncJiraGetProjects'])->name('sync-jira.projects');
    Route::post('/sync-jira/issues', [ProjectController::class, 'syncJiraProjectIssues'])->name('sync-jira.issues');
    Route::post('/sync-jira/worklogs', [ProjectController::class, 'syncJiraWorklogs'])->name('sync-jira.worklogs');

    // Follow-ups (must come BEFORE /{project} dynamic route)
    Route::get('/followups', [ProjectController::class, 'followups'])->name('followups');

    // Mass customer linking (must come BEFORE /{project} dynamic route)
    Route::get('/link-customers', [ProjectController::class, 'linkCustomers'])->name('link-customers');
    Route::post('/link-customers', [ProjectController::class, 'updateCustomerLinks'])->name('update-customer-links');

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

    // Capacity Planning (must come BEFORE /{project} dynamic route)
    Route::prefix('capacity')->name('capacity.')->group(function () {
        Route::get('/', [CapacityPlanningController::class, 'index'])->name('index');
        Route::get('/api/heatmap', [CapacityPlanningController::class, 'apiHeatmapData'])->name('api.heatmap');
    });

    // PM Dashboard (Command Center) - must come BEFORE /{project} dynamic route
    Route::prefix('pm-dashboard')->name('pm-dashboard.')->group(function () {
        Route::get('/', [PMDashboardController::class, 'index'])->name('index');
        Route::get('/calendar', [PMDashboardController::class, 'calendar'])->name('calendar');
        Route::get('/notifications', [PMDashboardController::class, 'notifications'])->name('notifications');
        Route::get('/api/notifications', [PMDashboardController::class, 'getNotifications'])->name('api.notifications');
        Route::get('/api/calendar-events', [PMDashboardController::class, 'calendar'])->name('api.calendar-events');
        Route::get('/api/data', [PMDashboardController::class, 'dashboardData'])->name('data');
        Route::post('/notifications/mark-read', [PMDashboardController::class, 'markNotificationRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [PMDashboardController::class, 'markAllNotificationsRead'])->name('notifications.mark-all-read');
        Route::post('/notifications/dismiss', [PMDashboardController::class, 'dismissNotification'])->name('notifications.dismiss');
        Route::post('/quick-followup', [PMDashboardController::class, 'quickFollowup'])->name('quick-followup');
        Route::get('/api/employee-workload/{employee}', [PMDashboardController::class, 'employeeWorkload'])->name('api.employee-workload');
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

    // Create template from project
    Route::post('/{project}/create-template', [ProjectTemplateController::class, 'createFromProject'])->name('create-template');

    // Project follow-up routes
    Route::post('/{project}/followups', [ProjectController::class, 'storeFollowup'])->name('store-followup');
    Route::get('/{project}/followups/history', [ProjectController::class, 'getProjectFollowups'])->name('get-followups');

    // Project Jira issues/tasks routes
    Route::get('/{project}/tasks', [ProjectController::class, 'tasks'])->name('tasks');
    Route::post('/{project}/tasks/sync', [ProjectController::class, 'syncIssues'])->name('sync-issues');
    Route::get('/{project}/tasks/issues', [ProjectController::class, 'getProjectIssues'])->name('get-issues');
    Route::get('/{project}/tasks/create', [ProjectController::class, 'createTask'])->name('create-task');
    Route::post('/{project}/tasks', [ProjectController::class, 'storeTask'])->name('store-task');
    Route::get('/{project}/tasks/bulk-create', [ProjectController::class, 'bulkCreateTasks'])->name('bulk-create-tasks');
    Route::post('/{project}/tasks/bulk', [ProjectController::class, 'storeBulkTasks'])->name('store-bulk-tasks');

    // Individual issue management (AJAX)
    Route::get('/{project}/tasks/{issue}/details', [ProjectController::class, 'getIssueDetails'])->name('issue-details');
    Route::get('/{project}/tasks/{issue}/transitions', [ProjectController::class, 'getIssueTransitions'])->name('issue-transitions');
    Route::post('/{project}/tasks/{issue}/update-field', [ProjectController::class, 'updateIssueField'])->name('update-issue-field');
    Route::post('/{project}/tasks/{issue}/transition', [ProjectController::class, 'transitionIssue'])->name('transition-issue');

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

        // Linked Financial Documents
        Route::get('/contracts', [ProjectFinanceController::class, 'contracts'])->name('contracts');
        Route::get('/invoices', [ProjectFinanceController::class, 'invoices'])->name('invoices');
        Route::get('/expenses', [ProjectFinanceController::class, 'expenses'])->name('expenses');

        // API endpoints
        Route::get('/api/summary', [ProjectFinanceController::class, 'apiSummary'])->name('api.summary');
        Route::get('/api/trend', [ProjectFinanceController::class, 'apiMonthlyTrend'])->name('api.trend');
    });

    // Project Planning routes
    Route::prefix('{project}/planning')->name('planning.')->group(function () {
        // Milestones
        Route::get('/milestones', [ProjectPlanningController::class, 'milestones'])->name('milestones');
        Route::post('/milestones', [ProjectPlanningController::class, 'storeMilestone'])->name('milestones.store');
        Route::put('/milestones/{milestone}', [ProjectPlanningController::class, 'updateMilestone'])->name('milestones.update');
        Route::delete('/milestones/{milestone}', [ProjectPlanningController::class, 'destroyMilestone'])->name('milestones.destroy');

        // Risks
        Route::get('/risks', [ProjectPlanningController::class, 'risks'])->name('risks');
        Route::post('/risks', [ProjectPlanningController::class, 'storeRisk'])->name('risks.store');
        Route::put('/risks/{risk}', [ProjectPlanningController::class, 'updateRisk'])->name('risks.update');
        Route::delete('/risks/{risk}', [ProjectPlanningController::class, 'destroyRisk'])->name('risks.destroy');

        // Time Estimates
        Route::get('/time-estimates', [ProjectPlanningController::class, 'timeEstimates'])->name('time-estimates');
        Route::post('/time-estimates', [ProjectPlanningController::class, 'storeTimeEstimate'])->name('time-estimates.store');
        Route::put('/time-estimates/{estimate}', [ProjectPlanningController::class, 'updateTimeEstimate'])->name('time-estimates.update');
        Route::delete('/time-estimates/{estimate}', [ProjectPlanningController::class, 'destroyTimeEstimate'])->name('time-estimates.destroy');

        // Dependencies
        Route::get('/dependencies', [ProjectPlanningController::class, 'dependencies'])->name('dependencies');
        Route::post('/dependencies', [ProjectPlanningController::class, 'storeDependency'])->name('dependencies.store');
        Route::put('/dependencies/{dependency}', [ProjectPlanningController::class, 'updateDependency'])->name('dependencies.update');
        Route::delete('/dependencies/{dependency}', [ProjectPlanningController::class, 'destroyDependency'])->name('dependencies.destroy');

        // Timeline/Gantt View
        Route::get('/timeline', [ProjectPlanningController::class, 'timeline'])->name('timeline');
    });
});
