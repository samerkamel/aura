<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\HR\Models\Employee;

/**
 * Employee Seeder
 *
 * Seeds the database with sample employee data for testing purposes
 *
 * @author GitHub Copilot
 */
class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample employees for testing
        Employee::factory()->create([
            'name' => 'John Smith',
            'email' => 'john.smith@qflow.test',
            'position' => 'Software Developer',
            'base_salary' => 75000.00,
            'status' => 'active',
        ]);

        Employee::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@qflow.test',
            'position' => 'Project Manager',
            'base_salary' => 85000.00,
            'status' => 'active',
        ]);

        Employee::factory()->create([
            'name' => 'Mike Johnson',
            'email' => 'mike.johnson@qflow.test',
            'position' => 'Designer',
            'base_salary' => 65000.00,
            'status' => 'active',
        ]);

        Employee::factory()->create([
            'name' => 'Sarah Wilson',
            'email' => 'sarah.wilson@qflow.test',
            'position' => 'HR Manager',
            'base_salary' => 80000.00,
            'status' => 'terminated',
        ]);

        // Create additional random employees
        Employee::factory(6)->create();

        echo "Employee seeder completed. Created 10 employees total.\n";
    }
}
