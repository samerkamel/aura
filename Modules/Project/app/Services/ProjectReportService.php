<?php

namespace Modules\Project\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\HR\Models\Employee;
use Modules\Payroll\Models\JiraWorklog;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectReport;
use Modules\Project\Models\ProjectReportLine;

class ProjectReportService
{
    /**
     * Generate a report preview for the given date range.
     * Returns data structure with projects, employees, hours, and rates.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateReportPreview(Carbon $startDate, Carbon $endDate): array
    {
        // Get projects that need monthly reports
        $projects = Project::needsMonthlyReport()
            ->orderBy('name')
            ->get();

        $reportData = [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'projects' => [],
            'total_hours' => 0,
            'total_amount' => 0,
        ];

        $employeeRates = []; // Track rates across projects for auto-fill

        foreach ($projects as $project) {
            $projectData = $this->getProjectEmployeeHours($project, $startDate, $endDate, $employeeRates);

            if (!empty($projectData['employees'])) {
                $reportData['projects'][] = $projectData;
                $reportData['total_hours'] += $projectData['total_hours'];
            }
        }

        return $reportData;
    }

    /**
     * Get employee hours for a specific project within the date range.
     *
     * @param Project $project
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array &$employeeRates Reference to track rates across projects
     * @return array
     */
    protected function getProjectEmployeeHours(Project $project, Carbon $startDate, Carbon $endDate, array &$employeeRates): array
    {
        // Get worklogs for this project's issue keys
        $worklogs = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%')
            ->whereBetween('worklog_started', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('employee_id, SUM(time_spent_hours) as total_hours')
            ->groupBy('employee_id')
            ->get();

        if ($worklogs->isEmpty()) {
            return [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'project_code' => $project->code,
                'employees' => [],
                'total_hours' => 0,
                'total_amount' => 0,
            ];
        }

        // Get employee details
        $employeeIds = $worklogs->pluck('employee_id')->toArray();
        $employees = Employee::whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');

        $projectEmployees = [];
        $projectTotalHours = 0;
        $projectTotalAmount = 0;

        // Use middle of the period for rate lookup
        $effectiveDate = $startDate->copy()->addDays($startDate->diffInDays($endDate) / 2);

        foreach ($worklogs as $worklog) {
            $employee = $employees->get($worklog->employee_id);
            if (!$employee) {
                continue;
            }

            // Determine rate: use tracked rate if employee seen before, otherwise use effective hourly rate
            $rate = 0;
            if (isset($employeeRates[$employee->id])) {
                $rate = $employeeRates[$employee->id];
            } else {
                // Get the effective hourly rate at the time of the report period
                $rate = (float) ($employee->getHourlyRateAt($effectiveDate) ?? $employee->hourly_rate ?? 0);
                $employeeRates[$employee->id] = $rate;
            }

            $hours = (float) $worklog->total_hours;
            $amount = $hours * $rate;

            $projectEmployees[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'team' => $employee->team,
                'hours' => $hours,
                'rate' => $rate,
                'amount' => $amount,
            ];

            $projectTotalHours += $hours;
            $projectTotalAmount += $amount;
        }

        // Sort by employee name
        usort($projectEmployees, fn($a, $b) => strcmp($a['employee_name'], $b['employee_name']));

        return [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'project_code' => $project->code,
            'employees' => $projectEmployees,
            'total_hours' => $projectTotalHours,
            'total_amount' => $projectTotalAmount,
        ];
    }

    /**
     * Save a report with the given data and rates.
     *
     * @param array $reportData The report preview data
     * @param array $rates Array of rates keyed by project_id_employee_id
     * @param int $userId The user creating the report
     * @param array $customHours Optional custom hours keyed by project_id_employee_id
     * @param array $customProjects Optional array of custom projects to add
     * @param array $teams Optional teams keyed by project_id_employee_id
     * @return ProjectReport
     */
    public function saveReport(array $reportData, array $rates, int $userId, array $customHours = [], array $customProjects = [], array $teams = []): ProjectReport
    {
        // Calculate totals with updated rates and hours
        $totalHours = 0;
        $totalAmount = 0;
        $projectsSnapshot = [];

        foreach ($reportData['projects'] as $project) {
            $projectSnapshot = [
                'project_id' => $project['project_id'],
                'project_name' => $project['project_name'],
                'project_code' => $project['project_code'],
                'employees' => [],
                'total_hours' => 0,
                'total_amount' => 0,
            ];

            foreach ($project['employees'] as $employee) {
                $key = $project['project_id'] . '_' . $employee['employee_id'];

                // Apply custom values if provided
                $hours = isset($customHours[$key]) ? (float) $customHours[$key] : $employee['hours'];
                $rate = isset($rates[$key]) ? (float) $rates[$key] : $employee['rate'];
                $team = isset($teams[$key]) ? $teams[$key] : ($employee['team'] ?? null);
                $amount = $hours * $rate;

                $projectSnapshot['employees'][] = [
                    'employee_id' => $employee['employee_id'],
                    'employee_name' => $employee['employee_name'],
                    'team' => $team,
                    'hours' => $hours,
                    'rate' => $rate,
                    'amount' => $amount,
                ];

                $projectSnapshot['total_hours'] += $hours;
                $projectSnapshot['total_amount'] += $amount;

                $totalHours += $hours;
                $totalAmount += $amount;
            }

            $projectsSnapshot[] = $projectSnapshot;
        }

        // Add custom projects
        foreach ($customProjects as $customProject) {
            if (empty($customProject['employees'])) {
                continue;
            }

            // Get project info if project_id is provided
            $projectId = $customProject['project_id'] ?? null;
            $projectName = $customProject['name'];
            $projectCode = 'CUSTOM';

            if ($projectId) {
                $existingProject = Project::find($projectId);
                if ($existingProject) {
                    $projectName = $existingProject->name;
                    $projectCode = $existingProject->code;
                }
            }

            $customProjectSnapshot = [
                'project_id' => $projectId,
                'project_name' => $projectName,
                'project_code' => $projectCode,
                'employees' => [],
                'total_hours' => 0,
                'total_amount' => 0,
                'is_custom' => true,
            ];

            foreach ($customProject['employees'] as $emp) {
                if (empty($emp['hours']) && empty($emp['rate'])) {
                    continue;
                }

                $hours = (float) ($emp['hours'] ?? 0);
                $rate = (float) ($emp['rate'] ?? 0);
                $amount = $hours * $rate;

                // Get employee details
                $employeeName = $emp['name'] ?? 'Unknown';
                $employeeId = $emp['employee_id'] ?? null;
                $employeeTeam = $emp['team'] ?? null;

                if ($employeeId) {
                    $employee = Employee::find($employeeId);
                    if ($employee) {
                        $employeeName = $employee->name;
                        if (!$employeeTeam) {
                            $employeeTeam = $employee->team;
                        }
                    }
                }

                $customProjectSnapshot['employees'][] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeName,
                    'team' => $employeeTeam,
                    'hours' => $hours,
                    'rate' => $rate,
                    'amount' => $amount,
                ];

                $customProjectSnapshot['total_hours'] += $hours;
                $customProjectSnapshot['total_amount'] += $amount;

                $totalHours += $hours;
                $totalAmount += $amount;
            }

            if (!empty($customProjectSnapshot['employees'])) {
                $projectsSnapshot[] = $customProjectSnapshot;
            }
        }

        // Create the report
        $report = ProjectReport::create([
            'name' => 'Project Report ' . Carbon::parse($reportData['start_date'])->format('M Y'),
            'start_date' => $reportData['start_date'],
            'end_date' => $reportData['end_date'],
            'total_hours' => $totalHours,
            'total_amount' => $totalAmount,
            'projects_data' => $projectsSnapshot,
            'created_by' => $userId,
        ]);

        // Create report lines (only for entries with valid project_id and employee_id)
        foreach ($projectsSnapshot as $project) {
            // Skip custom projects without a valid project_id
            if (empty($project['project_id'])) {
                continue;
            }

            foreach ($project['employees'] as $employee) {
                // Skip custom employees without a valid employee_id
                if (empty($employee['employee_id'])) {
                    continue;
                }

                ProjectReportLine::create([
                    'project_report_id' => $report->id,
                    'project_id' => $project['project_id'],
                    'employee_id' => $employee['employee_id'],
                    'hours' => $employee['hours'],
                    'rate' => $employee['rate'],
                    'amount' => $employee['amount'],
                ]);
            }
        }

        return $report;
    }

    /**
     * Update an existing report with refreshed data.
     *
     * @param ProjectReport $report The existing report to update
     * @param array $reportData The fresh report preview data
     * @param array $rates Array of rates keyed by project_id_employee_id
     * @param array $customHours Optional custom hours keyed by project_id_employee_id
     * @param array $customProjects Optional array of custom projects to add
     * @param array $teams Optional teams keyed by project_id_employee_id
     * @return ProjectReport
     */
    public function updateReport(ProjectReport $report, array $reportData, array $rates, array $customHours = [], array $customProjects = [], array $teams = []): ProjectReport
    {
        // Delete existing report lines
        $report->lines()->delete();

        // Calculate totals with updated rates and hours
        $totalHours = 0;
        $totalAmount = 0;
        $projectsSnapshot = [];

        foreach ($reportData['projects'] as $project) {
            $projectSnapshot = [
                'project_id' => $project['project_id'],
                'project_name' => $project['project_name'],
                'project_code' => $project['project_code'],
                'employees' => [],
                'total_hours' => 0,
                'total_amount' => 0,
            ];

            foreach ($project['employees'] as $employee) {
                $key = $project['project_id'] . '_' . $employee['employee_id'];

                // Apply custom values if provided
                $hours = isset($customHours[$key]) ? (float) $customHours[$key] : $employee['hours'];
                $rate = isset($rates[$key]) ? (float) $rates[$key] : $employee['rate'];
                $team = isset($teams[$key]) ? $teams[$key] : ($employee['team'] ?? null);
                $amount = $hours * $rate;

                $projectSnapshot['employees'][] = [
                    'employee_id' => $employee['employee_id'],
                    'employee_name' => $employee['employee_name'],
                    'team' => $team,
                    'hours' => $hours,
                    'rate' => $rate,
                    'amount' => $amount,
                ];

                $projectSnapshot['total_hours'] += $hours;
                $projectSnapshot['total_amount'] += $amount;

                $totalHours += $hours;
                $totalAmount += $amount;
            }

            $projectsSnapshot[] = $projectSnapshot;
        }

        // Add custom projects
        foreach ($customProjects as $customProject) {
            if (empty($customProject['employees'])) {
                continue;
            }

            // Get project info if project_id is provided
            $projectId = $customProject['project_id'] ?? null;
            $projectName = $customProject['name'];
            $projectCode = 'CUSTOM';

            if ($projectId) {
                $existingProject = Project::find($projectId);
                if ($existingProject) {
                    $projectName = $existingProject->name;
                    $projectCode = $existingProject->code;
                }
            }

            $customProjectSnapshot = [
                'project_id' => $projectId,
                'project_name' => $projectName,
                'project_code' => $projectCode,
                'employees' => [],
                'total_hours' => 0,
                'total_amount' => 0,
                'is_custom' => true,
            ];

            foreach ($customProject['employees'] as $emp) {
                if (empty($emp['hours']) && empty($emp['rate'])) {
                    continue;
                }

                $hours = (float) ($emp['hours'] ?? 0);
                $rate = (float) ($emp['rate'] ?? 0);
                $amount = $hours * $rate;

                // Get employee details
                $employeeName = $emp['name'] ?? 'Unknown';
                $employeeId = $emp['employee_id'] ?? null;
                $employeeTeam = $emp['team'] ?? null;

                if ($employeeId) {
                    $employee = Employee::find($employeeId);
                    if ($employee) {
                        $employeeName = $employee->name;
                        if (!$employeeTeam) {
                            $employeeTeam = $employee->team;
                        }
                    }
                }

                $customProjectSnapshot['employees'][] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeName,
                    'team' => $employeeTeam,
                    'hours' => $hours,
                    'rate' => $rate,
                    'amount' => $amount,
                ];

                $customProjectSnapshot['total_hours'] += $hours;
                $customProjectSnapshot['total_amount'] += $amount;

                $totalHours += $hours;
                $totalAmount += $amount;
            }

            if (!empty($customProjectSnapshot['employees'])) {
                $projectsSnapshot[] = $customProjectSnapshot;
            }
        }

        // Update the report
        $report->update([
            'total_hours' => $totalHours,
            'total_amount' => $totalAmount,
            'projects_data' => $projectsSnapshot,
        ]);

        // Create new report lines (only for entries with valid project_id and employee_id)
        foreach ($projectsSnapshot as $project) {
            // Skip custom projects without a valid project_id
            if (empty($project['project_id'])) {
                continue;
            }

            foreach ($project['employees'] as $employee) {
                // Skip custom employees without a valid employee_id
                if (empty($employee['employee_id'])) {
                    continue;
                }

                ProjectReportLine::create([
                    'project_report_id' => $report->id,
                    'project_id' => $project['project_id'],
                    'employee_id' => $employee['employee_id'],
                    'hours' => $employee['hours'],
                    'rate' => $employee['rate'],
                    'amount' => $employee['amount'],
                ]);
            }
        }

        return $report->fresh();
    }

    /**
     * Apply rates and recalculate amounts in the report data.
     *
     * @param array $reportData
     * @param array $rates Rates keyed by project_id_employee_id
     * @return array Updated report data
     */
    public function applyRates(array $reportData, array $rates): array
    {
        $reportData['total_hours'] = 0;
        $reportData['total_amount'] = 0;

        foreach ($reportData['projects'] as &$project) {
            $project['total_hours'] = 0;
            $project['total_amount'] = 0;

            foreach ($project['employees'] as &$employee) {
                $rateKey = $project['project_id'] . '_' . $employee['employee_id'];
                if (isset($rates[$rateKey])) {
                    $employee['rate'] = (float) $rates[$rateKey];
                }
                $employee['amount'] = $employee['hours'] * $employee['rate'];

                $project['total_hours'] += $employee['hours'];
                $project['total_amount'] += $employee['amount'];
            }

            $reportData['total_hours'] += $project['total_hours'];
            $reportData['total_amount'] += $project['total_amount'];
        }

        return $reportData;
    }
}
