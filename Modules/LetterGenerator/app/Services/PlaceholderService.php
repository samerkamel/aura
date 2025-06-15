<?php

namespace Modules\LetterGenerator\Services;

use Carbon\Carbon;
use Modules\HR\Models\Employee;

/**
 * Placeholder Service
 *
 * Manages available placeholders for letter templates and handles
 * the replacement of placeholders with actual employee data.
 *
 * @author Dev Agent
 */
class PlaceholderService
{
    /**
     * Get all available placeholders with their descriptions.
     *
     * @return array Array of placeholders with descriptions
     */
    public static function getAvailablePlaceholders(): array
    {
        return [
            '{{employee_name}}' => 'Employee full name',
            '{{employee_email}}' => 'Employee email address',
            '{{employee_position}}' => 'Employee position/job title',
            '{{start_date}}' => 'Employee start date',
            '{{base_salary}}' => 'Employee base salary',
            '{{current_date}}' => 'Current date',
            '{{current_year}}' => 'Current year',
            '{{employee_status}}' => 'Employee status (active, terminated, etc.)',
            '{{termination_date}}' => 'Employee termination date (if applicable)',
        ];
    }

    /**
     * Replace placeholders in template content with actual employee data.
     *
     * @param  string  $content  Template content with placeholders
     * @param  Employee  $employee  Employee model instance
     * @return string Content with placeholders replaced
     */
    public static function replacePlaceholders(string $content, Employee $employee): string
    {
        $replacements = [
            '{{employee_name}}' => $employee->name ?? '',
            '{{employee_email}}' => $employee->email ?? '',
            '{{employee_position}}' => $employee->position ?? '',
            '{{start_date}}' => $employee->start_date ? $employee->start_date->format('Y-m-d') : '',
            '{{base_salary}}' => $employee->base_salary ? number_format($employee->base_salary, 2) : '',
            '{{current_date}}' => Carbon::now()->format('Y-m-d'),
            '{{current_year}}' => Carbon::now()->format('Y'),
            '{{employee_status}}' => $employee->status ?? '',
            '{{termination_date}}' => $employee->termination_date ? $employee->termination_date->format('Y-m-d') : '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Get grouped placeholders for display in templates.
     *
     * @return array Grouped placeholders by category
     */
    public static function getGroupedPlaceholders(): array
    {
        return [
            'Employee Information' => [
                '{{employee_name}}' => 'Employee full name',
                '{{employee_email}}' => 'Employee email address',
                '{{employee_position}}' => 'Employee position/job title',
                '{{employee_status}}' => 'Employee status (active, terminated, etc.)',
            ],
            'Employment Dates' => [
                '{{start_date}}' => 'Employee start date',
                '{{termination_date}}' => 'Employee termination date (if applicable)',
            ],
            'Financial Information' => [
                '{{base_salary}}' => 'Employee base salary',
            ],
            'System Information' => [
                '{{current_date}}' => 'Current date',
                '{{current_year}}' => 'Current year',
            ],
        ];
    }
}
