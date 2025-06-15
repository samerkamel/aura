<?php

namespace Modules\Payroll\Services;

use Modules\HR\Models\Employee;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Models\AttendanceRule;
use Modules\Attendance\Models\PermissionOverride;
use Modules\Attendance\Models\PublicHoliday;
use Modules\Attendance\Models\WfhRecord;
use Modules\Leave\Models\LeaveRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * AttendanceCalculationService
 *
 * Handles the calculation of net attended hours for employees within a given payroll period.
 * Applies flexible hours rules, late penalties, permission deductions, and excludes public holidays.
 * Incorporates leave records (PTO, Sick Leave) and WFH days into the calculation.
 *
 * @author Dev Agent
 */
class AttendanceCalculationService
{
  /**
   * Calculate the net attended hours for an employee within a specific payroll period.
   *
   * @param Employee $employee The employee to calculate hours for
   * @param Carbon $periodStart The start date of the payroll period
   * @param Carbon $periodEnd The end date of the payroll period
   * @return float The total net attended hours for the period
   * @throws \Exception If calculation cannot be performed
   */
  public function calculateNetHours(Employee $employee, Carbon $periodStart, Carbon $periodEnd): float
  {
    // Fetch all required data for the calculation
    $data = $this->fetchCalculationData($employee, $periodStart, $periodEnd);

    // Process attendance logs to calculate raw work hours
    $dailyHours = $this->processAttendanceLogs($data['attendanceLogs']);

    // Apply leave records (PTO, Sick Leave) - these count as full work days
    $dailyHours = $this->applyLeaveRecords($dailyHours, $data['leaveRecords'], $periodStart, $periodEnd);

    // Apply WFH records - these contribute based on WFH policy percentage
    $dailyHours = $this->applyWfhRecords($dailyHours, $data['wfhRecords'], $data['wfhRule']);

    // Check if daily hours were calculated
    if (empty($dailyHours)) {
      return 0.0; // No daily hours calculated
    }

    // Apply flexible hours and late penalty rules
    $adjustedHours = $this->applyRules($dailyHours, $data['flexibleHoursRule'], $data['latePenaltyRule']);

    // Apply permission deductions
    $netHours = $this->applyPermissions($adjustedHours, $data['totalPermissionMinutes']);

    return round($netHours, 2);
  }

  /**
   * Fetch all required data for the calculation.
   *
   * @param Employee $employee
   * @param Carbon $periodStart
   * @param Carbon $periodEnd
   * @return array
   */
  protected function fetchCalculationData(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
  {
    // Ensure period boundaries include full days
    $periodStartInclusive = $periodStart->copy()->startOfDay();
    $periodEndInclusive = $periodEnd->copy()->endOfDay();

    // Get attendance logs for the employee within the period
    $attendanceLogs = AttendanceLog::where('employee_id', $employee->id)
      ->whereBetween('timestamp', [$periodStartInclusive, $periodEndInclusive])
      ->orderBy('timestamp')
      ->get();

    // Get flexible hours rule
    $flexibleHoursRule = AttendanceRule::where('rule_type', AttendanceRule::TYPE_FLEXIBLE_HOURS)->first();

    // Get late penalty rule
    $latePenaltyRule = AttendanceRule::where('rule_type', AttendanceRule::TYPE_LATE_PENALTY)->first();

    // Get permission rule for standard monthly allowance
    $permissionRule = AttendanceRule::where('rule_type', AttendanceRule::TYPE_PERMISSION)->first();

    // Get permission overrides for this employee and period
    $permissionOverrides = PermissionOverride::where('employee_id', $employee->id)
      ->where('payroll_period_start_date', $periodStart->format('Y-m-d'))
      ->sum('extra_permissions_granted');

    // Calculate total permission minutes available
    $standardPermissionMinutes = $permissionRule ? ($permissionRule->config['monthly_allowance_minutes'] ?? 0) : 0;
    $totalPermissionMinutes = $standardPermissionMinutes + $permissionOverrides;

    // Get public holidays within the period
    $publicHolidays = PublicHoliday::whereBetween('date', [$periodStartInclusive, $periodEndInclusive])
      ->pluck('date')
      ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
      ->toArray();

    // Get approved leave records for the employee within the period
    $leaveRecords = LeaveRecord::where('employee_id', $employee->id)
      ->approved()
      ->inDateRange($periodStartInclusive, $periodEndInclusive)
      ->with('leavePolicy')
      ->get();

    // Get WFH records for the employee within the period
    $wfhRecords = WfhRecord::where('employee_id', $employee->id)
      ->inDateRange($periodStartInclusive, $periodEndInclusive)
      ->get();

    // Get WFH policy rule
    $wfhRule = AttendanceRule::where('rule_type', 'wfh_policy')->first();

    return [
      'attendanceLogs' => $attendanceLogs,
      'flexibleHoursRule' => $flexibleHoursRule,
      'latePenaltyRule' => $latePenaltyRule,
      'totalPermissionMinutes' => $totalPermissionMinutes,
      'publicHolidays' => $publicHolidays,
      'leaveRecords' => $leaveRecords,
      'wfhRecords' => $wfhRecords,
      'wfhRule' => $wfhRule,
    ];
  }

  /**
   * Process attendance logs to calculate raw work duration for each day.
   *
   * @param Collection $attendanceLogs
   * @return array Array of daily hours with date as key
   */
  protected function processAttendanceLogs(Collection $attendanceLogs): array
  {
    $dailyHours = [];
    $logsByDate = $attendanceLogs->groupBy(fn($log) => $log->timestamp->format('Y-m-d'));

    foreach ($logsByDate as $date => $logs) {
      $dailyHours[$date] = $this->calculateDailyHours($logs);
    }

    return $dailyHours;
  }

  /**
   * Calculate work duration for a single day from attendance logs.
   *
   * @param Collection $dailyLogs
   * @return array Contains raw hours, sign_in_time, sign_out_time, and any issues
   */
  protected function calculateDailyHours(Collection $dailyLogs): array
  {
    $signInLog = $dailyLogs->where('type', 'sign_in')->first();
    $signOutLog = $dailyLogs->where('type', 'sign_out')->first();

    if (!$signInLog) {
      return [
        'raw_hours' => 0,
        'sign_in_time' => null,
        'sign_out_time' => null,
        'issue' => 'missing_sign_in'
      ];
    }

    if (!$signOutLog) {
      return [
        'raw_hours' => 0,
        'sign_in_time' => $signInLog->timestamp,
        'sign_out_time' => null,
        'issue' => 'missing_sign_out'
      ];
    }

    // Calculate work duration in hours
    $workDurationMinutes = $signInLog->timestamp->diffInMinutes($signOutLog->timestamp);
    $workDuration = $workDurationMinutes / 60;

    return [
      'raw_hours' => max(0, $workDuration), // Ensure non-negative
      'sign_in_time' => $signInLog->timestamp,
      'sign_out_time' => $signOutLog->timestamp,
      'issue' => null
    ];
  }

  /**
   * Apply flexible hours and late penalty rules to daily hours.
   *
   * @param array $dailyHours
   * @param AttendanceRule|null $flexibleHoursRule
   * @param AttendanceRule|null $latePenaltyRule
   * @return array
   */
  protected function applyRules(array $dailyHours, ?AttendanceRule $flexibleHoursRule, ?AttendanceRule $latePenaltyRule): array
  {
    $adjustedHours = [];

    foreach ($dailyHours as $date => $dayData) {
      $adjustedHours[$date] = $this->applyDailyRules($dayData, $flexibleHoursRule, $latePenaltyRule);
    }

    return $adjustedHours;
  }

  /**
   * Apply rules to a single day's attendance data.
   *
   * @param array $dayData
   * @param AttendanceRule|null $flexibleHoursRule
   * @param AttendanceRule|null $latePenaltyRule
   * @return array
   */
  protected function applyDailyRules(array $dayData, ?AttendanceRule $flexibleHoursRule, ?AttendanceRule $latePenaltyRule): array
  {
    $result = $dayData;
    $result['penalty_minutes'] = 0;
    $result['adjusted_hours'] = $dayData['raw_hours'];

    // Skip rule application if there's no sign-in or if there's an issue
    if (!$dayData['sign_in_time'] || $dayData['issue']) {
      return $result;
    }

    // Apply flexible hours rule to determine if there's a late penalty
    if ($flexibleHoursRule && $latePenaltyRule) {
      $penaltyMinutes = $this->calculateLatePenalty(
        $dayData['sign_in_time'],
        $flexibleHoursRule->config,
        $latePenaltyRule->config
      );

      $result['penalty_minutes'] = $penaltyMinutes;
      $result['adjusted_hours'] = max(0, $dayData['raw_hours'] - ($penaltyMinutes / 60));
    }

    return $result;
  }

  /**
   * Calculate late penalty for a given sign-in time.
   *
   * @param Carbon $signInTime
   * @param array $flexibleHoursConfig
   * @param array $latePenaltyConfig
   * @return int Penalty in minutes
   */
  protected function calculateLatePenalty(Carbon $signInTime, array $flexibleHoursConfig, array $latePenaltyConfig): int
  {
    $officialStartTime = $flexibleHoursConfig['official_start_time'] ?? '09:00';
    $flexibleWindowMinutes = $flexibleHoursConfig['flexible_window_minutes'] ?? 0;

    // Calculate the flexible start time (official start + flexible window)
    $flexibleStartTime = Carbon::createFromFormat('H:i', $officialStartTime)
      ->setDateFrom($signInTime)
      ->addMinutes($flexibleWindowMinutes);

    // If sign-in is within flexible window, no penalty
    if ($signInTime->lte($flexibleStartTime)) {
      return 0;
    }

    // Calculate how many minutes late
    $minutesLate = $flexibleStartTime->diffInMinutes($signInTime);

    // Apply tiered penalty structure
    $penalties = $latePenaltyConfig['tiers'] ?? [];

    foreach ($penalties as $tier) {
      $minLate = $tier['min_minutes_late'] ?? 0;
      $maxLate = $tier['max_minutes_late'] ?? PHP_INT_MAX;

      if ($minutesLate >= $minLate && $minutesLate <= $maxLate) {
        return $tier['penalty_minutes'] ?? 0;
      }
    }

    return 0;
  }

  /**
   * Apply permission deductions to offset penalties.
   *
   * @param array $adjustedHours
   * @param int $totalPermissionMinutes
   * @return float
   */
  protected function applyPermissions(array $adjustedHours, int $totalPermissionMinutes): float
  {
    $totalWorkHours = 0;
    $totalPenaltyMinutes = 0;

    foreach ($adjustedHours as $dayData) {
      $totalWorkHours += $dayData['adjusted_hours']; // This already has penalties subtracted
      $totalPenaltyMinutes += $dayData['penalty_minutes'] ?? 0;
    }

    // Calculate how much of the penalty can be offset by available permissions
    $permissionOffsetMinutes = min($totalPenaltyMinutes, $totalPermissionMinutes);

    // Add back the offset as hours
    $finalHours = $totalWorkHours + ($permissionOffsetMinutes / 60);

    return max(0, $finalHours);
  }

  /**
   * Apply leave records to daily hours calculation.
   * PTO and Sick Leave days are treated as fully attended workdays.
   *
   * @param array $dailyHours
   * @param Collection $leaveRecords
   * @param Carbon $periodStart
   * @param Carbon $periodEnd
   * @return array
   */
  protected function applyLeaveRecords(array $dailyHours, Collection $leaveRecords, Carbon $periodStart, Carbon $periodEnd): array
  {
    if ($leaveRecords->isEmpty()) {
      return $dailyHours;
    }

    $standardWorkHours = 8.0; // Default standard work hours

    foreach ($leaveRecords as $leaveRecord) {
      $dailyHours = $this->processLeaveRecord($dailyHours, $leaveRecord, $periodStart, $periodEnd, $standardWorkHours);
    }

    return $dailyHours;
  }

  /**
   * Process a single leave record.
   *
   * @param array $dailyHours
   * @param LeaveRecord $leaveRecord
   * @param Carbon $periodStart
   * @param Carbon $periodEnd
   * @param float $standardWorkHours
   * @return array
   */
  protected function processLeaveRecord(array $dailyHours, $leaveRecord, Carbon $periodStart, Carbon $periodEnd, float $standardWorkHours): array
  {
    $currentDate = $leaveRecord->start_date->copy();

    while ($currentDate->lte($leaveRecord->end_date)) {
      $dateKey = $currentDate->format('Y-m-d');

      // Only process dates within the payroll period and exclude weekends
      if ($currentDate->gte($periodStart) && $currentDate->lte($periodEnd) && !$currentDate->isWeekend()) {
        $dailyHours = $this->applyLeaveDayToHours($dailyHours, $dateKey, $leaveRecord, $standardWorkHours);
      }

      $currentDate->addDay();
    }

    return $dailyHours;
  }

  /**
   * Apply leave day to daily hours.
   *
   * @param array $dailyHours
   * @param string $dateKey
   * @param LeaveRecord $leaveRecord
   * @param float $standardWorkHours
   * @return array
   */
  protected function applyLeaveDayToHours(array $dailyHours, string $dateKey, $leaveRecord, float $standardWorkHours): array
  {
    if (!isset($dailyHours[$dateKey])) {
      // Create new leave day entry
      $dailyHours[$dateKey] = [
        'raw_hours' => $standardWorkHours,
        'sign_in_time' => null,
        'sign_out_time' => null,
        'issue' => null,
        'leave_type' => $leaveRecord->leavePolicy->type,
        'is_leave_day' => true,
      ];
    } else {
      // Update existing entry with leave information
      $dailyHours[$dateKey]['raw_hours'] = $standardWorkHours;
      $dailyHours[$dateKey]['leave_type'] = $leaveRecord->leavePolicy->type;
      $dailyHours[$dateKey]['is_leave_day'] = true;
    }

    return $dailyHours;
  }

  /**
   * Apply WFH records to daily hours calculation.
   * WFH days contribute to attendance based on the percentage defined in the WFH Policy.
   *
   * @param array $dailyHours
   * @param Collection $wfhRecords
   * @param AttendanceRule|null $wfhRule
   * @return array
   */
  protected function applyWfhRecords(array $dailyHours, Collection $wfhRecords, ?AttendanceRule $wfhRule): array
  {
    if ($wfhRecords->isEmpty()) {
      return $dailyHours;
    }

    // Get WFH contribution percentage (default to 100% if no rule)
    $wfhContributionPercentage = 1.0; // 100% by default
    if ($wfhRule && isset($wfhRule->config['attendance_contribution_percentage'])) {
      $wfhContributionPercentage = $wfhRule->config['attendance_contribution_percentage'] / 100;
    }

    // Get standard work hours (default to 8 hours)
    $standardWorkHours = 8.0;

    foreach ($wfhRecords as $wfhRecord) {
      $dateKey = $wfhRecord->date->format('Y-m-d');

      // Skip if this date is already a leave day (leave takes precedence over WFH)
      if (isset($dailyHours[$dateKey]) && !empty($dailyHours[$dateKey]['is_leave_day'])) {
        continue;
      }

      // If there's no attendance record for this date, create one for WFH
      if (!isset($dailyHours[$dateKey])) {
        $dailyHours[$dateKey] = [
          'raw_hours' => $standardWorkHours * $wfhContributionPercentage,
          'sign_in_time' => null,
          'sign_out_time' => null,
          'issue' => null,
          'is_wfh_day' => true,
          'wfh_contribution_percentage' => $wfhContributionPercentage * 100,
        ];
      } else {
        // If there's already an attendance record, apply WFH contribution
        // WFH percentage applies to the existing hours
        $dailyHours[$dateKey]['raw_hours'] *= $wfhContributionPercentage;
        $dailyHours[$dateKey]['is_wfh_day'] = true;
        $dailyHours[$dateKey]['wfh_contribution_percentage'] = $wfhContributionPercentage * 100;
      }
    }

    return $dailyHours;
  }
}
