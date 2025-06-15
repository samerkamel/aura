<?php

namespace Modules\HR\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\HR\Models\Employee;
use Tests\TestCase;

/**
 * Employee Feature Test
 *
 * Tests the employee management functionality including creation,
 * validation, and business logic.
 *
 * @author Dev Agent
 */
class EmployeeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test data constants
     */
    private const TEST_NAME_JOHN = 'John Doe';

    private const TEST_EMAIL_JOHN = 'john.doe@example.com';

    private const TEST_NAME_JANE = 'Jane Smith';

    private const TEST_EMAIL_JANE = 'jane.smith@example.com';

    private const TEST_POSITION_DEV = 'Software Developer';

    private const TEST_POSITION_DESIGNER = 'Designer';

    private const TEST_DATE = '2024-01-15';

    private const TEST_BANK_NAME = 'Test Bank';

    private const TEST_SALARY = 75000.00;

    private const TEST_PHONE = '+1234567890';

    private const TEST_ADDRESS = '123 Main St, City, State';

    private const TEST_ACCOUNT = '1234567890';

    /**
     * Test that the employee creation page can be accessed.
     */
    public function test_can_access_employee_create_page(): void
    {
        $response = $this->get(route('hr.employees.create'));

        $response->assertStatus(200);
        $response->assertViewIs('hr::employees.create');
    }

    /**
     * Test successful employee creation with valid data.
     */
    public function test_can_create_employee_with_valid_data(): void
    {
        $employeeData = [
            'name' => self::TEST_NAME_JOHN,
            'email' => self::TEST_EMAIL_JOHN,
            'position' => self::TEST_POSITION_DEV,
            'start_date' => self::TEST_DATE,
            'base_salary' => self::TEST_SALARY,
            'contact_info' => [
                'phone' => self::TEST_PHONE,
                'address' => self::TEST_ADDRESS,
            ],
            'bank_info' => [
                'bank_name' => self::TEST_BANK_NAME,
                'account_number' => self::TEST_ACCOUNT,
            ],
        ];

        $response = $this->post(route('hr.employees.store'), $employeeData);

        $response->assertRedirect(route('hr.employees.index'));
        $response->assertSessionHas('success', 'Employee '.self::TEST_NAME_JOHN.' created successfully.');

        $this->assertDatabaseHas('employees', [
            'name' => self::TEST_NAME_JOHN,
            'email' => self::TEST_EMAIL_JOHN,
            'position' => self::TEST_POSITION_DEV,
            'base_salary' => self::TEST_SALARY,
            'status' => 'active',
        ]);

        $employee = Employee::where('email', self::TEST_EMAIL_JOHN)->first();
        $this->assertNotNull($employee);
        $this->assertEquals(self::TEST_NAME_JOHN, $employee->name);
        $this->assertEquals(['phone' => self::TEST_PHONE, 'address' => self::TEST_ADDRESS], $employee->contact_info);
    }

    /**
     * Test employee creation fails with missing required fields.
     */
    public function test_employee_creation_fails_with_missing_required_fields(): void
    {
        $response = $this->post(route('hr.employees.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'base_salary']);
        $this->assertEquals(0, Employee::count());
    }

    /**
     * Test employee creation fails with invalid email format.
     */
    public function test_employee_creation_fails_with_invalid_email(): void
    {
        $employeeData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'base_salary' => 75000.00,
        ];

        $response = $this->post(route('hr.employees.store'), $employeeData);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(0, Employee::count());
    }

    /**
     * Test employee creation fails with duplicate email.
     */
    public function test_employee_creation_fails_with_duplicate_email(): void
    {
        // Create first employee
        Employee::create([
            'name' => 'Jane Doe',
            'email' => 'test@example.com',
            'base_salary' => 60000.00,
        ]);

        // Try to create second employee with same email
        $employeeData = [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'base_salary' => 75000.00,
        ];

        $response = $this->post(route('hr.employees.store'), $employeeData);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(1, Employee::count());
    }

    /**
     * Test employee creation fails with negative salary.
     */
    public function test_employee_creation_fails_with_negative_salary(): void
    {
        $employeeData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'base_salary' => -1000.00,
        ];

        $response = $this->post(route('hr.employees.store'), $employeeData);

        $response->assertSessionHasErrors(['base_salary']);
        $this->assertEquals(0, Employee::count());
    }

    /**
     * Test employee creation with only required fields.
     */
    public function test_can_create_employee_with_only_required_fields(): void
    {
        $employeeData = [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'base_salary' => 50000.00,
        ];

        $response = $this->post(route('hr.employees.store'), $employeeData);

        $response->assertRedirect(route('hr.employees.index'));
        $response->assertSessionHas('success', 'Employee Jane Smith created successfully.');

        $this->assertDatabaseHas('employees', [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'base_salary' => 50000.00,
            'status' => 'active',
        ]);
    }

    /**
     * Test that bank_info is properly encrypted when stored.
     */
    public function test_bank_info_is_encrypted_when_stored(): void
    {
        $employeeData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'base_salary' => 60000.00,
            'bank_info' => [
                'bank_name' => 'Test Bank',
                'account_number' => '1234567890',
            ],
        ];

        $this->post(route('hr.employees.store'), $employeeData);

        $employee = Employee::where('email', 'test@example.com')->first();
        $this->assertNotNull($employee);

        // Check that bank_info is properly decrypted when accessed through the model
        $this->assertEquals(['bank_name' => 'Test Bank', 'account_number' => '1234567890'], $employee->bank_info);
    }

    /**
     * Test employee index page displays employees.
     */
    public function test_employee_index_displays_employees(): void
    {
        // Create some test employees
        Employee::create(['name' => 'John Doe', 'email' => 'john@example.com', 'base_salary' => 75000]);
        Employee::create(['name' => 'Jane Smith', 'email' => 'jane@example.com', 'base_salary' => 65000]);

        $response = $this->get(route('hr.employees.index'));

        $response->assertStatus(200);
        $response->assertViewIs('hr::employees.index');
        $response->assertSee('John Doe');
        $response->assertSee('Jane Smith');
    }

    /**
     * Test that employee show page displays employee details.
     */
    public function test_can_view_employee_details(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_NAME_JOHN,
            'email' => self::TEST_EMAIL_JOHN,
            'position' => self::TEST_POSITION_DEV,
            'base_salary' => self::TEST_SALARY,
            'contact_info' => ['phone' => self::TEST_PHONE, 'address' => self::TEST_ADDRESS],
            'bank_info' => ['bank_name' => self::TEST_BANK_NAME, 'account_number' => self::TEST_ACCOUNT],
        ]);

        $response = $this->get(route('hr.employees.show', $employee));

        $response->assertStatus(200);
        $response->assertSee($employee->name);
        $response->assertSee($employee->email);
        $response->assertSee($employee->position);
        $response->assertSee('$75,000.00');
    }

    /**
     * Test that employee edit page displays form with current values.
     */
    public function test_can_access_employee_edit_page(): void
    {
        $employee = Employee::create([
            'name' => self::TEST_NAME_JANE,
            'email' => self::TEST_EMAIL_JANE,
            'position' => self::TEST_POSITION_DESIGNER,
            'base_salary' => 65000.00,
        ]);

        $response = $this->get(route('hr.employees.edit', $employee));

        $response->assertStatus(200);
        $response->assertSee('Edit Employee');
        $response->assertSee($employee->name);
        $response->assertSee($employee->email);
    }

    /**
     * Test employee update with valid data.
     */
    public function test_can_update_employee_with_valid_data(): void
    {
        $employee = Employee::create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'base_salary' => 50000.00,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'position' => 'Senior Developer',
            'base_salary' => 80000.00,
            'start_date' => self::TEST_DATE,
            'contact_info' => [
                'phone' => '555-0123',
                'address' => '456 Updated St',
            ],
            'bank_info' => [
                'bank_name' => 'Updated Bank',
                'account_number' => '987654321',
            ],
        ];

        $response = $this->put(route('hr.employees.update', $employee), $updateData);

        $response->assertRedirect(route('hr.employees.index'));
        $response->assertSessionHas('success', 'Employee Updated Name updated successfully.');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'position' => 'Senior Developer',
            'base_salary' => 80000.00,
            'start_date' => self::TEST_DATE,
        ]);
    }

    /**
     * Test employee update fails with duplicate email.
     */
    public function test_employee_update_fails_with_duplicate_email(): void
    {
        Employee::create(['name' => 'Existing Employee', 'email' => 'existing@example.com', 'base_salary' => 60000]);
        $employeeToUpdate = Employee::create(['name' => 'Employee To Update', 'email' => 'original@example.com', 'base_salary' => 50000]);

        $response = $this->put(route('hr.employees.update', $employeeToUpdate), [
            'name' => 'Test Name',
            'email' => 'existing@example.com', // Duplicate email
            'base_salary' => 50000,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test employee deletion.
     */
    public function test_can_delete_employee(): void
    {
        $employee = Employee::create([
            'name' => 'To Be Deleted',
            'email' => 'delete@example.com',
            'base_salary' => 45000,
        ]);

        $response = $this->delete(route('hr.employees.destroy', $employee));

        $response->assertRedirect(route('hr.employees.index'));
        $response->assertSessionHas('success', 'Employee To Be Deleted deleted successfully.');

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id,
        ]);
    }
}
