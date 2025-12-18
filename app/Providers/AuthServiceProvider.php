<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Helpers\BusinessUnitHelper;

/**
 * Authorization Service Provider
 *
 * Handles authorization gates and policies for the QFlow system
 *
 * @author GitHub Copilot
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gate to check if user is Super Admin (only super admins can manage permission overrides)
        Gate::define('manage-permission-overrides', function ($user) {
            return $user->role === 'super_admin';
        });

        // Gate to check if user can manage leave records (super admins and admins)
        Gate::define('manage-leave-records', function ($user) {
            return in_array($user->role, ['super_admin', 'admin']);
        });

        // Gate to check if user can manage WFH records (super admins and admins)
        Gate::define('manage-wfh-records', function ($user) {
            return in_array($user->role, ['super_admin', 'admin']);
        });

        // Super admin bypass for all product management
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        // Gate to check if user can view employee details (super admins and admins)
        Gate::define('view-employee-details', function ($user) {
            return in_array($user->role, ['super_admin', 'admin']);
        });

        // Accounting Module Permissions

        // Gate to check if user can access accounting dashboard
        Gate::define('view-accounting-dashboard', function ($user) {
            return in_array($user->role, ['super_admin', 'admin']);
        });

        // Gate to check if user can manage expense schedules
        Gate::define('manage-expense-schedules', function ($user) {
            return in_array($user->role, ['super_admin', 'admin']);
        });

        // Gate to check if user can manage income schedules and contracts
        Gate::define('manage-income-schedules', function ($user) {
            return in_array($user->role, ['super_admin', 'admin']);
        });

        // Gate to check if user can view cash flow reports
        Gate::define('view-cash-flow-reports', function ($user) {
            return in_array($user->role, ['super_admin', 'admin']);
        });

        // Gate to check if user can export financial reports
        Gate::define('export-financial-reports', function ($user) {
            return in_array($user->role, ['super_admin', 'admin']);
        });

        // Gate to check if user can manage expense categories
        Gate::define('manage-expense-categories', function ($user) {
            return in_array($user->role, ['super_admin', 'admin']);
        });

        // Gate for read-only access to accounting (employees can view some data)
        Gate::define('view-accounting-readonly', function ($user) {
            return in_array($user->role, ['super_admin', 'admin', 'employee']);
        });

        // Gate to check if user can manage customers
        Gate::define('manage-customers', function ($user) {
            // Check new role-based system first
            if ($user->hasRole('super-admin') || $user->hasPermission('manage-customers')) {
                return true;
            }

            // Fallback to old role field for backward compatibility
            return isset($user->role) && in_array($user->role, ['super_admin', 'admin']);
        });

        // Business Unit Management Permissions

        // Gate to check if user can manage business units
        Gate::define('manage-business-units', function ($user) {
            return $user->role === 'super_admin' || $user->hasPermission('manage-business-units');
        });

        // Gate to check if user can view all business units
        Gate::define('view-all-business-units', function ($user) {
            return $user->role === 'super_admin' || $user->hasPermission('view-all-business-units');
        });

        // Gate to check if user can assign users to business units
        Gate::define('assign-users-to-business-units', function ($user) {
            return $user->role === 'super_admin' || $user->hasPermission('assign-users-to-business-units');
        });

        // Gate to check if user can manage specific business unit
        Gate::define('manage-specific-business-unit', function ($user, $businessUnitId = null) {
            if ($user->role === 'super_admin') {
                return true;
            }

            if (!$businessUnitId) {
                $businessUnitId = BusinessUnitHelper::getCurrentBusinessUnitId();
            }

            return $businessUnitId && $user->hasAccessToBusinessUnit($businessUnitId);
        });

        // Product/Department Management with BU Context
        Gate::define('manage-departments', function ($user, $businessUnitId = null) {
            if ($user->role === 'super_admin') {
                return true;
            }

            // Check if user has the permission
            if (!$user->hasPermission('manage-departments')) {
                return false;
            }

            // If no specific BU is provided, check current context
            if (!$businessUnitId) {
                $businessUnitId = BusinessUnitHelper::getCurrentBusinessUnitId();
            }

            return $businessUnitId && $user->hasAccessToBusinessUnit($businessUnitId);
        });

        // Enhanced accounting permissions with BU context
        Gate::define('view-accounting-dashboard-bu', function ($user, $businessUnitId = null) {
            if ($user->role === 'super_admin') {
                return true;
            }

            if (!in_array($user->role, ['admin', 'employee'])) {
                return false;
            }

            if (!$businessUnitId) {
                $businessUnitId = BusinessUnitHelper::getCurrentBusinessUnitId();
            }

            return $businessUnitId && $user->hasAccessToBusinessUnit($businessUnitId);
        });

        Gate::define('manage-expense-schedules-bu', function ($user, $businessUnitId = null) {
            if ($user->role === 'super_admin') {
                return true;
            }

            if (!$user->hasPermission('manage-expense-schedules')) {
                return false;
            }

            if (!$businessUnitId) {
                $businessUnitId = BusinessUnitHelper::getCurrentBusinessUnitId();
            }

            return $businessUnitId && $user->hasAccessToBusinessUnit($businessUnitId);
        });

        Gate::define('manage-income-schedules-bu', function ($user, $businessUnitId = null) {
            if ($user->role === 'super_admin') {
                return true;
            }

            if (!$user->hasPermission('manage-income-schedules')) {
                return false;
            }

            if (!$businessUnitId) {
                $businessUnitId = BusinessUnitHelper::getCurrentBusinessUnitId();
            }

            return $businessUnitId && $user->hasAccessToBusinessUnit($businessUnitId);
        });

        // Roles & Permissions Management
        Gate::define('manage-roles-permissions', function ($user) {
            // Check new role-based system first
            if ($user->hasRole('super-admin') || $user->hasPermission('manage-roles-permissions')) {
                return true;
            }

            // Fallback to old role field for backward compatibility
            return isset($user->role) && $user->role === 'super_admin';
        });

        // Sector Management Permissions
        Gate::define('manage-sectors', function ($user) {
            // Check new role-based system first
            if ($user->hasRole('super-admin') || $user->hasPermission('manage-sectors')) {
                return true;
            }

            // Fallback to old role field for backward compatibility
            return isset($user->role) && $user->role === 'super_admin';
        });

        Gate::define('view-sectors', function ($user) {
            // Check new role-based system first
            if ($user->hasRole('super-admin') || $user->hasPermission('view-sectors')) {
                return true;
            }

            // Fallback to old role field for backward compatibility
            return isset($user->role) && in_array($user->role, ['super_admin', 'admin']);
        });
    }
}
