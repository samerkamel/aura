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
            ['name' => 'manage-roles-permissions', 'display_name' => 'Manage Roles & Permissions', 'description' => 'Full access to roles and permissions management', 'category' => 'administration'],

            // Business Unit Management
            ['name' => 'manage-business-units', 'display_name' => 'Manage Business Units', 'description' => 'Can create, edit, and delete business units', 'category' => 'business-units'],
            ['name' => 'view-all-business-units', 'display_name' => 'View All Business Units', 'description' => 'Can view all business units regardless of assignment', 'category' => 'business-units'],
            ['name' => 'assign-users-to-business-units', 'display_name' => 'Assign Users to Business Units', 'description' => 'Can assign and unassign users to business units', 'category' => 'business-units'],

            // Product Management
            ['name' => 'manage-products', 'display_name' => 'Manage Products', 'description' => 'Can manage products within assigned business units', 'category' => 'products'],

            // Customer Management
            ['name' => 'manage-customers', 'display_name' => 'Manage Customers', 'description' => 'Can create, edit, and delete customers', 'category' => 'customers'],

            // Sector Management
            ['name' => 'manage-sectors', 'display_name' => 'Manage Sectors', 'description' => 'Can create, edit, and delete sectors', 'category' => 'sectors'],
            ['name' => 'view-sectors', 'display_name' => 'View Sectors', 'description' => 'Can view sector information', 'category' => 'sectors'],

            // Budget Management
            ['name' => 'view-budgets', 'display_name' => 'View Budgets', 'description' => 'Can view budget information', 'category' => 'budgets'],
            ['name' => 'create-budgets', 'display_name' => 'Create Budgets', 'description' => 'Can create new budgets', 'category' => 'budgets'],
            ['name' => 'edit-budgets', 'display_name' => 'Edit Budgets', 'description' => 'Can edit existing budgets', 'category' => 'budgets'],
            ['name' => 'delete-budgets', 'display_name' => 'Delete Budgets', 'description' => 'Can delete budgets', 'category' => 'budgets'],

            // Additional missing permissions
            ['name' => 'view-customers', 'display_name' => 'View Customers', 'description' => 'Can view customer information', 'category' => 'customers'],
            ['name' => 'view-products', 'display_name' => 'View Products', 'description' => 'Can view products within business units', 'category' => 'products'],
            ['name' => 'view-business-units', 'display_name' => 'View Business Units', 'description' => 'Can view business units', 'category' => 'business-units'],

            // Invoice Management
            ['name' => 'view-invoices', 'display_name' => 'View Invoices', 'description' => 'Can view customer invoices', 'category' => 'invoicing'],
            ['name' => 'manage-invoices', 'display_name' => 'Manage Invoices', 'description' => 'Can create, edit, and manage customer invoices', 'category' => 'invoicing'],
            ['name' => 'generate-invoices', 'display_name' => 'Generate Invoices', 'description' => 'Can generate invoices from contract payments', 'category' => 'invoicing'],
            ['name' => 'manage-invoice-sequences', 'display_name' => 'Manage Invoice Sequences', 'description' => 'Can configure invoice numbering sequences', 'category' => 'invoicing'],

            // Internal Transaction Management
            ['name' => 'view-internal-transactions', 'display_name' => 'View Internal Transactions', 'description' => 'Can view inter-business unit transactions', 'category' => 'internal-accounting'],
            ['name' => 'manage-internal-transactions', 'display_name' => 'Manage Internal Transactions', 'description' => 'Can create and edit internal transactions', 'category' => 'internal-accounting'],
            ['name' => 'approve-internal-transactions', 'display_name' => 'Approve Internal Transactions', 'description' => 'Can approve or reject internal transactions', 'category' => 'internal-accounting'],
            ['name' => 'manage-internal-sequences', 'display_name' => 'Manage Internal Sequences', 'description' => 'Can configure internal transaction numbering', 'category' => 'internal-accounting'],

            // Project Management - Access Control
            ['name' => 'view-all-projects', 'display_name' => 'View All Projects', 'description' => 'Can view all projects in the system', 'category' => 'projects'],
            ['name' => 'view-assigned-projects', 'display_name' => 'View Assigned Projects', 'description' => 'Can view only projects assigned to them', 'category' => 'projects'],

            // Project Management - CRUD
            ['name' => 'create-project', 'display_name' => 'Create Projects', 'description' => 'Can create new projects', 'category' => 'projects'],
            ['name' => 'edit-project', 'display_name' => 'Edit Projects', 'description' => 'Can edit project details', 'category' => 'projects'],
            ['name' => 'edit-assigned-project', 'display_name' => 'Edit Assigned Projects', 'description' => 'Can edit projects they are assigned to as manager', 'category' => 'projects'],
            ['name' => 'delete-project', 'display_name' => 'Delete Projects', 'description' => 'Can delete projects', 'category' => 'projects'],

            // Project Management - Finance
            ['name' => 'view-project-finance', 'display_name' => 'View Project Finance', 'description' => 'Can view project financial dashboard', 'category' => 'projects'],
            ['name' => 'view-assigned-project-finance', 'display_name' => 'View Assigned Project Finance', 'description' => 'Can view finance of projects they manage', 'category' => 'projects'],
            ['name' => 'manage-project-budgets', 'display_name' => 'Manage Project Budgets', 'description' => 'Can create and edit project budgets', 'category' => 'projects'],
            ['name' => 'manage-project-costs', 'display_name' => 'Manage Project Costs', 'description' => 'Can record and edit project costs', 'category' => 'projects'],
            ['name' => 'manage-assigned-project-costs', 'display_name' => 'Manage Assigned Project Costs', 'description' => 'Can manage costs for projects they manage', 'category' => 'projects'],
            ['name' => 'manage-project-revenues', 'display_name' => 'Manage Project Revenues', 'description' => 'Can record and edit project revenues', 'category' => 'projects'],
            ['name' => 'view-project-profitability', 'display_name' => 'View Project Profitability', 'description' => 'Can view project profitability analysis', 'category' => 'projects'],
            ['name' => 'view-assigned-project-profitability', 'display_name' => 'View Assigned Project Profitability', 'description' => 'Can view profitability for projects they manage', 'category' => 'projects'],

            // Project Management - Operations
            ['name' => 'manage-project-followups', 'display_name' => 'Manage Project Follow-ups', 'description' => 'Can log and manage project follow-ups', 'category' => 'projects'],
            ['name' => 'manage-assigned-project-followups', 'display_name' => 'Manage Assigned Project Follow-ups', 'description' => 'Can manage follow-ups for projects they are assigned to', 'category' => 'projects'],
            ['name' => 'manage-project-reports', 'display_name' => 'Manage Project Reports', 'description' => 'Can generate and manage project reports', 'category' => 'projects'],
            ['name' => 'manage-project-team', 'display_name' => 'Manage Project Team', 'description' => 'Can assign and unassign employees to projects', 'category' => 'projects'],
            ['name' => 'sync-project-jira', 'display_name' => 'Sync Project Jira', 'description' => 'Can sync projects and issues from Jira', 'category' => 'projects'],
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
                'name' => 'business-unit-manager',
                'display_name' => 'Business Unit Manager',
                'description' => 'Can manage assigned business units and their operations',
                'permissions' => [
                    'view-employees', 'create-employees', 'edit-employees',
                    'view-attendance', 'manage-attendance',
                    'view-leave-policies', 'manage-leave-policies',
                    'view-billable-hours', 'manage-billable-hours',
                    'view-assets', 'manage-assets',
                    'view-accounting-readonly', 'manage-expense-schedules', 'manage-income', 'view-financial-reports',
                    'manage-products', 'manage-customers', 'view-customers',
                    'view-budgets', 'create-budgets', 'edit-budgets', 'delete-budgets',
                    'view-invoices', 'manage-invoices', 'generate-invoices',
                    'view-internal-transactions', 'manage-internal-transactions'
                ]
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
                    'view-assets', 'manage-assets',
                    'manage-products'
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
                    'view-employee-financial', 'edit-employee-financial', 'view-payroll', 'manage-payroll',
                    'manage-products', 'manage-customers', 'view-customers',
                    'view-invoices', 'manage-invoices', 'generate-invoices', 'manage-invoice-sequences',
                    'view-internal-transactions', 'manage-internal-transactions', 'approve-internal-transactions', 'manage-internal-sequences',
                    'view-budgets', 'create-budgets', 'edit-budgets', 'delete-budgets'
                ]
            ],
            [
                'name' => 'finance-readonly',
                'display_name' => 'Finance Viewer',
                'description' => 'Can only view financial information without editing',
                'permissions' => [
                    'view-accounting-readonly', 'view-financial-reports',
                    'view-invoices', 'view-internal-transactions', 'view-budgets'
                ]
            ],
            [
                'name' => 'project-manager',
                'display_name' => 'Project Manager',
                'description' => 'Can manage all projects and their financial data, but no access to accounting or other financial modules',
                'permissions' => [
                    'view-all-projects', 'create-project', 'edit-project', 'delete-project',
                    'view-project-finance', 'manage-project-budgets', 'manage-project-costs',
                    'manage-project-revenues', 'view-project-profitability',
                    'manage-project-followups', 'manage-project-reports',
                    'manage-project-team', 'sync-project-jira',
                    'view-employees'
                ]
            ],
            [
                'name' => 'project-member',
                'display_name' => 'Project Member',
                'description' => 'Can view and contribute to projects they are assigned to',
                'permissions' => [
                    'view-assigned-projects',
                    'edit-assigned-project',
                    'view-assigned-project-finance',
                    'manage-assigned-project-costs',
                    'view-assigned-project-profitability',
                    'manage-assigned-project-followups',
                    'view-employees'
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