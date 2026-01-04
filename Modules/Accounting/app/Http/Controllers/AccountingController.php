<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Accounting\Services\CashFlowProjectionService;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Accounting\Models\Contract;
use Modules\Accounting\Models\ContractPayment;
use Modules\Accounting\Models\ExpenseCategory;
use Modules\Accounting\Models\Account;
use Modules\Settings\Models\CompanySetting;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use League\Csv\Writer;

/**
 * AccountingController
 *
 * Main controller for the Accounting dashboard and overview functionality.
 */
class AccountingController extends Controller
{
    protected CashFlowProjectionService $projectionService;

    public function __construct(CashFlowProjectionService $projectionService)
    {
        $this->projectionService = $projectionService;
    }

    /**
     * Display the accounting dashboard.
     */
    public function index(): View
    {
        // Check authorization
        if (!auth()->user()->can('view-accounting-dashboard')) {
            abort(403, 'Unauthorized to access accounting dashboard.');
        }

        // Get company settings for fiscal cycle
        $companySettings = CompanySetting::getSettings();

        // Current period date range (respects cycle_start_day setting)
        $currentMonthStart = $companySettings->getCurrentPeriodStart();
        $currentMonthEnd = $companySettings->getCurrentPeriodEnd();

        // Previous period for growth calculations
        $previousMonthStart = $companySettings->getPeriodStartForDate(now()->subMonth());
        $previousMonthEnd = $companySettings->getPeriodEndForDate(now()->subMonth());

        // Fiscal year start (respects fiscal_year_start_month and cycle_start_day)
        $yearStart = $companySettings->getFiscalYearStart();

        // Get starting balance from accounts
        $totalAccountBalance = Account::active()->sum('current_balance');

        // Get current month projections
        $endMonth = now()->addMonths(6)->endOfMonth();

        // Generate projections for dashboard
        $projections = $this->projectionService
            ->setStartingBalance($totalAccountBalance)
            ->generateProjections($currentMonthStart, $endMonth, 'monthly');

        // ACTUAL PAID EXPENSES this month (from imported/recorded expenses)
        $monthlyExpensesActual = ExpenseSchedule::where('payment_status', 'paid')
            ->where('paid_date', '>=', $currentMonthStart)
            ->where('paid_date', '<=', $currentMonthEnd)
            ->sum('paid_amount');

        // Previous month expenses for growth calculation
        $previousMonthExpenses = ExpenseSchedule::where('payment_status', 'paid')
            ->where('paid_date', '>=', $previousMonthStart)
            ->where('paid_date', '<=', $previousMonthEnd)
            ->sum('paid_amount');

        // ACTUAL INCOME this month (from paid contract payments + income expenses)
        $monthlyIncomeFromContracts = ContractPayment::where('status', 'paid')
            ->where('paid_date', '>=', $currentMonthStart)
            ->where('paid_date', '<=', $currentMonthEnd)
            ->sum('amount');

        // Income from imported income items (is_income = true expenses that were paid)
        $monthlyIncomeFromImports = ExpenseSchedule::where('payment_status', 'paid')
            ->whereHas('category', function($q) {
                // Income category or positive amounts
            })
            ->where('paid_date', '>=', $currentMonthStart)
            ->where('paid_date', '<=', $currentMonthEnd)
            ->where('paid_amount', '>', 0) // Positive amounts are income
            ->sum('paid_amount');

        // Also check for pending contract payments this month (expected income)
        $expectedIncome = ContractPayment::where('status', 'pending')
            ->where('due_date', '>=', $currentMonthStart)
            ->where('due_date', '<=', $currentMonthEnd)
            ->sum('amount');

        $monthlyIncome = $monthlyIncomeFromContracts + $expectedIncome;

        // Previous month income for growth calculation
        $previousMonthIncome = ContractPayment::where('status', 'paid')
            ->where('paid_date', '>=', $previousMonthStart)
            ->where('paid_date', '<=', $previousMonthEnd)
            ->sum('amount');

        // Calculate real growth percentages
        $incomeGrowth = $previousMonthIncome > 0
            ? (($monthlyIncome - $previousMonthIncome) / $previousMonthIncome) * 100
            : 0;

        $expenseGrowth = $previousMonthExpenses > 0
            ? (($monthlyExpensesActual - $previousMonthExpenses) / $previousMonthExpenses) * 100
            : 0;

        // Use actual expenses for display, fallback to recurring if no paid expenses
        $monthlyExpenses = $monthlyExpensesActual > 0
            ? $monthlyExpensesActual
            : ExpenseSchedule::active()->get()->sum('monthly_equivalent_amount');

        $netCashFlow = $monthlyIncome - $monthlyExpenses;

        $activeContracts = Contract::active()->count();
        $totalContractValue = Contract::active()->sum('total_amount');

        // Year-to-date totals
        $ytdExpenses = ExpenseSchedule::where('payment_status', 'paid')
            ->where('paid_date', '>=', $yearStart)
            ->sum('paid_amount');

        $ytdIncome = ContractPayment::where('status', 'paid')
            ->where('paid_date', '>=', $yearStart)
            ->sum('amount');

        // Account balances summary
        $accounts = Account::active()->orderByDesc('current_balance')->get();
        $accountsSummary = [
            'total_balance' => $totalAccountBalance,
            'accounts' => $accounts->take(5),
            'count' => $accounts->count(),
        ];

        // Get upcoming payments (next 30 days)
        $upcomingPayments = collect();

        // Add expense payments
        ExpenseSchedule::active()->with('category')->get()->each(function($schedule) use ($upcomingPayments) {
            if ($schedule->next_payment_date && $schedule->next_payment_date <= now()->addDays(30)) {
                $upcomingPayments->push([
                    'type' => 'expense',
                    'name' => $schedule->name,
                    'amount' => $schedule->amount,
                    'date' => $schedule->next_payment_date,
                    'category' => $schedule->category->name,
                    'color' => $schedule->category->color,
                ]);
            }
        });

        // Add income payments from contract payments
        ContractPayment::with('contract')
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(30))
            ->get()
            ->each(function($payment) use ($upcomingPayments) {
                $upcomingPayments->push([
                    'type' => 'income',
                    'name' => $payment->name,
                    'amount' => $payment->amount,
                    'date' => $payment->due_date,
                    'source' => $payment->contract->client_name,
                ]);
            });

        $upcomingPayments = $upcomingPayments->sortBy('date');

        // Chart data for cash flow
        $cashFlowData = [
            'income' => $projections->pluck('projected_income')->toArray(),
            'expenses' => $projections->pluck('projected_expenses')->toArray(),
            'netFlow' => $projections->pluck('net_flow')->toArray(),
            'periods' => $projections->pluck('projection_date')->map(fn($date) => $date->format('M Y'))->toArray(),
        ];

        // Expense categories breakdown - using ACTUAL paid expenses this month
        $categoryData = ExpenseCategory::active()
            ->whereNull('parent_id') // Only top-level categories
            ->get()
            ->map(function($category) use ($currentMonthStart, $currentMonthEnd) {
                // Get actual paid expenses for this category this month
                $actualAmount = ExpenseSchedule::where('payment_status', 'paid')
                    ->where('category_id', $category->id)
                    ->where('paid_date', '>=', $currentMonthStart)
                    ->where('paid_date', '<=', $currentMonthEnd)
                    ->sum('paid_amount');

                // Also include subcategories
                $subcategoryIds = $category->subcategories()->pluck('id');
                if ($subcategoryIds->isNotEmpty()) {
                    $actualAmount += ExpenseSchedule::where('payment_status', 'paid')
                        ->whereIn('category_id', $subcategoryIds)
                        ->where('paid_date', '>=', $currentMonthStart)
                        ->where('paid_date', '<=', $currentMonthEnd)
                        ->sum('paid_amount');
                }

                return [
                    'name' => $category->name,
                    'amount' => $actualAmount,
                    'color' => $category->color,
                ];
            })
            ->filter(fn($cat) => $cat['amount'] > 0);

        $expenseCategories = [
            'names' => $categoryData->pluck('name')->toArray(),
            'amounts' => $categoryData->pluck('amount')->toArray(),
            'colors' => $categoryData->pluck('color')->toArray(),
        ];

        // Recent PAID expenses (for activity feed)
        $recentExpenses = ExpenseSchedule::where('payment_status', 'paid')
            ->with(['category', 'paidFromAccount'])
            ->orderByDesc('paid_date')
            ->take(5)
            ->get();

        // Recent contracts
        $recentContracts = Contract::active()->latest()->take(5)->get();

        // Identify deficit periods
        $deficitPeriods = $projections->where('net_flow', '<', 0);

        // Additional dashboard variables
        $selectedPeriod = 'monthly';
        $currentPeriodLabel = $companySettings->getPeriodLabel();
        $fiscalYearLabel = $companySettings->getFiscalYearLabel();

        return view('accounting::dashboard.index', compact(
            'monthlyIncome',
            'monthlyExpenses',
            'netCashFlow',
            'activeContracts',
            'totalContractValue',
            'upcomingPayments',
            'cashFlowData',
            'expenseCategories',
            'recentContracts',
            'recentExpenses',
            'deficitPeriods',
            'selectedPeriod',
            'incomeGrowth',
            'expenseGrowth',
            'ytdExpenses',
            'ytdIncome',
            'accountsSummary',
            'currentPeriodLabel',
            'fiscalYearLabel'
        ));
    }

    /**
     * Display cash flow reports.
     */
    public function reports(Request $request)
    {
        // Check authorization
        if (!auth()->user()->can('view-cash-flow-reports')) {
            abort(403, 'Unauthorized to view cash flow reports.');
        }

        // Handle export requests
        if ($request->has('export')) {
            return $this->exportReport($request);
        }

        // Parse parameters
        $selectedPeriod = $request->input('period', 'monthly');
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->startOfYear();
        $duration = (int) $request->input('duration', 12);
        $reportType = $request->input('type', 'projection');

        // Calculate end date based on duration
        $endDate = match($selectedPeriod) {
            'weekly' => $startDate->copy()->addWeeks($duration),
            'monthly' => $startDate->copy()->addMonths($duration),
            'quarterly' => $startDate->copy()->addMonths($duration * 3),
            default => $startDate->copy()->addMonths($duration),
        };

        // Get starting balance from accounts
        $totalAccountBalance = Account::active()->sum('current_balance');

        // Generate projections
        $projections = $this->projectionService
            ->setStartingBalance($totalAccountBalance)
            ->generateProjections($startDate, $endDate, $selectedPeriod);

        // Prepare projection data for tables with actual vs expected breakdown
        $projectionData = $projections->map(function($projection, $index) use ($selectedPeriod) {
            return [
                'period' => $selectedPeriod === 'weekly'
                    ? 'Week ' . ($index + 1)
                    : ($selectedPeriod === 'quarterly' ? 'Q' . ($index + 1) : $projection['projection_date']->format('M Y')),
                'dates' => $selectedPeriod === 'monthly'
                    ? $projection['projection_date']->format('M 1 - ') . $projection['projection_date']->copy()->endOfMonth()->format('M j, Y')
                    : null,
                'period_type' => $projection['period_type_label'] ?? 'unknown',

                // Income breakdown
                'actual_income' => $projection['actual_income'] ?? 0,
                'expected_income' => $projection['expected_income'] ?? 0,
                'income' => $projection['projected_income'],

                // Contracts breakdown
                'actual_contracts' => $projection['actual_contracts'] ?? 0,
                'expected_contracts' => $projection['expected_contracts'] ?? 0,
                'contracts' => $projection['total_contracts'] ?? 0,

                // Expenses breakdown
                'actual_expenses' => $projection['actual_expenses'] ?? 0,
                'scheduled_expenses' => $projection['scheduled_expenses'] ?? 0,
                'expenses' => $projection['projected_expenses'],

                'netFlow' => $projection['net_flow'],
            ];
        });

        // Calculate summary data with actual vs expected breakdown
        $summaryData = [
            'totalIncome' => $projections->sum('projected_income'),
            'actualIncome' => $projections->sum('actual_income'),
            'expectedIncome' => $projections->sum('expected_income'),
            'totalExpenses' => $projections->sum('projected_expenses'),
            'actualExpenses' => $projections->sum('actual_expenses'),
            'scheduledExpenses' => $projections->sum('scheduled_expenses'),
            'totalContracts' => $projections->sum('total_contracts'),
            'actualContracts' => $projections->sum('actual_contracts'),
            'expectedContracts' => $projections->sum('expected_contracts'),
            'netCashFlow' => $projections->sum('net_flow'),
            'avgMonthly' => $projections->avg('net_flow'),
        ];

        // Get deficit periods
        $deficitPeriods = $projectionData->filter(fn($p) => $p['netFlow'] < 0)
            ->map(function($deficit) {
                $deficit['runningBalance'] = 0; // Calculate running balance here if needed
                return $deficit;
            });

        // Chart data with actual vs expected breakdown
        $chartData = [
            'actualIncome' => $projections->pluck('actual_income')->toArray(),
            'expectedIncome' => $projections->pluck('expected_income')->toArray(),
            'income' => $projections->pluck('projected_income')->toArray(),
            'actualExpenses' => $projections->pluck('actual_expenses')->toArray(),
            'scheduledExpenses' => $projections->pluck('scheduled_expenses')->toArray(),
            'expenses' => $projections->pluck('projected_expenses')->toArray(),
            'netFlow' => $projections->pluck('net_flow')->toArray(),
            'periods' => $projectionData->pluck('period')->toArray(),
            'periodTypes' => $projections->pluck('period_type_label')->toArray(),
        ];

        // Breakdowns
        $contractsForBreakdown = Contract::active()->get();

        $incomeBreakdown = [
            'labels' => $contractsForBreakdown->pluck('client_name')->toArray(),
            'amounts' => $contractsForBreakdown->pluck('total_amount')->toArray(),
        ];

        $expenseBreakdown = [
            'labels' => ExpenseCategory::active()->pluck('name')->toArray(),
            'amounts' => ExpenseCategory::active()->pluck('monthly_amount')->toArray(),
            'colors' => ExpenseCategory::active()->pluck('color')->toArray(),
        ];

        // Get upcoming payments for schedule tab
        $upcomingPayments = collect();

        // Add expense payments
        ExpenseSchedule::active()->with('category')->get()->each(function($schedule) use ($upcomingPayments) {
            if ($schedule->next_payment_date && $schedule->next_payment_date <= now()->addMonths(3)) {
                $upcomingPayments->push([
                    'type' => 'expense',
                    'name' => $schedule->name,
                    'description' => $schedule->description,
                    'amount' => $schedule->amount,
                    'date' => $schedule->next_payment_date,
                    'category' => $schedule->category->name,
                    'color' => $schedule->category->color,
                ]);
            }
        });

        // Add income payments from contract payments
        ContractPayment::with('contract')
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addMonths(3))
            ->get()
            ->each(function($payment) use ($upcomingPayments) {
                $upcomingPayments->push([
                    'type' => 'income',
                    'name' => $payment->name,
                    'description' => $payment->description ?? '',
                    'amount' => $payment->amount,
                    'date' => $payment->due_date,
                    'source' => $payment->contract->client_name,
                ]);
            });

        $upcomingPayments = $upcomingPayments->sortBy('date');

        return view('accounting::reports.index', compact(
            'selectedPeriod',
            'startDate',
            'duration',
            'reportType',
            'projectionData',
            'summaryData',
            'deficitPeriods',
            'chartData',
            'incomeBreakdown',
            'expenseBreakdown',
            'upcomingPayments'
        ));
    }

    /**
     * Export report in various formats
     */
    private function exportReport(Request $request)
    {
        // Check export permission
        if (!auth()->user()->can('export-financial-reports')) {
            abort(403, 'Unauthorized to export financial reports.');
        }

        $format = $request->input('export');

        // Get the same data as reports method
        $selectedPeriod = $request->input('period', 'monthly');
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->startOfYear();
        $duration = (int) $request->input('duration', 12);

        // Calculate end date based on duration
        $endDate = match($selectedPeriod) {
            'weekly' => $startDate->copy()->addWeeks($duration),
            'monthly' => $startDate->copy()->addMonths($duration),
            'quarterly' => $startDate->copy()->addMonths($duration * 3),
            default => $startDate->copy()->addMonths($duration),
        };

        // Get starting balance from accounts
        $totalAccountBalance = Account::active()->sum('current_balance');

        $projections = $this->projectionService
            ->setStartingBalance($totalAccountBalance)
            ->generateProjections($startDate, $endDate, $selectedPeriod);

        switch ($format) {
            case 'pdf':
                return $this->exportPdf($projections, $selectedPeriod, $startDate, $endDate);
            case 'excel':
                return $this->exportExcel($projections, $selectedPeriod, $startDate, $endDate);
            case 'csv':
                return $this->exportCsv($projections, $selectedPeriod, $startDate, $endDate);
            default:
                return redirect()->back()->with('error', 'Invalid export format');
        }
    }

    /**
     * Export cash flow report as PDF
     */
    private function exportPdf($projections, $selectedPeriod, $startDate, $endDate)
    {
        $data = [
            'projections' => $projections,
            'selectedPeriod' => $selectedPeriod,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now(),
            'totalIncome' => $projections->sum('projected_income'),
            'totalExpenses' => $projections->sum('projected_expenses'),
            'netCashFlow' => $projections->sum('net_flow'),
        ];

        $pdf = Pdf::loadView('accounting::exports.pdf-report', $data);
        $filename = 'cash-flow-report-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export cash flow report as Excel
     */
    private function exportExcel($projections, $selectedPeriod, $startDate, $endDate)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setTitle('Cash Flow Report');
        $sheet->setCellValue('A1', 'Cash Flow Report - ' . ucfirst($selectedPeriod));
        $sheet->setCellValue('A2', 'Generated: ' . now()->format('F j, Y g:i A'));
        $sheet->setCellValue('A3', 'Period: ' . $startDate->format('M j, Y') . ' to ' . $endDate->format('M j, Y'));

        // Column headers
        $sheet->setCellValue('A5', 'Period');
        $sheet->setCellValue('B5', 'Income');
        $sheet->setCellValue('C5', 'Expenses');
        $sheet->setCellValue('D5', 'Net Flow');
        $sheet->setCellValue('E5', 'Running Balance');

        // Data rows
        $row = 6;
        $runningBalance = 0;
        foreach ($projections as $index => $projection) {
            $periodLabel = $selectedPeriod === 'weekly'
                ? 'Week ' . ($index + 1)
                : ($selectedPeriod === 'quarterly' ? 'Q' . ($index + 1) : $projection['projection_date']->format('M Y'));

            $runningBalance += $projection['net_flow'];

            $sheet->setCellValue('A' . $row, $periodLabel);
            $sheet->setCellValue('B' . $row, $projection['projected_income']);
            $sheet->setCellValue('C' . $row, $projection['projected_expenses']);
            $sheet->setCellValue('D' . $row, $projection['net_flow']);
            $sheet->setCellValue('E' . $row, $runningBalance);
            $row++;
        }

        // Summary totals
        $row += 2;
        $sheet->setCellValue('A' . $row, 'TOTALS:');
        $sheet->setCellValue('B' . $row, $projections->sum('projected_income'));
        $sheet->setCellValue('C' . $row, $projections->sum('projected_expenses'));
        $sheet->setCellValue('D' . $row, $projections->sum('net_flow'));

        // Format as currency
        $sheet->getStyle('B5:E' . $row)->getNumberFormat()->setFormatCode('#,##0.00" EGP"');

        $writer = new Xlsx($spreadsheet);
        $filename = 'cash-flow-report-' . now()->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'cash_flow_report');

        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend();
    }

    /**
     * Export cash flow report as CSV
     */
    private function exportCsv($projections, $selectedPeriod, $startDate, $endDate)
    {
        $csv = Writer::createFromString('');

        // Add headers
        $csv->insertOne(['Cash Flow Report - ' . ucfirst($selectedPeriod)]);
        $csv->insertOne(['Generated: ' . now()->format('F j, Y g:i A')]);
        $csv->insertOne(['Period: ' . $startDate->format('M j, Y') . ' to ' . $endDate->format('M j, Y')]);
        $csv->insertOne([]);
        $csv->insertOne(['Period', 'Income', 'Expenses', 'Net Flow', 'Running Balance']);

        // Add data
        $runningBalance = 0;
        foreach ($projections as $index => $projection) {
            $periodLabel = $selectedPeriod === 'weekly'
                ? 'Week ' . ($index + 1)
                : ($selectedPeriod === 'quarterly' ? 'Q' . ($index + 1) : $projection['projection_date']->format('M Y'));

            $runningBalance += $projection['net_flow'];

            $csv->insertOne([
                $periodLabel,
                $projection['projected_income'],
                $projection['projected_expenses'],
                $projection['net_flow'],
                $runningBalance
            ]);
        }

        // Add totals
        $csv->insertOne([]);
        $csv->insertOne([
            'TOTALS:',
            $projections->sum('projected_income'),
            $projections->sum('projected_expenses'),
            $projections->sum('net_flow'),
            ''
        ]);

        $filename = 'cash-flow-report-' . now()->format('Y-m-d') . '.csv';

        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
