<?php

namespace Modules\Payroll\Services;

use Modules\HR\Models\Employee;
use Modules\Payroll\Models\BillableHour;
use Modules\Payroll\Models\JiraWorklog;
use Modules\Payroll\Models\PayrollPeriodSetting;
use Modules\Attendance\Models\Setting;
use Modules\Attendance\Models\WfhRecord;
use Modules\Leave\Models\LeaveRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PayrollCalculationService
 *
 * Orchestrates the entire payroll calculation process by combining attendance,
 * billable hours, and other factors to compute final performance percentages.
 * Consumes the AttendanceCalculationService and applies configured weights.
 *
 * @author Dev Agent
 */
class PayrollCalculationService
{
  /**
   * @var AttendanceCalculationService
   */
  private $attendanceService;

  public function __construct(AttendanceCalculationService $attendanceService)
  {
    $this->attendanceService = $attendanceService;
  }

  /**
   * Calculate payroll summary for all active employees in a given period.
   *
   * @param Carbon $periodStart The start date of the payroll period
   * @param Carbon $periodEnd The end date of the payroll period
   * @return Collection Collection of employee payroll summaries
   * @throws \Exception If calculation cannot be performed
   */
  public function calculatePayrollSummary(Carbon $periodStart, Carbon $periodEnd): Collection
  {
    // Get all active employees
    $employees = Employee::where('status', 'active')->get();

    // Get configured weights
    $attendanceWeight = (float) Setting::get('weight_attendance_pct', 50);
    $billableHoursWeight = (float) Setting::get('weight_billable_hours_pct', 50);

    $summaries = collect();

    foreach ($employees as $employee) {
      $summary = $this->calculateEmployeePayrollSummary(
        $employee,
        $periodStart,
        $periodEnd,
        $attendanceWeight,
        $billableHoursWeight
      );

      $summaries->push($summary);
    }

    return $summaries;
  }

  /**
   * Calculate payroll summary for a single employee.
   *
   * @param Employee $employee The employee to calculate for
   * @param Carbon $periodStart The start date of the payroll period
   * @param Carbon $periodEnd The end date of the payroll period
   * @param float $attendanceWeight Weight for attendance percentage
   * @param float $billableHoursWeight Weight for billable hours percentage
   * @return array Employee payroll summary data
   * @throws \Exception If calculation cannot be performed
   */
  private function calculateEmployeePayrollSummary(
    Employee $employee,
    Carbon $periodStart,
    Carbon $periodEnd,
    float $attendanceWeight,
    float $billableHoursWeight
  ): array {
    // Calculate attendance metrics using the AttendanceCalculationService
    $netAttendedHours = $this->attendanceService->calculateNetHours($employee, $periodStart, $periodEnd);

    // Get required monthly hours (assuming 8 hours per working day, ~22 working days per month)
    $requiredMonthlyHours = $this->calculateRequiredMonthlyHours($periodStart, $periodEnd);

    // Calculate attendance percentage
    $attendancePercentage = $requiredMonthlyHours > 0
      ? min(100, ($netAttendedHours / $requiredMonthlyHours) * 100)
      : 0;

    // Check if billable hours apply to this employee
    $billableHoursApplicable = $employee->billable_hours_applicable ?? true;

    // Initialize billable hours variables
    $billableHours = 0;
    $jiraWorklogHours = 0;
    $manualBillableHours = 0;
    $targetBillableHours = 0;
    $billableHoursPercentage = 0;

    if ($billableHoursApplicable) {
      // Get manual billable hours for the period
      $billableHoursRecord = BillableHour::where('employee_id', $employee->id)
        ->where('payroll_period_start_date', $periodStart->toDateString())
        ->first();

      $manualBillableHours = $billableHoursRecord ? $billableHoursRecord->hours : 0;

      // Get Jira worklog hours for the period
      $jiraWorklogHours = JiraWorklog::where('employee_id', $employee->id)
        ->whereBetween('worklog_started', [$periodStart, $periodEnd])
        ->sum('time_spent_hours');

      // Total billable hours = Jira worklogs + manual entry
      $billableHours = (float) $jiraWorklogHours + (float) $manualBillableHours;

      // Get target billable hours: admin override or calculated (6 hours/day, max 120)
      // Use periodEnd to determine the payroll month (Dec 25 = December payroll)
      $periodSetting = PayrollPeriodSetting::forPeriod($periodEnd);
      if ($periodSetting && $periodSetting->target_billable_hours !== null) {
        $targetBillableHours = (float) $periodSetting->target_billable_hours;
      } else {
        $workingDays = $this->countWorkingDays($periodStart, $periodEnd);
        $targetBillableHours = min($workingDays * 6, 120);
      }

      // Calculate billable hours percentage
      $billableHoursPercentage = $targetBillableHours > 0
        ? min(100, ($billableHours / $targetBillableHours) * 100)
        : 0;
    }

    // Calculate final weighted performance percentage
    // For employees where billable hours don't apply, use 100% attendance weight
    $effectiveAttendanceWeight = $billableHoursApplicable ? $attendanceWeight : 100;
    $effectiveBillableWeight = $billableHoursApplicable ? $billableHoursWeight : 0;

    if ($billableHoursApplicable) {
      $finalPerformancePercentage = (
        ($attendancePercentage * ($attendanceWeight / 100)) +
        ($billableHoursPercentage * ($billableHoursWeight / 100))
      );
    } else {
      // If billable hours don't apply, salary is 100% based on attendance
      $finalPerformancePercentage = $attendancePercentage;
    }

    // Get additional metrics
    $additionalMetrics = $this->getAdditionalMetrics($employee, $periodStart, $periodEnd);

    return [
      'employee' => $employee,
      'net_attended_hours' => round($netAttendedHours, 2),
      'required_monthly_hours' => $requiredMonthlyHours,
      'attendance_percentage' => round($attendancePercentage, 2),
      'billable_hours_applicable' => $billableHoursApplicable,
      'billable_hours' => round($billableHours, 2),
      'jira_worklog_hours' => round((float) $jiraWorklogHours, 2),
      'manual_billable_hours' => round((float) $manualBillableHours, 2),
      'target_billable_hours' => round($targetBillableHours, 2),
      'billable_hours_percentage' => round($billableHoursPercentage, 2),
      'final_performance_percentage' => round($finalPerformancePercentage, 2),
      'attendance_weight' => $effectiveAttendanceWeight,
      'billable_hours_weight' => $effectiveBillableWeight,
      'pto_days' => $additionalMetrics['pto_days'],
      'wfh_days' => $additionalMetrics['wfh_days'],
      'penalty_minutes' => $additionalMetrics['penalty_minutes'],
    ];
  }

  /**
   * Count working days (Monday to Friday) in the given period.
   *
   * @param Carbon $periodStart
   * @param Carbon $periodEnd
   * @return int Number of working days
   */
  private function countWorkingDays(Carbon $periodStart, Carbon $periodEnd): int
  {
    $workingDays = 0;
    $current = $periodStart->copy();

    while ($current->lte($periodEnd)) {
      if ($current->isWeekday()) {
        $workingDays++;
      }
      $current->addDay();
    }

    return $workingDays;
  }

  /**
   * Calculate required monthly hours for the given period.
   *
   * @param Carbon $periodStart
   * @param Carbon $periodEnd
   * @return float Required hours for the period
   */
  private function calculateRequiredMonthlyHours(Carbon $periodStart, Carbon $periodEnd): float
  {
    // Assuming 8 hours per working day
    return $this->countWorkingDays($periodStart, $periodEnd) * 8;
  }

  /**
   * Get additional metrics for an employee in the given period.
   *
   * @param Employee $employee
   * @param Carbon $periodStart
   * @param Carbon $periodEnd
   * @return array Additional metrics (PTO days, WFH days, penalty minutes)
   */
  private function getAdditionalMetrics(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
  {
    // Get PTO/Leave days - count actual leave days within the period
    $ptoDays = $this->calculateLeaveDaysInPeriod($employee, $periodStart, $periodEnd);

    // Get WFH days
    $wfhDays = WfhRecord::where('employee_id', $employee->id)
      ->whereBetween('date', [$periodStart, $periodEnd])
      ->count();

    // Get penalty minutes - will be calculated from attendance logs in future iterations
    $penaltyMinutes = 0;

    return [
      'pto_days' => $ptoDays,
      'wfh_days' => $wfhDays,
      'penalty_minutes' => $penaltyMinutes,
    ];
  }

  /**
   * Calculate the number of leave days within the payroll period.
   * Only counts approved leave records and calculates the overlap with the period.
   *
   * @param Employee $employee
   * @param Carbon $periodStart
   * @param Carbon $periodEnd
   * @return int Number of leave days within the period
   */
  private function calculateLeaveDaysInPeriod(Employee $employee, Carbon $periodStart, Carbon $periodEnd): int
  {
    // Get approved leave records that overlap with the period
    $leaveRecords = LeaveRecord::where('employee_id', $employee->id)
      ->approved()
      ->inDateRange($periodStart, $periodEnd)
      ->get();

    $totalLeaveDays = 0;

    foreach ($leaveRecords as $leaveRecord) {
      // Calculate the overlap between leave dates and payroll period
      $leaveStart = $leaveRecord->start_date;
      $leaveEnd = $leaveRecord->end_date;

      // Get the actual start (max of leave start and period start)
      $effectiveStart = $leaveStart->gt($periodStart) ? $leaveStart : $periodStart;

      // Get the actual end (min of leave end and period end)
      $effectiveEnd = $leaveEnd->lt($periodEnd) ? $leaveEnd : $periodEnd;

      // Count working days (excluding weekends) in the effective range
      $current = $effectiveStart->copy();
      while ($current->lte($effectiveEnd)) {
        if ($current->isWeekday()) {
          $totalLeaveDays++;
        }
        $current->addDay();
      }
    }

    return $totalLeaveDays;
  }

  /**
   * Finalize payroll run for all employees in a given period.
   * Saves permanent records to the payroll_runs table with calculated salaries
   * and a JSON snapshot of contributing factors.
   *
   * @param Carbon $periodStart The start date of the payroll period
   * @param Carbon $periodEnd The end date of the payroll period
   * @return Collection Collection of finalized PayrollRun models
   * @throws \Exception If finalization cannot be performed
   */
  public function finalizePayrollRun(Carbon $periodStart, Carbon $periodEnd): Collection
  {
    // Get all active employees
    $employees = Employee::where('status', 'active')->get();

    // Get configured weights
    $attendanceWeight = (float) Setting::get('weight_attendance_pct', 50);
    $billableHoursWeight = (float) Setting::get('weight_billable_hours_pct', 50);

    $finalizedRuns = collect();

    // Use database transaction to ensure atomicity
    DB::transaction(function () use ($employees, $periodStart, $periodEnd, $attendanceWeight, $billableHoursWeight, &$finalizedRuns) {
      foreach ($employees as $employee) {
        // Calculate employee payroll summary
        $summary = $this->calculateEmployeePayrollSummary(
          $employee,
          $periodStart,
          $periodEnd,
          $attendanceWeight,
          $billableHoursWeight
        );

        // Calculate final salary based on performance percentage
        $baseSalary = $employee->base_salary ?? 0;
        $finalSalary = $baseSalary * ($summary['final_performance_percentage'] / 100);

        // Create calculation snapshot
        $calculationSnapshot = [
          'attendance' => [
            'net_attended_hours' => $summary['net_attended_hours'],
            'required_monthly_hours' => $summary['required_monthly_hours'],
            'percentage' => $summary['attendance_percentage'],
            'weight' => $summary['attendance_weight'],
          ],
          'billable_hours' => [
            'billable_hours' => $summary['billable_hours'],
            'jira_worklog_hours' => $summary['jira_worklog_hours'],
            'manual_billable_hours' => $summary['manual_billable_hours'],
            'target_billable_hours' => $summary['target_billable_hours'],
            'percentage' => $summary['billable_hours_percentage'],
            'weight' => $summary['billable_hours_weight'],
          ],
          'additional_metrics' => [
            'pto_days' => $summary['pto_days'],
            'wfh_days' => $summary['wfh_days'],
            'penalty_minutes' => $summary['penalty_minutes'],
          ],
          'calculated_at' => now()->toISOString(),
          'system_settings' => [
            'attendance_weight' => $attendanceWeight,
            'billable_hours_weight' => $billableHoursWeight,
          ],
        ];

        // Create or update payroll run record
        $payrollRun = \Modules\Payroll\Models\PayrollRun::updateOrCreate(
          [
            'employee_id' => $employee->id,
            'period_start_date' => $periodStart->toDateString(),
            'period_end_date' => $periodEnd->toDateString(),
          ],
          [
            'base_salary' => $baseSalary,
            'final_salary' => $finalSalary,
            'performance_percentage' => $summary['final_performance_percentage'],
            'calculation_snapshot' => $calculationSnapshot,
            'status' => 'finalized',
          ]
        );

        $finalizedRuns->push($payrollRun);
      }
    });

    return $finalizedRuns;
  }
}
