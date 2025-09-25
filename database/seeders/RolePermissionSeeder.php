<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // HR Management Permissions
            ['name' => 'view-employees', 'display_name' => 'View Employees', 'description' => 'Can view employee list and basic details', 'category' => 'hr'],
            ['name' => 'view-employee-financial', 'display_name' => 'View Employee Financial Info', 'description' => 'Can view employee salary and compensation details', 'category' => 'hr'],
            ['name' => 'create-employees', 'display_name' => 'Create Employees', 'description' => 'Can create new employees', 'category' => 'hr'],
            ['name' => 'edit-employees', 'display_name' => 'Edit Employees', 'description' => 'Can edit employee details', 'category' => 'hr'],
            ['name' => 'edit-employee-financial', 'display_name' => 'Edit Employee Financial Info', 'description' => 'Can edit employee salary and compensation', 'category' => 'hr'],
            ['name' => 'delete-employees', 'display_name' => 'Delete Employees', 'description' => 'Can delete employees', 'category' => 'hr'],

            // Attendance Management
            ['name' => 'view-attendance', 'display_name' => 'View Attendance', 'description' => 'Can view attendance records', 'category' => 'hr'],
            ['name' => 'manage-attendance', 'display_name' => 'Manage Attendance', 'description' => 'Can import and modify attendance records', 'category' => 'hr'],
            ['name' => 'manage-attendance-settings', 'display_name' => 'Manage Attendance Settings', 'description' => 'Can configure attendance settings and rules', 'category' => 'hr'],

            // Leave Management
            ['name' => 'view-leave-policies', 'display_name' => 'View Leave Policies', 'description' => 'Can view leave policies', 'category' => 'hr'],
            ['name' => 'manage-leave-policies', 'display_name' => 'Manage Leave Policies', 'description' => 'Can create and edit leave policies', 'category' => 'hr'],

            // Payroll Management
            ['name' => 'view-billable-hours', 'display_name' => 'View Billable Hours', 'description' => 'Can view billable hours', 'category' => 'hr'],
            ['name' => 'manage-billable-hours', 'display_name' => 'Manage Billable Hours', 'description' => 'Can edit billable hours', 'category' => 'hr'],
            ['name' => 'view-payroll', 'display_name' => 'View Payroll', 'description' => 'Can view payroll information', 'category' => 'hr'],
            ['name' => 'manage-payroll', 'display_name' => 'Manage Payroll', 'description' => 'Can run payroll and manage settings', 'category' => 'hr'],

            // Document Management
            ['name' => 'view-letter-templates', 'display_name' => 'View Letter Templates', 'description' => 'Can view letter templates', 'category' => 'hr'],
            ['name' => 'manage-letter-templates', 'display_name' => 'Manage Letter Templates', 'description' => 'Can create and edit letter templates', 'category' => 'hr'],

            // Asset Management
            ['name' => 'view-assets', 'display_name' => 'View Assets', 'description' => 'Can view company assets', 'category' => 'assets'],
            ['name' => 'manage-assets', 'display_name' => 'Manage Assets', 'description' => 'Can create, edit, and delete assets', 'category' => 'assets'],

            // Financial Management
            ['name' => 'view-accounting-readonly', 'display_name' => 'View Accounting (Read-only)', 'description' => 'Can view financial data without editing', 'category' => 'accounting'],
            ['name' => 'manage-expense-schedules', 'display_name' => 'Manage Expense Schedules', 'description' => 'Can create and edit expense schedules', 'category' => 'accounting'],
            ['name' => 'manage-expense-categories', 'display_name' => 'Manage Expense Categories', 'description' => 'Can manage expense categories', 'category' => 'accounting'],
            ['name' => 'manage-accounts', 'display_name' => 'Manage Accounts', 'description' => 'Can manage financial accounts', 'category' => 'accounting'],
            ['name' => 'manage-income', 'display_name' => 'Manage Income', 'description' => 'Can manage income and contracts', 'category' => 'accounting'],
            ['name' => 'view-financial-reports', 'display_name' => 'View Financial Reports', 'description' => 'Can view financial reports', 'category' => 'accounting'],

            // Administration
            ['name' => 'manage-users', 'display_name' => 'Manage Users', 'description' => 'Can create, edit, and delete users', 'category' => 'administration'],
            ['name' => 'manage-roles', 'display_name' => 'Manage Roles', 'description' => 'Can create and edit roles', 'category' => 'administration'],
            ['name' => 'manage-permissions', 'display_name' => 'Manage Permissions', 'description' => 'Can assign permissions to roles', 'category' => 'administration'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(['name' => $permissionData['name']], $permissionData);
        }

        // Create Roles
        $roles = [
            [
                'name' => 'super-admin',
                'display_name' => 'Super Administrator',
                'description' => 'Has full access to all system features',
                'permissions' => Permission::all()->pluck('name')->toArray()
            ],
            [
                'name' => 'hr-manager',
                'display_name' => 'HR Manager',
                'description' => 'Can manage all HR-related functions including financial compensation',
                'permissions' => [
                    'view-employees', 'view-employee-financial', 'create-employees', 'edit-employees', 'edit-employee-financial',
                    'view-attendance', 'manage-attendance', 'manage-attendance-settings',
                    'view-leave-policies', 'manage-leave-policies',
                    'view-billable-hours', 'manage-billable-hours', 'view-payroll', 'manage-payroll',
                    'view-letter-templates', 'manage-letter-templates',
                    'view-assets', 'manage-assets'
                ]
            ],
            [
                'name' => 'hr-assistant',
                'display_name' => 'HR Assistant',
                'description' => 'Can manage employees but cannot view or edit financial compensation',
                'permissions' => [
                    'view-employees', 'create-employees', 'edit-employees',
                    'view-attendance', 'manage-attendance',
                    'view-leave-policies',
                    'view-letter-templates', 'manage-letter-templates'
                ]
            ],
            [
                'name' => 'finance-manager',
                'display_name' => 'Finance Manager',
                'description' => 'Has full access to financial management features',
                'permissions' => [
                    'view-accounting-readonly', 'manage-expense-schedules', 'manage-expense-categories',
                    'manage-accounts', 'manage-income', 'view-financial-reports',
                    'view-employee-financial', 'edit-employee-financial', 'view-payroll', 'manage-payroll'
                ]
            ],
            [
                'name' => 'finance-readonly',
                'display_name' => 'Finance Viewer',
                'description' => 'Can only view financial information without editing',
                'permissions' => [
                    'view-accounting-readonly', 'view-financial-reports'
                ]
            ],
            [
                'name' => 'employee',
                'display_name' => 'Employee',
                'description' => 'Basic employee access with limited permissions',
                'permissions' => [
                    'view-employees'
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(['name' => $roleData['name']], $roleData);

            // Assign permissions to role
            foreach ($permissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}