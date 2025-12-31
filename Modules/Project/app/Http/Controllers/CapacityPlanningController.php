<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Project\Models\Project;
use Modules\HR\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CapacityPlanningController extends Controller
{
    /**
     * Display the capacity planning dashboard.
     */
    public function index(Request $request): View
    {
        $startDate = Carbon::parse($request->get('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->get('end_date', now()->addMonths(2)->endOfMonth()));

        // Get all active employees with billable hours (project staff only, excludes support)
        $employees = Employee::active()
            ->where('billable_hours_applicable', true)
            ->with(['projects' => function ($query) use ($startDate, $endDate) {
                $query->where('is_active', true)
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('planned_start_date', [$startDate, $endDate])
                            ->orWhereBetween('planned_end_date', [$startDate, $endDate])
                            ->orWhere(function ($q2) use ($startDate, $endDate) {
                                $q2->where('planned_start_date', '<=', $startDate)
                                    ->where('planned_end_date', '>=', $endDate);
                            });
                    });
            }])
            ->orderBy('name')
            ->get();

        // Get active projects with employee allocations
        $projects = Project::where('is_active', true)
            ->with(['employees', 'customer'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('planned_start_date', [$startDate, $endDate])
                    ->orWhereBetween('planned_end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('planned_start_date', '<=', $startDate)
                            ->where('planned_end_date', '>=', $endDate);
                    });
            })
            ->orderBy('planned_start_date')
            ->get();

        // Calculate capacity data by week
        $weeks = $this->getWeeksInRange($startDate, $endDate);
        $capacityData = $this->calculateCapacityByWeek($employees, $weeks);

        // Calculate summary stats
        $totalCapacity = $employees->count() * 40; // Hours per week
        $totalAllocated = $this->calculateTotalAllocatedHours($employees, $projects);
        $utilizationRate = $totalCapacity > 0 ? ($totalAllocated / $totalCapacity) * 100 : 0;

        $overallocatedEmployees = $employees->filter(function ($employee) {
            $totalAllocation = $employee->projects->sum('pivot.allocation_percentage');
            return $totalAllocation > 100;
        });

        $underutilizedEmployees = $employees->filter(function ($employee) {
            $totalAllocation = $employee->projects->sum('pivot.allocation_percentage');
            return $totalAllocation < 50 && $totalAllocation > 0;
        });

        $unallocatedEmployees = $employees->filter(function ($employee) {
            return $employee->projects->isEmpty();
        });

        return view('project::capacity.index', [
            'employees' => $employees,
            'projects' => $projects,
            'weeks' => $weeks,
            'capacityData' => $capacityData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalCapacity' => $totalCapacity,
            'totalAllocated' => $totalAllocated,
            'utilizationRate' => $utilizationRate,
            'overallocatedEmployees' => $overallocatedEmployees,
            'underutilizedEmployees' => $underutilizedEmployees,
            'unallocatedEmployees' => $unallocatedEmployees,
        ]);
    }

    /**
     * Get weeks in the date range.
     */
    private function getWeeksInRange(Carbon $start, Carbon $end): array
    {
        $weeks = [];
        $current = $start->copy()->startOfWeek();

        while ($current <= $end) {
            $weeks[] = [
                'start' => $current->copy(),
                'end' => $current->copy()->endOfWeek(),
                'label' => $current->format('M d'),
            ];
            $current->addWeek();
        }

        return $weeks;
    }

    /**
     * Calculate capacity data by week for each employee.
     */
    private function calculateCapacityByWeek($employees, array $weeks): array
    {
        $data = [];

        foreach ($employees as $employee) {
            $employeeData = [
                'employee' => $employee,
                'weeks' => [],
            ];

            foreach ($weeks as $week) {
                $weekAllocation = 0;

                foreach ($employee->projects as $project) {
                    // Check if project overlaps with this week
                    $projectStart = $project->planned_start_date ?? Carbon::now()->subYear();
                    $projectEnd = $project->planned_end_date ?? Carbon::now()->addYear();

                    if ($projectStart <= $week['end'] && $projectEnd >= $week['start']) {
                        $weekAllocation += $project->pivot->allocation_percentage ?? 0;
                    }
                }

                $employeeData['weeks'][] = [
                    'week' => $week,
                    'allocation' => min($weekAllocation, 150), // Cap at 150% for display
                    'status' => $this->getAllocationStatus($weekAllocation),
                ];
            }

            $data[] = $employeeData;
        }

        return $data;
    }

    /**
     * Get allocation status color.
     */
    private function getAllocationStatus(float $percentage): string
    {
        if ($percentage <= 0) {
            return 'available';
        } elseif ($percentage < 50) {
            return 'underutilized';
        } elseif ($percentage <= 100) {
            return 'optimal';
        } else {
            return 'overallocated';
        }
    }

    /**
     * Calculate total allocated hours across all employees and projects.
     */
    private function calculateTotalAllocatedHours($employees, $projects): float
    {
        $totalHours = 0;

        foreach ($employees as $employee) {
            foreach ($employee->projects as $project) {
                $allocation = $project->pivot->allocation_percentage ?? 0;
                $totalHours += ($allocation / 100) * 40; // 40 hours per week
            }
        }

        return $totalHours;
    }

    /**
     * API endpoint for capacity heatmap data.
     */
    public function apiHeatmapData(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->get('end_date', now()->addMonths(2)->endOfMonth()));

        $employees = Employee::active()
            ->where('billable_hours_applicable', true)
            ->with(['projects' => function ($query) use ($startDate, $endDate) {
                $query->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();

        $weeks = $this->getWeeksInRange($startDate, $endDate);
        $heatmapData = [];

        foreach ($employees as $employee) {
            $row = ['employee' => $employee->full_name];

            foreach ($weeks as $week) {
                $weekAllocation = 0;

                foreach ($employee->projects as $project) {
                    $projectStart = $project->planned_start_date ?? Carbon::now()->subYear();
                    $projectEnd = $project->planned_end_date ?? Carbon::now()->addYear();

                    if ($projectStart <= $week['end'] && $projectEnd >= $week['start']) {
                        $weekAllocation += $project->pivot->allocation_percentage ?? 0;
                    }
                }

                $row['weeks'][] = [
                    'label' => $week['label'],
                    'value' => $weekAllocation,
                    'status' => $this->getAllocationStatus($weekAllocation),
                ];
            }

            $heatmapData[] = $row;
        }

        return response()->json($heatmapData);
    }
}
