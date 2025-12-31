<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectReport;
use Modules\Project\Services\ProjectReportService;
use Modules\HR\Models\Employee;

class ProjectReportController extends Controller
{
    protected ProjectReportService $reportService;

    public function __construct(ProjectReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display a listing of saved reports.
     */
    public function index(Request $request): View
    {
        if (!Gate::allows('manage-project-reports')) {
            abort(403, 'You do not have permission to view project reports.');
        }

        $query = ProjectReport::with('createdBy')
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->filled('from')) {
            $query->where('start_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('end_date', '<=', $request->to);
        }

        $reports = $query->paginate(20);

        return view('project::reports.index', [
            'reports' => $reports,
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    /**
     * Show the form for generating a new report (date picker).
     */
    public function create(): View
    {
        if (!Gate::allows('manage-project-reports')) {
            abort(403, 'You do not have permission to create project reports.');
        }

        // Get projects that need monthly reports
        $reportableProjects = Project::needsMonthlyReport()->orderBy('name')->get();

        return view('project::reports.create', [
            'reportableProjects' => $reportableProjects,
        ]);
    }

    /**
     * Generate a report preview with editable rates.
     */
    public function generate(Request $request): View
    {
        if (!Gate::allows('manage-project-reports')) {
            abort(403, 'You do not have permission to generate project reports.');
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $reportData = $this->reportService->generateReportPreview($startDate, $endDate);

        // Get all active projects for the dropdown (to add manually)
        $allProjects = Project::where('is_active', true)->orderBy('name')->get();

        // Get all active employees for the dropdown
        $allEmployees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('project::reports.generate', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'allProjects' => $allProjects,
            'allEmployees' => $allEmployees,
            'canEditHours' => auth()->user()->hasRole('super-admin'),
        ]);
    }

    /**
     * Store the generated report with rates.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!Gate::allows('manage-project-reports')) {
            abort(403, 'You do not have permission to save project reports.');
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'rates' => 'array',
            'rates.*' => 'numeric|min:0',
            'hours' => 'array',
            'hours.*' => 'numeric|min:0',
            'teams' => 'array',
            'teams.*' => 'nullable|string',
            'custom_projects' => 'array',
            'custom_projects.*.name' => 'required_with:custom_projects|string',
            'custom_projects.*.project_id' => 'nullable|exists:projects,id',
            'custom_projects.*.employees' => 'array',
            'custom_projects.*.employees.*.employee_id' => 'nullable',
            'custom_projects.*.employees.*.name' => 'required_with:custom_projects.*.employees|string',
            'custom_projects.*.employees.*.hours' => 'required_with:custom_projects.*.employees|numeric|min:0',
            'custom_projects.*.employees.*.rate' => 'required_with:custom_projects.*.employees|numeric|min:0',
            'custom_projects.*.employees.*.team' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Generate fresh report data
        $reportData = $this->reportService->generateReportPreview($startDate, $endDate);

        // Apply custom hours if provided (super admin only)
        $customHours = $validated['hours'] ?? [];

        // Apply rates, hours, teams, and custom projects, then save
        $report = $this->reportService->saveReport(
            $reportData,
            $validated['rates'] ?? [],
            auth()->id(),
            $customHours,
            $validated['custom_projects'] ?? [],
            $validated['teams'] ?? []
        );

        return redirect()
            ->route('projects.reports.show', $report)
            ->with('success', 'Report saved successfully.');
    }

    /**
     * Display a saved report.
     */
    public function show(ProjectReport $report): View
    {
        if (!Gate::allows('manage-project-reports')) {
            abort(403, 'You do not have permission to view project reports.');
        }

        return view('project::reports.show', [
            'report' => $report,
        ]);
    }

    /**
     * Export report as PDF.
     */
    public function exportPdf(ProjectReport $report)
    {
        if (!Gate::allows('manage-project-reports')) {
            abort(403, 'You do not have permission to export project reports.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('project::reports.pdf', [
            'report' => $report,
            'isPdf' => true,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'project-report-' . $report->start_date->format('Y-m') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export report as Excel.
     */
    public function exportExcel(ProjectReport $report)
    {
        if (!Gate::allows('manage-project-reports')) {
            abort(403, 'You do not have permission to export project reports.');
        }

        // Create Excel export
        $filename = 'project-report-' . $report->start_date->format('Y-m') . '.xlsx';

        return response()->streamDownload(function () use ($report) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header
            $sheet->setCellValue('A1', 'Project Report: ' . $report->start_date->format('M d, Y') . ' - ' . $report->end_date->format('M d, Y'));
            $sheet->mergeCells('A1:F1');

            // Column headers
            $sheet->setCellValue('A3', 'Project');
            $sheet->setCellValue('B3', 'Employee');
            $sheet->setCellValue('C3', 'Team');
            $sheet->setCellValue('D3', 'Hours');
            $sheet->setCellValue('E3', 'Rate (EGP/hr)');
            $sheet->setCellValue('F3', 'Amount (EGP)');

            // Bold headers
            $sheet->getStyle('A3:F3')->getFont()->setBold(true);

            // Track team totals
            $teamTotals = [];
            $teams = \Modules\HR\Models\Employee::TEAMS;
            foreach ($teams as $teamKey => $teamLabel) {
                $teamTotals[$teamKey] = ['hours' => 0, 'amount' => 0, 'label' => $teamLabel];
            }
            $teamTotals[''] = ['hours' => 0, 'amount' => 0, 'label' => 'Unassigned'];

            $row = 4;
            foreach ($report->projects_data as $project) {
                $projectStartRow = $row;
                foreach ($project['employees'] as $employee) {
                    $sheet->setCellValue('A' . $row, $project['project_name']);
                    $sheet->setCellValue('B' . $row, $employee['employee_name']);
                    $sheet->setCellValue('C' . $row, $employee['team'] ?? '');
                    $sheet->setCellValue('D' . $row, $employee['hours']);
                    $sheet->setCellValue('E' . $row, $employee['rate']);
                    $sheet->setCellValue('F' . $row, $employee['amount']);

                    // Track team totals
                    $team = $employee['team'] ?? '';
                    if (isset($teamTotals[$team])) {
                        $teamTotals[$team]['hours'] += $employee['hours'];
                        $teamTotals[$team]['amount'] += $employee['amount'];
                    }

                    $row++;
                }

                // Project subtotal with average rate
                $projectAvgRate = $project['total_hours'] > 0 ? $project['total_amount'] / $project['total_hours'] : 0;
                $sheet->setCellValue('A' . $row, '');
                $sheet->setCellValue('B' . $row, $project['project_name'] . ' Total');
                $sheet->setCellValue('C' . $row, '');
                $sheet->setCellValue('D' . $row, $project['total_hours']);
                $sheet->setCellValue('E' . $row, $projectAvgRate); // Average rate
                $sheet->setCellValue('F' . $row, $project['total_amount']);
                $sheet->getStyle('B' . $row . ':F' . $row)->getFont()->setBold(true);
                $row++;
            }

            // Team summary section
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Summary by Team');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;

            $sheet->setCellValue('A' . $row, 'Team');
            $sheet->setCellValue('B' . $row, 'Hours');
            $sheet->setCellValue('C' . $row, 'Amount (EGP)');
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
            $row++;

            foreach ($teamTotals as $teamKey => $teamData) {
                if ($teamData['hours'] > 0 || $teamKey === '') {
                    $sheet->setCellValue('A' . $row, $teamData['label']);
                    $sheet->setCellValue('B' . $row, $teamData['hours']);
                    $sheet->setCellValue('C' . $row, $teamData['amount']);
                    $row++;
                }
            }

            // Grand total with average rate
            $grandAvgRate = $report->total_hours > 0 ? $report->total_amount / $report->total_hours : 0;
            $row += 2;
            $sheet->setCellValue('B' . $row, 'Grand Total');
            $sheet->setCellValue('D' . $row, $report->total_hours);
            $sheet->setCellValue('E' . $row, $grandAvgRate); // Average rate
            $sheet->setCellValue('F' . $row, $report->total_amount);
            $sheet->getStyle('B' . $row . ':F' . $row)->getFont()->setBold(true);

            // Auto-size columns
            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Format currency columns
            $sheet->getStyle('E4:E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('F4:F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('D4:D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Edit an existing report - refresh hours from Jira but keep rates.
     */
    public function edit(ProjectReport $report): View
    {
        if (!Gate::allows('manage-project-reports')) {
            abort(403, 'You do not have permission to edit project reports.');
        }

        $startDate = $report->start_date;
        $endDate = $report->end_date;

        // Generate fresh report data with updated hours from Jira
        $reportData = $this->reportService->generateReportPreview($startDate, $endDate);

        // Extract saved rates from the existing report
        $savedRates = [];
        $savedHours = [];
        if ($report->projects_data) {
            foreach ($report->projects_data as $project) {
                foreach ($project['employees'] as $employee) {
                    $key = $project['project_id'] . '_' . $employee['employee_id'];
                    $savedRates[$key] = $employee['rate'];
                    // Keep custom hours if they were manually set (for custom projects)
                    if (!empty($project['is_custom'])) {
                        $savedHours[$key] = $employee['hours'];
                    }
                }
            }
        }

        // Apply saved rates to the fresh data
        foreach ($reportData['projects'] as &$project) {
            foreach ($project['employees'] as &$employee) {
                $key = $project['project_id'] . '_' . $employee['employee_id'];
                if (isset($savedRates[$key])) {
                    $employee['rate'] = $savedRates[$key];
                    $employee['amount'] = $employee['hours'] * $employee['rate'];
                }
            }
            // Recalculate project totals
            $project['total_amount'] = array_sum(array_column($project['employees'], 'amount'));
        }

        // Recalculate grand total
        $reportData['total_amount'] = array_sum(array_column($reportData['projects'], 'total_amount'));

        // Get all active projects for the dropdown (to add manually)
        $allProjects = Project::where('is_active', true)->orderBy('name')->get();

        // Get all active employees for the dropdown
        $allEmployees = Employee::where('status', 'active')->orderBy('name')->get();

        // Extract custom projects from saved report
        $customProjects = [];
        if ($report->projects_data) {
            foreach ($report->projects_data as $project) {
                if (!empty($project['is_custom'])) {
                    $customProjects[] = $project;
                }
            }
        }

        return view('project::reports.edit', [
            'report' => $report,
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'allProjects' => $allProjects,
            'allEmployees' => $allEmployees,
            'canEditHours' => auth()->user()->hasRole('super-admin'),
            'customProjects' => $customProjects,
        ]);
    }

    /**
     * Update an existing report with refreshed hours and rates.
     */
    public function update(Request $request, ProjectReport $report): RedirectResponse
    {
        if (!Gate::allows('manage-project-reports')) {
            abort(403, 'You do not have permission to update project reports.');
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'rates' => 'array',
            'rates.*' => 'numeric|min:0',
            'hours' => 'array',
            'hours.*' => 'numeric|min:0',
            'teams' => 'array',
            'teams.*' => 'nullable|string',
            'custom_projects' => 'array',
            'custom_projects.*.name' => 'required_with:custom_projects|string',
            'custom_projects.*.project_id' => 'nullable|exists:projects,id',
            'custom_projects.*.employees' => 'array',
            'custom_projects.*.employees.*.employee_id' => 'nullable',
            'custom_projects.*.employees.*.name' => 'required_with:custom_projects.*.employees|string',
            'custom_projects.*.employees.*.hours' => 'required_with:custom_projects.*.employees|numeric|min:0',
            'custom_projects.*.employees.*.rate' => 'required_with:custom_projects.*.employees|numeric|min:0',
            'custom_projects.*.employees.*.team' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Generate fresh report data
        $reportData = $this->reportService->generateReportPreview($startDate, $endDate);

        // Apply custom hours if provided (super admin only)
        $customHours = $validated['hours'] ?? [];

        // Update the report using the service
        $updatedReport = $this->reportService->updateReport(
            $report,
            $reportData,
            $validated['rates'] ?? [],
            $customHours,
            $validated['custom_projects'] ?? [],
            $validated['teams'] ?? []
        );

        return redirect()
            ->route('projects.reports.show', $updatedReport)
            ->with('success', 'Report updated successfully. Hours have been refreshed from Jira.');
    }

    /**
     * Delete a saved report.
     */
    public function destroy(ProjectReport $report): RedirectResponse
    {
        if (!Gate::allows('manage-project-reports')) {
            abort(403, 'You do not have permission to delete project reports.');
        }

        $report->delete();

        return redirect()
            ->route('projects.reports.index')
            ->with('success', 'Report deleted successfully.');
    }
}
