<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\AssetManager\Models\Asset;
use Carbon\Carbon;

/**
 * Employee Off-boarding Test
 *
 * Tests the complete off-boarding workflow including asset management
 * and final payroll calculations.
 *
 * @author Dev Agent
 */
class EmployeeOffboardingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_access_offboarding_page_for_active_employee()
    {
        $employee = Employee::factory()->create(['status' => 'active']);

        $response = $this->get(route('hr.employees.offboarding.show', $employee));

        $response->assertOk();
        $response->assertViewIs('hr::employees.offboarding');
        $response->assertViewHas('employee', $employee);
        $response->assertViewHas('payrollCalculation');
    }

    /** @test */
    public function can_process_employee_termination()
    {
        $employee = Employee::factory()->create(['status' => 'active']);

        $terminationDate = Carbon::now()->format('Y-m-d');

        $response = $this->post(route('hr.employees.offboarding.process', $employee), [
            'termination_date' => $terminationDate,
            'status' => 'terminated',
            'notes' => 'End of contract'
        ]);

        $response->assertRedirect(route('hr.employees.show', $employee));
        $response->assertSessionHas('success');

        // Check employee status updated
        $employee->refresh();
        $this->assertEquals('terminated', $employee->status);
        $this->assertEquals($terminationDate, $employee->termination_date->format('Y-m-d'));
    }

    /** @test */
    public function offboarding_validation_fails_with_missing_required_fields()
    {
        $employee = Employee::factory()->create(['status' => 'active']);

        $response = $this->post(route('hr.employees.offboarding.process', $employee), []);

        $response->assertSessionHasErrors(['termination_date', 'status']);
    }

    /** @test */
    public function final_payroll_calculation_is_accurate()
    {
        $employee = Employee::factory()->create([
            'status' => 'active',
            'base_salary' => 3000.00
        ]);

        $response = $this->get(route('hr.employees.offboarding.show', $employee));

        $response->assertOk();

        $payrollCalculation = $response->viewData('payrollCalculation');

        $this->assertEquals($employee->id, $payrollCalculation['employee_id']);
        $this->assertEquals($employee->name, $payrollCalculation['employee_name']);
        $this->assertEquals(3000.00, $payrollCalculation['base_salary']);
        $this->assertEquals(100.00, $payrollCalculation['daily_rate']); // 3000 / 30
        $this->assertArrayHasKey('working_days', $payrollCalculation);
        $this->assertArrayHasKey('pro_rated_amount', $payrollCalculation);
    }

    /** @test */
    public function complete_offboarding_workflow_end_to_end()
    {
        // Create employee with assets assigned
        $employee = Employee::factory()->create(['status' => 'active', 'base_salary' => 3000.00]);

        // Create and assign multiple assets
        $laptop = Asset::factory()->create(['name' => 'Test Laptop', 'type' => 'Laptop', 'status' => 'assigned']);
        $phone = Asset::factory()->create(['name' => 'Test Phone', 'type' => 'Phone', 'status' => 'assigned']);

        $employee->assets()->attach($laptop->id, [
            'assigned_date' => Carbon::now()->subDays(60),
            'notes' => 'Laptop for development'
        ]);
        $employee->assets()->attach($phone->id, [
            'assigned_date' => Carbon::now()->subDays(30),
            'notes' => 'Company phone'
        ]);

        // Step 1: Access off-boarding page
        $response = $this->get(route('hr.employees.offboarding.show', $employee));
        $response->assertOk();
        $response->assertSee('Test Laptop');
        $response->assertSee('Test Phone');
        $response->assertSee('$3,000.00'); // Base salary

        // Step 2: Process off-boarding
        $terminationDate = Carbon::now()->format('Y-m-d');
        $response = $this->post(route('hr.employees.offboarding.process', $employee), [
            'termination_date' => $terminationDate,
            'status' => 'terminated',
            'notes' => 'End of contract - all assets to be returned'
        ]);

        // Step 3: Verify redirect and success message
        $response->assertRedirect(route('hr.employees.show', $employee));
        $response->assertSessionHas('success');

        // Step 4: Verify employee status change
        $employee->refresh();
        $this->assertEquals('terminated', $employee->status);
        $this->assertEquals($terminationDate, $employee->termination_date->format('Y-m-d'));

        // Step 5: Verify all assets returned
        $updatedAssets = $employee->assets;
        $this->assertCount(2, $updatedAssets);

        foreach ($updatedAssets as $asset) {
            $this->assertNotNull($asset->pivot->returned_date);
            // Since returned_date is a string from pivot, compare as string
            $this->assertEquals($terminationDate, Carbon::parse($asset->pivot->returned_date)->format('Y-m-d'));
            $this->assertStringContainsString('returned', $asset->pivot->notes);
        }

        // Step 6: Verify assets are now available
        $laptop->refresh();
        $phone->refresh();
        $this->assertEquals('available', $laptop->status);
        $this->assertEquals('available', $phone->status);

        // Step 7: Verify off-boarding button no longer shows
        $response = $this->get(route('hr.employees.show', $employee));
        $response->assertOk();
        $response->assertDontSee('Process Off-boarding');
        $response->assertSee('Terminated'); // Status badge
    }
}
