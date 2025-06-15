<?php

namespace Modules\Attendance\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Attendance\Models\PublicHoliday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * PublicHolidayTest
 *
 * Tests the public holiday functionality including creation, display, and deletion
 *
 * @author Dev Agent
 */
class PublicHolidayTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    // Test constants
    private const TEST_HOLIDAY_NAME = 'Test Holiday';
    private const CHRISTMAS_BREAK_NAME = 'Christmas Break';
    private const SINGLE_DAY_RANGE_NAME = 'Single Day Range';
    private const NEW_YEARS_DAY_NAME = 'New Year\'s Day';
    private const FIRST_HOLIDAY_NAME = 'First Holiday';
    private const EXISTING_HOLIDAY_NAME = 'Existing Holiday';
    private const NEW_RANGE_NAME = 'New Range';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test that public holidays index page is accessible
     */
    public function test_public_holidays_index_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get(route('attendance.public-holidays.index'));

        $response->assertStatus(200);
        $response->assertViewIs('attendance::public-holidays.index');
    }

    /**
     * Test that a public holiday can be successfully created
     */
    public function test_public_holiday_can_be_created(): void
    {
        $holidayData = [
            'name' => self::NEW_YEARS_DAY_NAME,
            'date' => now()->addDays(30)->format('Y-m-d')
        ];

        $response = $this->actingAs($this->user)
            ->post(route('attendance.public-holidays.store'), $holidayData);

        $response->assertRedirect(route('attendance.public-holidays.index'));
        $response->assertSessionHas('success', 'Public holiday added successfully.');

        $this->assertDatabaseHas('public_holidays', [
            'name' => self::NEW_YEARS_DAY_NAME,
            'date' => $holidayData['date']
        ]);
    }

    /**
     * Test that a public holiday can be successfully deleted
     */
    public function test_public_holiday_can_be_deleted(): void
    {
        $holiday = PublicHoliday::create([
            'name' => self::TEST_HOLIDAY_NAME,
            'date' => now()->addDays(10)->format('Y-m-d')
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('attendance.public-holidays.destroy', $holiday));

        $response->assertRedirect(route('attendance.public-holidays.index'));
        $response->assertSessionHas('success', 'Public holiday deleted successfully.');

        $this->assertDatabaseMissing('public_holidays', [
            'id' => $holiday->id,
            'name' => self::TEST_HOLIDAY_NAME
        ]);
    }

    /**
     * Test validation for required fields
     */
    public function test_public_holiday_creation_requires_name_and_date(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.public-holidays.store'), []);

        $response->assertSessionHasErrors(['name', 'date']);
    }

    /**
     * Test validation for date must be today or in the future
     */
    public function test_public_holiday_date_must_be_today_or_future(): void
    {
        $pastDate = now()->subDays(1)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('attendance.public-holidays.store'), [
                'name' => 'Past Holiday',
                'date' => $pastDate
            ]);

        $response->assertSessionHasErrors(['date']);
    }

    /**
     * Test that duplicate dates are not allowed
     */
    public function test_duplicate_holiday_dates_are_not_allowed(): void
    {
        $date = now()->addDays(15)->format('Y-m-d');

        // Create first holiday
        PublicHoliday::create([
            'name' => self::FIRST_HOLIDAY_NAME,
            'date' => $date
        ]);

        // Try to create another holiday on the same date
        $response = $this->actingAs($this->user)
            ->post(route('attendance.public-holidays.store'), [
                'name' => 'Second Holiday',
                'date' => $date
            ]);

        $response->assertSessionHasErrors(['date']);
    }

    /**
     * Test that a date range of holidays can be successfully created
     */
    public function test_public_holiday_range_can_be_created(): void
    {
        $startDate = now()->addDays(30)->format('Y-m-d');
        $endDate = now()->addDays(32)->format('Y-m-d'); // 3-day range

        $holidayData = [
            'name' => self::CHRISTMAS_BREAK_NAME,
            'is_date_range' => '1',
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $response = $this->actingAs($this->user)
            ->post(route('attendance.public-holidays.store'), $holidayData);

        $response->assertRedirect(route('attendance.public-holidays.index'));
        $response->assertSessionHas('success', 'Public holidays added successfully (3 days).');

        // Check that 3 holidays were created
        $this->assertEquals(3, PublicHoliday::where('name', self::CHRISTMAS_BREAK_NAME)->count());

        // Check specific dates
        $this->assertDatabaseHas('public_holidays', [
            'name' => self::CHRISTMAS_BREAK_NAME,
            'date' => $startDate
        ]);

        $this->assertDatabaseHas('public_holidays', [
            'name' => self::CHRISTMAS_BREAK_NAME,
            'date' => now()->addDays(31)->format('Y-m-d')
        ]);

        $this->assertDatabaseHas('public_holidays', [
            'name' => self::CHRISTMAS_BREAK_NAME,
            'date' => $endDate
        ]);
    }

    /**
     * Test that date range validation works correctly
     */
    public function test_date_range_validation(): void
    {
        // Test missing start date
        $response = $this->actingAs($this->user)
            ->post(route('attendance.public-holidays.store'), [
                'name' => self::TEST_HOLIDAY_NAME,
                'is_date_range' => '1',
                'end_date' => now()->addDays(5)->format('Y-m-d')
            ]);

        $response->assertSessionHasErrors(['start_date']);

        // Test missing end date
        $response = $this->actingAs($this->user)
            ->post(route('attendance.public-holidays.store'), [
                'name' => self::TEST_HOLIDAY_NAME,
                'is_date_range' => '1',
                'start_date' => now()->addDays(5)->format('Y-m-d')
            ]);

        $response->assertSessionHasErrors(['end_date']);

        // Test end date before start date
        $response = $this->actingAs($this->user)
            ->post(route('attendance.public-holidays.store'), [
                'name' => self::TEST_HOLIDAY_NAME,
                'is_date_range' => '1',
                'start_date' => now()->addDays(10)->format('Y-m-d'),
                'end_date' => now()->addDays(8)->format('Y-m-d')
            ]);

        $response->assertSessionHasErrors(['end_date']);
    }

    /**
     * Test that overlapping date ranges are not allowed
     */
    public function test_overlapping_date_ranges_are_not_allowed(): void
    {
        // Create existing holiday
        PublicHoliday::create([
            'name' => self::EXISTING_HOLIDAY_NAME,
            'date' => now()->addDays(15)->format('Y-m-d')
        ]);

        // Try to create a range that overlaps
        $response = $this->actingAs($this->user)
            ->post(route('attendance.public-holidays.store'), [
                'name' => self::NEW_RANGE_NAME,
                'is_date_range' => '1',
                'start_date' => now()->addDays(14)->format('Y-m-d'),
                'end_date' => now()->addDays(16)->format('Y-m-d')
            ]);

        $response->assertSessionHasErrors(['start_date']);
    }

    /**
     * Test that a single day range works correctly
     */
    public function test_single_day_range_works(): void
    {
        $singleDate = now()->addDays(20)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('attendance.public-holidays.store'), [
                'name' => self::SINGLE_DAY_RANGE_NAME,
                'is_date_range' => '1',
                'start_date' => $singleDate,
                'end_date' => $singleDate
            ]);

        $response->assertRedirect(route('attendance.public-holidays.index'));
        $response->assertSessionHas('success', 'Public holiday added successfully.');

        $this->assertDatabaseHas('public_holidays', [
            'name' => self::SINGLE_DAY_RANGE_NAME,
            'date' => $singleDate
        ]);

        $this->assertEquals(1, PublicHoliday::where('name', self::SINGLE_DAY_RANGE_NAME)->count());
    }
}
