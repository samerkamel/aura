<?php

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Models\PublicHoliday;
use Modules\Attendance\Models\Setting;
use Illuminate\Support\Collection;

/**
 * WorkingDaysService
 *
 * Centralized service for calculating working days excluding weekends and public holidays.
 * Used across Leave, SelfService, and Payroll modules for consistent day counting.
 *
 * @author Dev Agent
 */
class WorkingDaysService
{
    /**
     * Day name to Carbon day number mapping
     */
    protected array $dayNameToNumber = [
        'sunday' => 0,
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
    ];

    /**
     * Calculate the number of working days between two dates.
     * Excludes weekends and public holidays.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    public function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $weekendDayNumbers = $this->getWeekendDayNumbers();
        $holidays = $this->getHolidayDates($startDate, $endDate);

        $workingDays = 0;
        $currentDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->startOfDay();

        while ($currentDate->lte($endDate)) {
            $isWeekend = in_array($currentDate->dayOfWeek, $weekendDayNumbers);
            $isHoliday = $holidays->contains($currentDate->format('Y-m-d'));

            if (!$isWeekend && !$isHoliday) {
                $workingDays++;
            }

            $currentDate->addDay();
        }

        return $workingDays;
    }

    /**
     * Check if a specific date is a working day.
     *
     * @param Carbon $date
     * @return bool
     */
    public function isWorkingDay(Carbon $date): bool
    {
        $weekendDayNumbers = $this->getWeekendDayNumbers();
        $isWeekend = in_array($date->dayOfWeek, $weekendDayNumbers);

        if ($isWeekend) {
            return false;
        }

        $isHoliday = PublicHoliday::whereDate('date', $date->format('Y-m-d'))->exists();

        return !$isHoliday;
    }

    /**
     * Get the configured weekend day numbers.
     *
     * @return array
     */
    public function getWeekendDayNumbers(): array
    {
        $weekendDays = Setting::get('weekend_days', ['friday', 'saturday']);

        $dayNumbers = array_map(function ($day) {
            return $this->dayNameToNumber[strtolower($day)] ?? null;
        }, $weekendDays);

        return array_filter($dayNumbers, fn($d) => $d !== null);
    }

    /**
     * Get public holiday dates within a date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    protected function getHolidayDates(Carbon $startDate, Carbon $endDate): Collection
    {
        return PublicHoliday::whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'));
    }

    /**
     * Get the next working day from a given date.
     *
     * @param Carbon $date
     * @return Carbon
     */
    public function getNextWorkingDay(Carbon $date): Carbon
    {
        $nextDay = $date->copy()->addDay();

        while (!$this->isWorkingDay($nextDay)) {
            $nextDay->addDay();
        }

        return $nextDay;
    }

    /**
     * Get all working dates between two dates.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getWorkingDates(Carbon $startDate, Carbon $endDate): Collection
    {
        $weekendDayNumbers = $this->getWeekendDayNumbers();
        $holidays = $this->getHolidayDates($startDate, $endDate);

        $workingDates = collect();
        $currentDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->startOfDay();

        while ($currentDate->lte($endDate)) {
            $isWeekend = in_array($currentDate->dayOfWeek, $weekendDayNumbers);
            $isHoliday = $holidays->contains($currentDate->format('Y-m-d'));

            if (!$isWeekend && !$isHoliday) {
                $workingDates->push($currentDate->copy());
            }

            $currentDate->addDay();
        }

        return $workingDates;
    }
}
