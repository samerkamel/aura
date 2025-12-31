<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectBudget;
use Modules\Project\Models\ProjectCost;
use Modules\Project\Models\ProjectRevenue;
use Modules\Project\Services\ProjectFinancialService;
use Modules\HR\Models\Employee;

class ProjectFinanceController extends Controller
{
    protected ProjectFinancialService $financialService;

    public function __construct(ProjectFinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Financial dashboard.
     */
    public function index(Project $project)
    {
        if (!Gate::allows('view-project-finance', $project)) {
            abort(403, 'You do not have permission to view project finances.');
        }

        $dashboard = $this->financialService->getFinancialDashboard($project);

        return view('project::projects.finance.index', compact('project', 'dashboard'));
    }

    /**
     * Budget management page.
     */
    public function budgets(Project $project)
    {
        if (!Gate::allows('manage-project-budgets', $project)) {
            abort(403, 'You do not have permission to manage project budgets.');
        }

        $budgets = $project->budgets()->orderBy('category')->get();
        $categories = ProjectBudget::CATEGORIES;
        $breakdown = $this->financialService->getBudgetBreakdown($project);

        return view('project::projects.finance.budgets', compact('project', 'budgets', 'categories', 'breakdown'));
    }

    /**
     * Store a new budget.
     */
    public function storeBudget(Request $request, Project $project)
    {
        if (!Gate::allows('manage-project-budgets', $project)) {
            abort(403, 'You do not have permission to manage project budgets.');
        }

        $validated = $request->validate([
            'category' => 'required|string',
            'description' => 'nullable|string',
            'planned_amount' => 'required|numeric|min:0',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);

        $this->financialService->createBudget($project, $validated);

        return redirect()->route('projects.finance.budgets', $project)
            ->with('success', 'Budget created successfully.');
    }

    /**
     * Update a budget.
     */
    public function updateBudget(Request $request, Project $project, ProjectBudget $budget)
    {
        if (!Gate::allows('manage-project-budgets', $project)) {
            abort(403, 'You do not have permission to manage project budgets.');
        }

        $validated = $request->validate([
            'category' => 'required|string',
            'description' => 'nullable|string',
            'planned_amount' => 'required|numeric|min:0',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'is_active' => 'boolean',
        ]);

        $budget->update($validated);

        return redirect()->route('projects.finance.budgets', $project)
            ->with('success', 'Budget updated successfully.');
    }

    /**
     * Delete a budget.
     */
    public function destroyBudget(Project $project, ProjectBudget $budget)
    {
        if (!Gate::allows('manage-project-budgets', $project)) {
            abort(403, 'You do not have permission to manage project budgets.');
        }

        $budget->delete();

        return redirect()->route('projects.finance.budgets', $project)
            ->with('success', 'Budget deleted successfully.');
    }

    /**
     * Costs management page.
     */
    public function costs(Project $project, Request $request)
    {
        if (!Gate::allows('manage-project-costs', $project)) {
            abort(403, 'You do not have permission to manage project costs.');
        }

        // Only show non-labor costs in the table (labor is calculated dynamically from worklogs)
        $query = $project->costs()->with(['employee', 'budget', 'creator'])
            ->where('cost_type', '!=', 'labor');

        // Filters
        if ($request->filled('cost_type') && $request->cost_type !== 'labor') {
            $query->where('cost_type', $request->cost_type);
        }
        if ($request->filled('start_date')) {
            $query->where('cost_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('cost_date', '<=', $request->end_date);
        }
        if ($request->filled('billable')) {
            $query->where('is_billable', $request->billable === 'yes');
        }

        $costs = $query->orderBy('cost_date', 'desc')->paginate(20);

        // Remove labor from cost types dropdown (it's calculated automatically)
        $costTypes = array_filter(ProjectCost::COST_TYPES, fn($key) => $key !== 'labor', ARRAY_FILTER_USE_KEY);

        $budgets = $project->budgets()->active()->get();
        $employees = Employee::active()->orderBy('name')->get();
        $breakdown = $this->financialService->getCostBreakdown($project);

        return view('project::projects.finance.costs', compact(
            'project', 'costs', 'costTypes', 'budgets', 'employees', 'breakdown'
        ));
    }

    /**
     * Store a new cost.
     */
    public function storeCost(Request $request, Project $project)
    {
        if (!Gate::allows('manage-project-costs', $project)) {
            abort(403, 'You do not have permission to manage project costs.');
        }

        $validated = $request->validate([
            'cost_type' => 'required|string|in:' . implode(',', array_keys(ProjectCost::COST_TYPES)),
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'cost_date' => 'required|date',
            'project_budget_id' => 'nullable|exists:project_budgets,id',
            'employee_id' => 'nullable|exists:employees,id',
            'hours' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_billable' => 'boolean',
        ]);

        $this->financialService->recordCost($project, $validated);

        return redirect()->route('projects.finance.costs', $project)
            ->with('success', 'Cost recorded successfully.');
    }

    /**
     * Update a cost.
     */
    public function updateCost(Request $request, Project $project, ProjectCost $cost)
    {
        if (!Gate::allows('manage-project-costs', $project)) {
            abort(403, 'You do not have permission to manage project costs.');
        }

        $validated = $request->validate([
            'cost_type' => 'required|string|in:' . implode(',', array_keys(ProjectCost::COST_TYPES)),
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'cost_date' => 'required|date',
            'project_budget_id' => 'nullable|exists:project_budgets,id',
            'employee_id' => 'nullable|exists:employees,id',
            'hours' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_billable' => 'boolean',
        ]);

        $cost->update($validated);

        return redirect()->route('projects.finance.costs', $project)
            ->with('success', 'Cost updated successfully.');
    }

    /**
     * Delete a cost.
     */
    public function destroyCost(Project $project, ProjectCost $cost)
    {
        if (!Gate::allows('manage-project-costs', $project)) {
            abort(403, 'You do not have permission to manage project costs.');
        }

        $cost->delete();

        return redirect()->route('projects.finance.costs', $project)
            ->with('success', 'Cost deleted successfully.');
    }

    /**
     * Revenue management page.
     */
    public function revenues(Project $project, Request $request)
    {
        if (!Gate::allows('manage-project-revenues', $project)) {
            abort(403, 'You do not have permission to manage project revenues.');
        }

        $query = $project->revenues()->with(['contract', 'invoice', 'creator']);

        // Filters
        if ($request->filled('revenue_type')) {
            $query->where('revenue_type', $request->revenue_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date')) {
            $query->where('revenue_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('revenue_date', '<=', $request->end_date);
        }

        $revenues = $query->orderBy('revenue_date', 'desc')->paginate(20);
        $revenueTypes = ProjectRevenue::REVENUE_TYPES;
        $statuses = ProjectRevenue::STATUSES;
        $breakdown = $this->financialService->getRevenueBreakdown($project);

        return view('project::projects.finance.revenues', compact(
            'project', 'revenues', 'revenueTypes', 'statuses', 'breakdown'
        ));
    }

    /**
     * Store a new revenue.
     */
    public function storeRevenue(Request $request, Project $project)
    {
        if (!Gate::allows('manage-project-revenues', $project)) {
            abort(403, 'You do not have permission to manage project revenues.');
        }

        $validated = $request->validate([
            'revenue_type' => 'required|string|in:' . implode(',', array_keys(ProjectRevenue::REVENUE_TYPES)),
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'revenue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'required|string|in:' . implode(',', array_keys(ProjectRevenue::STATUSES)),
            'amount_received' => 'nullable|numeric|min:0',
            'received_date' => 'nullable|date',
        ]);

        $this->financialService->recordRevenue($project, $validated);

        return redirect()->route('projects.finance.revenues', $project)
            ->with('success', 'Revenue recorded successfully.');
    }

    /**
     * Update a revenue.
     */
    public function updateRevenue(Request $request, Project $project, ProjectRevenue $revenue)
    {
        if (!Gate::allows('manage-project-revenues', $project)) {
            abort(403, 'You do not have permission to manage project revenues.');
        }

        $validated = $request->validate([
            'revenue_type' => 'required|string|in:' . implode(',', array_keys(ProjectRevenue::REVENUE_TYPES)),
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'revenue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'required|string|in:' . implode(',', array_keys(ProjectRevenue::STATUSES)),
            'amount_received' => 'nullable|numeric|min:0',
            'received_date' => 'nullable|date',
        ]);

        $revenue->update($validated);

        return redirect()->route('projects.finance.revenues', $project)
            ->with('success', 'Revenue updated successfully.');
    }

    /**
     * Delete a revenue.
     */
    public function destroyRevenue(Project $project, ProjectRevenue $revenue)
    {
        if (!Gate::allows('manage-project-revenues', $project)) {
            abort(403, 'You do not have permission to manage project revenues.');
        }

        $revenue->delete();

        return redirect()->route('projects.finance.revenues', $project)
            ->with('success', 'Revenue deleted successfully.');
    }

    /**
     * Record a payment for a revenue.
     */
    public function recordPayment(Request $request, Project $project, ProjectRevenue $revenue)
    {
        if (!Gate::allows('manage-project-revenues', $project)) {
            abort(403, 'You do not have permission to manage project revenues.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $revenue->outstanding_amount,
            'received_date' => 'required|date',
        ]);

        $revenue->amount_received += $validated['amount'];
        $revenue->received_date = $validated['received_date'];
        $revenue->save();

        return redirect()->route('projects.finance.revenues', $project)
            ->with('success', 'Payment recorded successfully.');
    }

    /**
     * Profitability analysis page.
     */
    public function profitability(Project $project)
    {
        if (!Gate::allows('view-project-profitability', $project)) {
            abort(403, 'You do not have permission to view project profitability.');
        }

        $profitability = $this->financialService->getProfitabilityAnalysis($project);
        $monthlyTrend = $this->financialService->getMonthlyTrend($project, 12);
        $burnRate = $this->financialService->calculateBurnRate($project);

        return view('project::projects.finance.profitability', compact(
            'project', 'profitability', 'monthlyTrend', 'burnRate'
        ));
    }

    /**
     * API endpoint for financial summary.
     */
    public function apiSummary(Project $project)
    {
        if (!Gate::allows('view-project-finance', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($this->financialService->getFinancialSummary($project));
    }

    /**
     * API endpoint for monthly trend.
     */
    public function apiMonthlyTrend(Project $project, Request $request)
    {
        if (!Gate::allows('view-project-finance', $project)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $months = $request->get('months', 6);
        return response()->json($this->financialService->getMonthlyTrend($project, $months));
    }

    /**
     * Generate labor costs from worklogs.
     */
    public function generateLaborCosts(Request $request, Project $project)
    {
        if (!Gate::allows('manage-project-costs', $project)) {
            abort(403, 'You do not have permission to manage project costs.');
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $count = $this->financialService->generateLaborCostsFromWorklogs(
            $project,
            \Carbon\Carbon::parse($validated['start_date']),
            \Carbon\Carbon::parse($validated['end_date'])
        );

        return redirect()->route('projects.finance.costs', $project)
            ->with('success', "Generated {$count} labor cost entries from worklogs.");
    }
}
