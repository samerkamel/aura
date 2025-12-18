<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Modules\HR\Models\Employee;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
            $verticalMenuData = json_decode($verticalMenuJson);
            $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
            $horizontalMenuData = json_decode($horizontalMenuJson);

            // Filter menu based on user roles
            if (Auth::check()) {
                $user = Auth::user();
                $userRoles = $this->getUserRoles($user);

                // Filter vertical menu
                if (isset($verticalMenuData->menu)) {
                    $verticalMenuData->menu = $this->filterMenuItems($verticalMenuData->menu, $userRoles, $user);
                }
            }

            // Share all menuData to all the views
            $view->with('menuData', [$verticalMenuData, $horizontalMenuData]);
        });
    }

    /**
     * Get all roles for a user including special computed roles.
     */
    protected function getUserRoles($user): array
    {
        $roles = [];

        // Get direct roles from the database
        if (method_exists($user, 'roles')) {
            $roles = $user->roles()->pluck('name')->toArray();
        }

        // Check for legacy role attribute
        if (isset($user->role) && $user->role === 'super_admin') {
            $roles[] = 'super-admin';
        }

        // Check if user is a manager (has employees reporting to them)
        $employee = Employee::where('email', $user->email)->first();
        if ($employee) {
            $hasSubordinates = Employee::where('manager_id', $employee->id)->exists();
            if ($hasSubordinates) {
                $roles[] = 'manager';
            }
        }

        return array_unique($roles);
    }

    /**
     * Filter menu items based on user roles.
     */
    protected function filterMenuItems(array $menuItems, array $userRoles, $user): array
    {
        $filteredItems = [];

        foreach ($menuItems as $item) {
            // If no roles specified, check if it should be hidden by default
            if (!isset($item->roles)) {
                // Items without roles are hidden for non-admin users
                if (!$this->isAdminUser($userRoles)) {
                    continue;
                }
                $filteredItems[] = $item;
                continue;
            }

            // Check if user has access to this item
            if ($this->hasAccess($item->roles, $userRoles)) {
                $filteredItems[] = $item;
            }
        }

        return $filteredItems;
    }

    /**
     * Check if user has access based on item roles.
     */
    protected function hasAccess(array $itemRoles, array $userRoles): bool
    {
        // "all" means everyone has access
        if (in_array('all', $itemRoles)) {
            return true;
        }

        // Check if user has any of the required roles
        return !empty(array_intersect($itemRoles, $userRoles));
    }

    /**
     * Check if user is an admin (has any admin-level role).
     */
    protected function isAdminUser(array $userRoles): bool
    {
        $adminRoles = [
            'super-admin',
            'hr-manager',
            'hr-assistant',
            'finance-manager',
            'finance-readonly',
            'business-unit-manager'
        ];

        return !empty(array_intersect($adminRoles, $userRoles));
    }
}
