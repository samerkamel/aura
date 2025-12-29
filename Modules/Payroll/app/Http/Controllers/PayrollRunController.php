<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\Payroll\Services\PayrollCalculationService;
use Modules\Payroll\Models\PayrollRun;
use Modules\Settings\Models\CompanySetting;
use Carbon\Carbon;

/**
 * PayrollRunController
 *
 * Handles the "Run & Review Payroll" functionality, allowing admins to view
 * detailed payroll calculation summaries for all employees before finalizing.
 *
 * @author Dev Agent
 */
class PayrollRunController extends Controller
{
    /**
     * @var PayrollCalculationService
     */
    private $payrollCalculationService;

    public function __construct(PayrollCalculationService $payrollCalculationService)
    {
        $this->payrollCalculationService = $payrollCalculationService;
    }

    /**
     * Display the payroll review page with calculation summaries.
     *
     * Payroll period is determined by company settings (cycle_start_day).
     *
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function review(Request $request): View
    {
        $companySettings = CompanySetting::getSettings();
        $selectedPeriod = $request->get('period');

        $cycleDay = $companySettings->cycle_start_day ?? 1;

        if ($selectedPeriod) {
            // When selecting a month (e.g., December), show the period that determines that month's salary
            // If cycle starts on 26th, December salary = Nov 26 to Dec 25
            $periodMonth = Carbon::createFromFormat('Y-m', $selectedPeriod);
        } else {
            // Default to current month
            $periodMonth = Carbon::now()->startOfMonth();
        }

        // Period for selected month ends on (cycleDay - 1) of that month
        // and starts on cycleDay of the previous month
        $periodEnd = $periodMonth->copy()->day($cycleDay)->subDay()->endOfDay();
        $periodStart = $periodMonth->copy()->subMonth()->day($cycleDay)->startOfDay();

        // Calculate payroll summaries for all employees
        $employeeSummaries = $this->payrollCalculationService->calculatePayrollSummary($periodStart, $periodEnd);

        // Generate period options for the dropdown (last 6 months + current + next month)
        $periodOptions = collect();
        for ($i = -6; $i <= 1; $i++) {
            $date = Carbon::now()->addMonths($i);
            $periodOptions->push([
                'value' => $date->format('Y-m'),
                'label' => $date->format('F Y'),
                'selected' => $date->format('Y-m') === $periodMonth->format('Y-m')
            ]);
        }

        return view('payroll::run.review', compact(
            'employeeSummaries',
            'periodStart',
            'periodEnd',
            'periodOptions'
        ));
    }

    /**
     * Finalize payroll run and export bank sheet for the selected period.
     *
     * Payroll period is determined by company settings (cycle_start_day).
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Exception
     */
    public function finalizeAndExport(Request $request)
    {
        // Validate the period parameter
        $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $companySettings = CompanySetting::getSettings();
        $selectedPeriod = $request->get('period');
        $periodMonth = Carbon::createFromFormat('Y-m', $selectedPeriod);

        // When selecting a month (e.g., December), use the period that determines that month's salary
        // If cycle starts on 26th, December salary = Nov 26 to Dec 25
        $cycleDay = $companySettings->cycle_start_day ?? 1;
        $periodEnd = $periodMonth->copy()->day($cycleDay)->subDay()->endOfDay();
        $periodStart = $periodMonth->copy()->subMonth()->day($cycleDay)->startOfDay();

        try {
            // Finalize payroll run (atomic transaction in service)
            $finalizedRuns = $this->payrollCalculationService->finalizePayrollRun($periodStart, $periodEnd);

            // Generate Excel file for bank submission
            $periodLabel = $periodStart->format('F Y');
            $export = new \Modules\Payroll\Exports\BankSheetExport($finalizedRuns, $periodLabel);

            $fileName = 'payroll_bank_sheet_' . $periodStart->format('Y_m') . '.xlsx';

            // Return Excel download response
            return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Payroll finalization failed', [
                'period' => $selectedPeriod,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return with error message
            return back()->withErrors(['finalization' => 'Failed to finalize payroll: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the payroll adjustments page.
     * Creates preliminary payroll runs and allows salary adjustments before finalizing.
     *
     * @param Request $request
     * @return View
     */
    public function adjustments(Request $request): View
    {
        // Validate the period parameter
        $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $companySettings = CompanySetting::getSettings();
        $selectedPeriod = $request->get('period');
        $periodMonth = Carbon::createFromFormat('Y-m', $selectedPeriod);

        // When selecting a month (e.g., December), use the period that determines that month's salary
        // If cycle starts on 26th, December salary = Nov 26 to Dec 25
        $cycleDay = $companySettings->cycle_start_day ?? 1;
        $periodEnd = $periodMonth->copy()->day($cycleDay)->subDay()->endOfDay();
        $periodStart = $periodMonth->copy()->subMonth()->day($cycleDay)->startOfDay();

        // Get or create preliminary payroll runs for all employees
        $payrollRuns = $this->getOrCreatePreliminaryRuns($periodStart, $periodEnd);

        // Generate period options for the dropdown
        $periodOptions = collect();
        for ($i = -6; $i <= 1; $i++) {
            $date = Carbon::now()->addMonths($i);
            $periodOptions->push([
                'value' => $date->format('Y-m'),
                'label' => $date->format('F Y'),
                'selected' => $date->format('Y-m') === $periodMonth->format('Y-m')
            ]);
        }

        return view('payroll::run.adjustments', compact(
            'payrollRuns',
            'periodStart',
            'periodEnd',
            'periodOptions',
            'selectedPeriod'
        ));
    }

    /**
     * Get or create preliminary payroll runs for a period.
     *
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getOrCreatePreliminaryRuns(Carbon $periodStart, Carbon $periodEnd)
    {
        // Check if runs already exist for this period
        $existingRuns = PayrollRun::forPeriod($periodStart->toDateString(), $periodEnd->toDateString())
            ->with('employee')
            ->get();

        if ($existingRuns->count() > 0) {
            return $existingRuns;
        }

        // Calculate and create preliminary runs
        $employeeSummaries = $this->payrollCalculationService->calculatePayrollSummary($periodStart, $periodEnd);

        Log::info('Creating preliminary payroll runs', [
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'employee_count' => count($employeeSummaries),
        ]);

        DB::transaction(function () use ($employeeSummaries, $periodStart, $periodEnd) {
            foreach ($employeeSummaries as $summary) {
                $employee = $summary['employee'];
                $baseSalary = $employee->base_salary ?? 0;
                $calculatedSalary = $baseSalary * ($summary['final_performance_percentage'] / 100);

                Log::debug('Creating payroll run', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'base_salary' => $baseSalary,
                    'performance' => $summary['final_performance_percentage'],
                    'calculated_salary' => $calculatedSalary,
                ]);

                PayrollRun::create([
                    'employee_id' => $employee->id,
                    'period_start_date' => $periodStart->toDateString(),
                    'period_end_date' => $periodEnd->toDateString(),
                    'base_salary' => $baseSalary,
                    'final_salary' => $calculatedSalary,
                    'calculated_salary' => $calculatedSalary,
                    'performance_percentage' => $summary['final_performance_percentage'],
                    'bonus_amount' => 0,
                    'deduction_amount' => 0,
                    'is_adjusted' => false,
                    'calculation_snapshot' => [
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
                            'applicable' => $summary['billable_hours_applicable'],
                        ],
                        'additional_metrics' => [
                            'pto_days' => $summary['pto_days'],
                            'wfh_days' => $summary['wfh_days'],
                            'penalty_minutes' => $summary['penalty_minutes'],
                        ],
                        'calculated_at' => now()->toISOString(),
                    ],
                    'status' => 'pending_adjustment',
                ]);
            }
        });

        return PayrollRun::forPeriod($periodStart->toDateString(), $periodEnd->toDateString())
            ->with('employee')
            ->get();
    }

    /**
     * Save payroll adjustments.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAdjustments(Request $request)
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
            'adjustments' => 'required|array',
            'adjustments.*.payroll_run_id' => 'required|exists:payroll_runs,id',
            'adjustments.*.adjusted_salary' => 'nullable|numeric|min:0',
            'adjustments.*.bonus_amount' => 'nullable|numeric|min:0',
            'adjustments.*.deduction_amount' => 'nullable|numeric|min:0',
            'adjustments.*.adjustment_notes' => 'nullable|string|max:500',
        ]);

        $adjustments = $request->input('adjustments');

        DB::transaction(function () use ($adjustments) {
            foreach ($adjustments as $adjustment) {
                $payrollRun = PayrollRun::findOrFail($adjustment['payroll_run_id']);

                $hasAdjustment = !empty($adjustment['adjusted_salary']) ||
                    !empty($adjustment['bonus_amount']) ||
                    !empty($adjustment['deduction_amount']) ||
                    !empty($adjustment['adjustment_notes']);

                $payrollRun->update([
                    'adjusted_salary' => $adjustment['adjusted_salary'] ?: null,
                    'bonus_amount' => $adjustment['bonus_amount'] ?? 0,
                    'deduction_amount' => $adjustment['deduction_amount'] ?? 0,
                    'adjustment_notes' => $adjustment['adjustment_notes'] ?? null,
                    'is_adjusted' => $hasAdjustment,
                    // Update final_salary to reflect the effective salary
                    'final_salary' => $this->calculateEffectiveSalary($payrollRun, $adjustment),
                ]);
            }
        });

        return redirect()
            ->route('payroll.run.adjustments', ['period' => $request->input('period')])
            ->with('success', 'Adjustments saved successfully.');
    }

    /**
     * Calculate effective salary based on adjustments.
     *
     * @param PayrollRun $payrollRun
     * @param array $adjustment
     * @return float
     */
    private function calculateEffectiveSalary(PayrollRun $payrollRun, array $adjustment): float
    {
        $baseSalary = !empty($adjustment['adjusted_salary'])
            ? (float) $adjustment['adjusted_salary']
            : (float) $payrollRun->calculated_salary;

        $bonus = (float) ($adjustment['bonus_amount'] ?? 0);
        $deduction = (float) ($adjustment['deduction_amount'] ?? 0);

        return $baseSalary + $bonus - $deduction;
    }

    /**
     * Finalize payroll from the adjustments page.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function finalizeFromAdjustments(Request $request)
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $companySettings = CompanySetting::getSettings();
        $selectedPeriod = $request->get('period');
        $periodMonth = Carbon::createFromFormat('Y-m', $selectedPeriod);

        // When selecting a month (e.g., December), use the period that determines that month's salary
        // If cycle starts on 26th, December salary = Nov 26 to Dec 25
        $cycleDay = $companySettings->cycle_start_day ?? 1;
        $periodEnd = $periodMonth->copy()->day($cycleDay)->subDay()->endOfDay();
        $periodStart = $periodMonth->copy()->subMonth()->day($cycleDay)->startOfDay();

        try {
            // Update all pending payroll runs to finalized status
            $payrollRuns = PayrollRun::forPeriod($periodStart->toDateString(), $periodEnd->toDateString())
                ->with('employee')
                ->get();

            if ($payrollRuns->isEmpty()) {
                return back()->withErrors(['finalization' => 'No payroll records found for this period.']);
            }

            DB::transaction(function () use ($payrollRuns) {
                foreach ($payrollRuns as $run) {
                    $run->update([
                        'status' => 'finalized',
                        'calculation_snapshot' => array_merge($run->calculation_snapshot ?? [], [
                            'finalized_at' => now()->toISOString(),
                        ]),
                    ]);
                }
            });

            // Generate Excel file for bank submission
            $periodLabel = $periodStart->format('F Y');
            $export = new \Modules\Payroll\Exports\BankSheetExport($payrollRuns, $periodLabel);

            $fileName = 'payroll_bank_sheet_' . $periodStart->format('Y_m') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
        } catch (\Exception $e) {
            Log::error('Payroll finalization from adjustments failed', [
                'period' => $selectedPeriod,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['finalization' => 'Failed to finalize payroll: ' . $e->getMessage()]);
        }
    }

    /**
     * Recalculate payroll runs for a period (reset adjustments).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function recalculate(Request $request)
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $companySettings = CompanySetting::getSettings();
        $selectedPeriod = $request->get('period');
        $periodMonth = Carbon::createFromFormat('Y-m', $selectedPeriod);

        // When selecting a month (e.g., December), use the period that determines that month's salary
        // If cycle starts on 26th, December salary = Nov 26 to Dec 25
        $cycleDay = $companySettings->cycle_start_day ?? 1;
        $periodEnd = $periodMonth->copy()->day($cycleDay)->subDay()->endOfDay();
        $periodStart = $periodMonth->copy()->subMonth()->day($cycleDay)->startOfDay();

        // Delete existing runs for this period (only if not finalized)
        $deletedCount = PayrollRun::forPeriod($periodStart->toDateString(), $periodEnd->toDateString())
            ->where('status', '!=', 'finalized')
            ->delete();

        Log::info('Payroll recalculation completed', [
            'period' => $selectedPeriod,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'deleted_runs' => $deletedCount,
        ]);

        return redirect()
            ->route('payroll.run.adjustments', ['period' => $selectedPeriod])
            ->with('success', "Payroll recalculated successfully. {$deletedCount} runs deleted.");
    }
}
