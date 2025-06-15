<?php

namespace Modules\AssetManager\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Employee;
use Modules\AssetManager\Models\Asset;
use App\Models\User;
use Carbon\Carbon;

/**
 * Feature Test: Employee Asset Management
 *
 * Tests the complete lifecycle of asset management from employee profiles:
 * - Creating an asset
 * - Assigning it to an employee
 * - Viewing it on their profile
 * - Un-assigning it from the profile
 *
 * @author Dev Agent
 */
class EmployeeAssetManagementTest extends TestCase
{
  use RefreshDatabase;

  private User $user;
  private Employee $employee;
  private Asset $asset;

  /**
   * Set up test environment
   */
  protected function setUp(): void
  {
    parent::setUp();

    // Create a test user with proper permissions
    $this->user = User::factory()->create([
      'email' => 'test@qflow.com',
      'name' => 'Test User'
    ]);

    // Create test employee
    $this->employee = Employee::factory()->create([
      'name' => 'John Doe',
      'email' => 'john.doe@company.com',
      'position' => 'Software Developer',
      'status' => 'active'
    ]);

    // Create test asset
    $this->asset = Asset::factory()->create([
      'name' => 'MacBook Pro 16"',
      'type' => 'laptop',
      'serial_number' => 'MBP16-2024-001',
      'status' => 'available'
    ]);
  }

  /**
   * Test that employee profile displays assigned assets correctly
   */
  public function test_employee_profile_displays_assigned_assets(): void
  {
    // Assign asset to employee
    $this->employee->assets()->attach($this->asset->id, [
      'assigned_date' => Carbon::now()->subDays(7),
      'notes' => 'Initial assignment for new hire'
    ]);

    // Update asset status
    $this->asset->update(['status' => 'assigned']);

    // Act as authenticated user and visit employee profile
    $response = $this->actingAs($this->user)
      ->get(route('hr.employees.show', $this->employee));

    // Assert page loads successfully
    $response->assertStatus(200);

    // Assert assigned assets section is visible
    $response->assertSee('Assigned Assets');
    $response->assertSee($this->asset->name);
    $response->assertSee($this->asset->serial_number);
    $response->assertSee('MacBook Pro 16"');
    $response->assertSee('MBP16-2024-001');

    // Assert asset details are displayed
    $response->assertSee('Laptop'); // Type (transformed)

    // Assert action buttons are present
    $response->assertSee('Un-assign Asset');
    $response->assertSee('View Asset Details');
  }

  /**
   * Test that employee profile shows empty state when no assets assigned
   */
  public function test_employee_profile_shows_empty_state_when_no_assets(): void
  {
    // Act as authenticated user and visit employee profile
    $response = $this->actingAs($this->user)
      ->get(route('hr.employees.show', $this->employee));

    // Assert page loads successfully
    $response->assertStatus(200);

    // Assert assigned assets section is visible
    $response->assertSee('Assigned Assets');

    // Assert empty state is displayed
    $response->assertSee('No assets assigned');
    $response->assertSee('Assign First Asset');
  }

  /**
   * Test asset assignment from employee profile
   */
  public function test_asset_assignment_from_employee_profile(): void
  {
    // Ensure asset is available
    $this->asset->update(['status' => 'available']);

    // Prepare assignment data
    $assignmentData = [
      'asset_id' => $this->asset->id,
      'assigned_date' => Carbon::now()->format('Y-m-d'),
      'notes' => 'Assigned via employee profile'
    ];

    // Act as authenticated user and assign asset
    $response = $this->actingAs($this->user)
      ->postJson(route('assetmanager.employee-assets.assign', $this->employee), $assignmentData);

    // Assert successful assignment
    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    // Assert database records are created correctly
    $this->assertDatabaseHas('asset_employee', [
      'asset_id' => $this->asset->id,
      'employee_id' => $this->employee->id,
      'assigned_date' => Carbon::now()->format('Y-m-d'),
      'returned_date' => null,
      'notes' => 'Assigned via employee profile'
    ]);

    // Assert asset status is updated
    $this->asset->refresh();
    $this->assertEquals('assigned', $this->asset->status);
  }

  /**
   * Test asset un-assignment from employee profile
   */
  public function test_asset_unassignment_from_employee_profile(): void
  {
    // First assign the asset
    $this->employee->assets()->attach($this->asset->id, [
      'assigned_date' => Carbon::now()->subDays(30),
      'notes' => 'Initial assignment'
    ]);
    $this->asset->update(['status' => 'assigned']);

    // Prepare un-assignment data
    $unassignmentData = [
      'asset_id' => $this->asset->id,
      'returned_date' => Carbon::now()->format('Y-m-d'),
      'notes' => 'Asset returned - un-assigned from employee profile'
    ];

    // Act as authenticated user and un-assign asset
    $response = $this->actingAs($this->user)
      ->postJson(route('assetmanager.employee-assets.unassign', $this->employee), $unassignmentData);

    // Assert successful un-assignment
    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    // Assert database records are updated correctly
    $this->assertDatabaseHas('asset_employee', [
      'asset_id' => $this->asset->id,
      'employee_id' => $this->employee->id,
      'returned_date' => Carbon::now()->format('Y-m-d')
    ]);

    // Check that notes were updated with return information
    $assignment = $this->asset->employees()
      ->where('employee_id', $this->employee->id)
      ->first();

    $this->assertStringContainsString('Initial assignment', $assignment->pivot->notes);
    $this->assertStringContainsString('Asset returned - un-assigned from employee profile', $assignment->pivot->notes);

    // Assert asset status is updated back to available
    $this->asset->refresh();
    $this->assertEquals('available', $this->asset->status);
  }

  /**
   * Test assignment history display on employee profile
   */
  public function test_assignment_history_display(): void
  {
    // Create multiple assignments with history
    $oldAsset = Asset::factory()->create([
      'name' => 'Old Laptop',
      'serial_number' => 'OLD-001',
      'status' => 'available'
    ]);

    // Assign and return old asset
    $this->employee->assets()->attach($oldAsset->id, [
      'assigned_date' => Carbon::now()->subDays(180),
      'returned_date' => Carbon::now()->subDays(60),
      'notes' => 'Returned for upgrade'
    ]);

    // Assign current asset
    $this->employee->assets()->attach($this->asset->id, [
      'assigned_date' => Carbon::now()->subDays(30),
      'notes' => 'New hardware assignment'
    ]);
    $this->asset->update(['status' => 'assigned']);

    // Visit employee profile
    $response = $this->actingAs($this->user)
      ->get(route('hr.employees.show', $this->employee));

    // Assert assignment history summary is shown
    $response->assertSee('Assignment History');
    $response->assertSee('This employee has been assigned 2 asset(s) in total');
    $response->assertSee('with 1 currently active assignment(s)');

    // Assert history details can be viewed
    $response->assertSee('View Full History');
  }

  /**
   * Test that only available assets appear in assignment dropdown
   */
  public function test_assignment_modal_shows_only_available_assets(): void
  {
    // Create multiple assets with different statuses
    $availableAsset = Asset::factory()->create([
      'name' => 'Available Laptop',
      'status' => 'available'
    ]);

    $assignedAsset = Asset::factory()->create([
      'name' => 'Assigned Laptop',
      'status' => 'assigned'
    ]);

    $maintenanceAsset = Asset::factory()->create([
      'name' => 'Maintenance Laptop',
      'status' => 'maintenance'
    ]);

    // Visit employee profile
    $response = $this->actingAs($this->user)
      ->get(route('hr.employees.show', $this->employee));

    // Assert only available assets are shown in the modal
    $response->assertSee('Available Laptop');
    $response->assertDontSee('Assigned Laptop');
    $response->assertDontSee('Maintenance Laptop');
  }

  /**
   * Test error handling for invalid assignment attempts
   */
  public function test_cannot_assign_already_assigned_asset(): void
  {
    // Assign asset to another employee first
    $otherEmployee = Employee::factory()->create();
    $otherEmployee->assets()->attach($this->asset->id, [
      'assigned_date' => Carbon::now()->subDays(10)
    ]);
    $this->asset->update(['status' => 'assigned']);

    // Try to assign the same asset to current employee
    $assignmentData = [
      'asset_id' => $this->asset->id,
      'assigned_date' => Carbon::now()->format('Y-m-d'),
      'notes' => 'Should fail'
    ];

    $response = $this->actingAs($this->user)
      ->postJson(route('assetmanager.employee-assets.assign', $this->employee), $assignmentData);

    // Assert assignment fails with appropriate error message
    $response->assertStatus(422);
    $response->assertJson([
      'success' => false,
      'message' => 'This asset is already assigned to another employee.'
    ]);
  }

  /**
   * Test complete lifecycle: create -> assign -> view -> unassign
   */
  public function test_complete_asset_lifecycle(): void
  {
    // Step 1: Verify asset is available initially
    $this->assertEquals('available', $this->asset->status);

    // Step 2: Assign asset to employee
    $assignmentData = [
      'asset_id' => $this->asset->id,
      'assigned_date' => Carbon::now()->format('Y-m-d'),
      'notes' => 'Full lifecycle test assignment'
    ];

    $assignResponse = $this->actingAs($this->user)
      ->postJson(route('assetmanager.employee-assets.assign', $this->employee), $assignmentData);

    $assignResponse->assertStatus(200);
    $assignResponse->assertJson(['success' => true]);

    // Step 3: Verify asset appears on employee profile
    $profileResponse = $this->actingAs($this->user)
      ->get(route('hr.employees.show', $this->employee));

    $profileResponse->assertStatus(200);
    $profileResponse->assertSee($this->asset->name);
    $profileResponse->assertSee($this->asset->serial_number);

    // Step 4: Un-assign asset from employee profile
    $unassignmentData = [
      'asset_id' => $this->asset->id,
      'returned_date' => Carbon::now()->format('Y-m-d'),
      'notes' => 'Full lifecycle test return'
    ];

    $unassignResponse = $this->actingAs($this->user)
      ->postJson(route('assetmanager.employee-assets.unassign', $this->employee), $unassignmentData);

    $unassignResponse->assertStatus(200);
    $unassignResponse->assertJson(['success' => true]);

    // Step 5: Verify asset is available again
    $this->asset->refresh();
    $this->assertEquals('available', $this->asset->status);

    // Step 6: Verify assignment history is recorded
    $this->assertDatabaseHas('asset_employee', [
      'asset_id' => $this->asset->id,
      'employee_id' => $this->employee->id,
      'returned_date' => Carbon::now()->format('Y-m-d')
    ]);
  }
}
