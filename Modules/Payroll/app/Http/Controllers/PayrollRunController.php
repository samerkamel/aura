<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Modules\Payroll\Services\PayrollCalculationService;
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
     * Payroll period runs from 26th of previous month to 25th of current month.
     *
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function review(Request $request): View
    {
        // Get the selected period or default to current month
        // Period format: Y-m represents the month being paid (e.g., 2025-12 = Dec 2025 payroll)
        // Actual dates: 26th of previous month to 25th of selected month
        $selectedPeriod = $request->get('period');

        if ($selectedPeriod) {
            $periodMonth = Carbon::createFromFormat('Y-m', $selectedPeriod);
        } else {
            // Default to current payroll period based on today's date
            // If today is 26th or later, we're in next month's payroll period
            $today = Carbon::now();
            if ($today->day >= 26) {
                $periodMonth = $today->copy()->addMonth();
            } else {
                $periodMonth = $today->copy();
            }
        }

        // Period runs from 26th of previous month to 25th of current month
        $periodStart = $periodMonth->copy()->subMonth()->setDay(26)->startOfDay();
        $periodEnd = $periodMonth->copy()->setDay(25)->endOfDay();

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
     * Payroll period runs from 26th of previous month to 25th of current month.
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

        $selectedPeriod = $request->get('period');
        $periodMonth = Carbon::createFromFormat('Y-m', $selectedPeriod);

        // Period runs from 26th of previous month to 25th of current month
        $periodStart = $periodMonth->copy()->subMonth()->setDay(26)->startOfDay();
        $periodEnd = $periodMonth->copy()->setDay(25)->endOfDay();

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
}
