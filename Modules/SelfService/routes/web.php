<?php

use Illuminate\Support\Facades\Route;
use Modules\SelfService\Http\Controllers\DashboardController;
use Modules\SelfService\Http\Controllers\LeaveRequestController;
use Modules\SelfService\Http\Controllers\WfhRequestController;
use Modules\SelfService\Http\Controllers\PermissionRequestController;
use Modules\SelfService\Http\Controllers\MyAttendanceController;
use Modules\SelfService\Http\Controllers\ApprovalController;

Route::middleware(['auth', 'verified', 'ensure.has.employee'])->prefix('self-service')->name('self-service.')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // My Attendance (read-only)
    Route::get('attendance', [MyAttendanceController::class, 'index'])->name('attendance');

    // Leave Requests
    Route::get('leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('leave-requests/create', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::post('leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::get('leave-requests/{leave_request}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
    Route::post('leave-requests/{leave_request}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');

    // WFH Requests
    Route::get('wfh-requests', [WfhRequestController::class, 'index'])->name('wfh-requests.index');
    Route::get('wfh-requests/create', [WfhRequestController::class, 'create'])->name('wfh-requests.create');
    Route::post('wfh-requests', [WfhRequestController::class, 'store'])->name('wfh-requests.store');
    Route::get('wfh-requests/{wfh_request}', [WfhRequestController::class, 'show'])->name('wfh-requests.show');
    Route::post('wfh-requests/{wfh_request}/cancel', [WfhRequestController::class, 'cancel'])->name('wfh-requests.cancel');

    // Permission Requests
    Route::get('permission-requests', [PermissionRequestController::class, 'index'])->name('permission-requests.index');
    Route::get('permission-requests/create', [PermissionRequestController::class, 'create'])->name('permission-requests.create');
    Route::post('permission-requests', [PermissionRequestController::class, 'store'])->name('permission-requests.store');
    Route::get('permission-requests/{permission_request}', [PermissionRequestController::class, 'show'])->name('permission-requests.show');
    Route::post('permission-requests/{permission_request}/cancel', [PermissionRequestController::class, 'cancel'])->name('permission-requests.cancel');

    // Approvals (for managers and admins)
    Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('approvals/{selfServiceRequest}', [ApprovalController::class, 'show'])->name('approvals.show');
    Route::post('approvals/{selfServiceRequest}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('approvals/{selfServiceRequest}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
});
