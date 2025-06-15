<?php

namespace Modules\LetterGenerator\Tests\Unit;

use Modules\LetterGenerator\Services\PlaceholderService;
use Tests\TestCase;

/**
 * Placeholder Service Unit Test
 *
 * Tests the placeholder service functionality including placeholder
 * management and replacement logic.
 *
 * @author Dev Agent
 */
class PlaceholderServiceTest extends TestCase
{
    /**
     * Test that available placeholders are returned correctly.
     */
    public function test_get_available_placeholders_returns_array(): void
    {
        $placeholders = PlaceholderService::getAvailablePlaceholders();

        $this->assertIsArray($placeholders);
        $this->assertArrayHasKey('{{employee_name}}', $placeholders);
        $this->assertArrayHasKey('{{employee_position}}', $placeholders);
        $this->assertArrayHasKey('{{base_salary}}', $placeholders);
    }

    /**
     * Test that grouped placeholders are returned correctly.
     */
    public function test_get_grouped_placeholders_returns_grouped_array(): void
    {
        $groupedPlaceholders = PlaceholderService::getGroupedPlaceholders();

        $this->assertIsArray($groupedPlaceholders);
        $this->assertArrayHasKey('Employee Information', $groupedPlaceholders);
        $this->assertArrayHasKey('Employment Dates', $groupedPlaceholders);
        $this->assertArrayHasKey('Financial Information', $groupedPlaceholders);
        $this->assertArrayHasKey('System Information', $groupedPlaceholders);
    }
}
