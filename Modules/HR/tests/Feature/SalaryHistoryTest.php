<?php

namespace Modules\HR\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\HR\Models\SalaryHistory;
use Tests\TestCase;

/**
 * Salary History Test
 *
 * Tests the salary history tracking functionality, including automatic
 * creation of history records when employee salaries are updated.
 *
 * @author Dev Agent
 */
class SalaryHistoryTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Test constants
   */
  private const TEST_EMPLOYEE_NAME = 'John Doe';

  private const TEST_EMPLOYEE_EMAIL = 'john.doe@example.com';

  private const INITIAL_SALARY = 50000.00;

  private const UPDATED_SALARY = 55000.00;

  private const SALARY_CHANGE_REASON = 'Annual Performance Review';

  /**
   * Test that updating employee salary creates a salary history record.
   */
  public function test_salary_update_creates_history_record(): void
  {
    $employee = Employee::create([
      'name' => self::TEST_EMPLOYEE_NAME,
      'email' => self::TEST_EMPLOYEE_EMAIL,
      'base_salary' => self::INITIAL_SALARY,
    ]);

    // Update the salary
    $employee->update(['base_salary' => self::UPDATED_SALARY]);

    // Verify history record was created
    $this->assertDatabaseHas('salary_histories', [
      'employee_id' => $employee->id,
      'old_salary' => self::INITIAL_SALARY,
      'new_salary' => self::UPDATED_SALARY,
    ]);

    $historyRecord = SalaryHistory::where('employee_id', $employee->id)->first();
    $this->assertNotNull($historyRecord);
    $this->assertEquals(self::INITIAL_SALARY, $historyRecord->old_salary);
    $this->assertEquals(self::UPDATED_SALARY, $historyRecord->new_salary);
    $this->assertNotNull($historyRecord->change_date);
  }

  /**
   * Test that salary history includes reason when provided.
   */
  public function test_salary_history_includes_reason(): void
  {
    $employee = Employee::create([
      'name' => self::TEST_EMPLOYEE_NAME,
      'email' => self::TEST_EMPLOYEE_EMAIL,
      'base_salary' => self::INITIAL_SALARY,
    ]);

    // Set salary change reason and update
    $employee->salary_change_reason = self::SALARY_CHANGE_REASON;
    $employee->update(['base_salary' => self::UPDATED_SALARY]);

    // Verify history record includes reason
    $this->assertDatabaseHas('salary_histories', [
      'employee_id' => $employee->id,
      'old_salary' => self::INITIAL_SALARY,
      'new_salary' => self::UPDATED_SALARY,
      'reason' => self::SALARY_CHANGE_REASON,
    ]);
  }

  /**
   * Test that no history record is created when salary doesn't change.
   */
  public function test_no_history_created_when_salary_unchanged(): void
  {
    $employee = Employee::create([
      'name' => self::TEST_EMPLOYEE_NAME,
      'email' => self::TEST_EMPLOYEE_EMAIL,
      'base_salary' => self::INITIAL_SALARY,
    ]);

    // Update other fields but not salary
    $employee->update(['name' => 'John Smith']);

    // Verify no history record was created
    $this->assertCount(0, SalaryHistory::where('employee_id', $employee->id)->get());
  }

  /**
   * Test that no history record is created for new employees.
   */
  public function test_no_history_created_for_new_employee(): void
  {
    Employee::create([
      'name' => self::TEST_EMPLOYEE_NAME,
      'email' => self::TEST_EMPLOYEE_EMAIL,
      'base_salary' => self::INITIAL_SALARY,
    ]);

    // Verify no history record was created
    $this->assertCount(0, SalaryHistory::all());
  }

  /**
   * Test salary change calculation in model.
   */
  public function test_salary_change_calculation(): void
  {
    $history = new SalaryHistory([
      'old_salary' => self::INITIAL_SALARY,
      'new_salary' => self::UPDATED_SALARY,
    ]);

    $expectedChange = self::UPDATED_SALARY - self::INITIAL_SALARY;
    $this->assertEquals($expectedChange, $history->salary_change);
    $this->assertEquals('+' . number_format($expectedChange, 2), $history->formatted_salary_change);
  }

  /**
   * Test salary history display on employee profile.
   */
  public function test_salary_history_displays_on_employee_profile(): void
  {
    $employee = Employee::create([
      'name' => self::TEST_EMPLOYEE_NAME,
      'email' => self::TEST_EMPLOYEE_EMAIL,
      'base_salary' => self::INITIAL_SALARY,
    ]);

    // Create salary history
    $employee->salary_change_reason = self::SALARY_CHANGE_REASON;
    $employee->update(['base_salary' => self::UPDATED_SALARY]);

    // Visit employee profile
    $response = $this->get(route('hr.employees.show', $employee));

    $response->assertStatus(200);
    $response->assertSee('Salary History');
    $response->assertSee(number_format(self::INITIAL_SALARY, 2));
    $response->assertSee(number_format(self::UPDATED_SALARY, 2));
    $response->assertSee(self::SALARY_CHANGE_REASON);
  }

  /**
   * Test employee salary update via controller includes reason.
   */
  public function test_employee_update_via_controller_creates_history(): void
  {
    $employee = Employee::create([
      'name' => self::TEST_EMPLOYEE_NAME,
      'email' => self::TEST_EMPLOYEE_EMAIL,
      'base_salary' => self::INITIAL_SALARY,
    ]);

    $updateData = [
      'name' => $employee->name,
      'email' => $employee->email,
      'base_salary' => self::UPDATED_SALARY,
      'salary_change_reason' => self::SALARY_CHANGE_REASON,
    ];

    $response = $this->put(route('hr.employees.update', $employee), $updateData);

    $response->assertRedirect(route('hr.employees.index'));
    $response->assertSessionHas('success');

    // Verify history record was created
    $this->assertDatabaseHas('salary_histories', [
      'employee_id' => $employee->id,
      'old_salary' => self::INITIAL_SALARY,
      'new_salary' => self::UPDATED_SALARY,
      'reason' => self::SALARY_CHANGE_REASON,
    ]);
  }

  /**
   * Test multiple salary changes create multiple history records.
   */
  public function test_multiple_salary_changes_create_multiple_records(): void
  {
    $employee = Employee::create([
      'name' => self::TEST_EMPLOYEE_NAME,
      'email' => self::TEST_EMPLOYEE_EMAIL,
      'base_salary' => self::INITIAL_SALARY,
    ]);

    // First salary change
    $employee->salary_change_reason = 'Mid-year adjustment';
    $employee->update(['base_salary' => 52000.00]);

    // Second salary change
    $employee->salary_change_reason = 'Annual increase';
    $employee->update(['base_salary' => self::UPDATED_SALARY]);

    // Verify both history records exist
    $historyRecords = SalaryHistory::where('employee_id', $employee->id)->get();
    $this->assertCount(2, $historyRecords);

    // Verify the records are in correct order
    $firstChange = $historyRecords->sortBy('change_date')->first();
    $secondChange = $historyRecords->sortBy('change_date')->last();

    $this->assertEquals(self::INITIAL_SALARY, $firstChange->old_salary);
    $this->assertEquals(52000.00, $firstChange->new_salary);
    $this->assertEquals('Mid-year adjustment', $firstChange->reason);

    $this->assertEquals(52000.00, $secondChange->old_salary);
    $this->assertEquals(self::UPDATED_SALARY, $secondChange->new_salary);
    $this->assertEquals('Annual increase', $secondChange->reason);
  }
}
