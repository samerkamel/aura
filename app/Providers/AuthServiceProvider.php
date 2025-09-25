<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
    }
}
