<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Modules\Accounting\Models\Estimate;
use Modules\Accounting\Services\EstimateService;
use Modules\Accounting\Services\EstimateConversionService;
use Modules\Settings\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * EstimateController
 *
 * Handles CRUD operations for estimates and quotations.
 */
class EstimateController extends Controller
{
    protected EstimateService $estimateService;

    public function __construct(EstimateService $estimateService)
    {
        $this->estimateService = $estimateService;
    }

    /**
     * Display a listing of estimates.
     */
    public function index(Request $request): View
    {
        $query = Estimate::with(['customer', 'project', 'createdBy', 'items']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by project
        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->where('issue_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->where('issue_date', '<=', $request->to_date);
        }

        // Search by estimate number, client name, or title
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('estimate_number', 'like', '%' . $request->search . '%')
                  ->orWhere('client_name', 'like', '%' . $request->search . '%')
                  ->orWhere('title', 'like', '%' . $request->search . '%');
            });
        }

        $estimates = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get customers and projects for filters
        $customers = \App\Models\Customer::orderBy('name')->get();
        $projects = \Modules\Project\Models\Project::orderBy('name')->get();

        // Statistics
        $statistics = [
            'total_estimates' => Estimate::count(),
            'draft_count' => Estimate::draft()->count(),
            'sent_count' => Estimate::sent()->count(),
            'approved_count' => Estimate::approved()->count(),
            'approved_total' => Estimate::approved()->sum('total'),
        ];

        return view('accounting::estimates.index', compact(
            'estimates',
            'customers',
            'projects',
            'statistics'
        ));
    }

    /**
     * Show the form for creating a new estimate.
     */
    public function create(Request $request): View
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        $projects = \Modules\Project\Models\Project::orderBy('name')->get();
        $companySettings = CompanySetting::getSettings();

        // Pre-select project from query parameter
        $selectedProjectId = $request->query('project_id');
        $selectedProject = $selectedProjectId
            ? \Modules\Project\Models\Project::with('customer')->find($selectedProjectId)
            : null;

        return view('accounting::estimates.create', compact(
            'customers',
            'projects',
            'companySettings',
            'selectedProjectId',
            'selectedProject'
        ));
    }

    /**
     * Store a newly created estimate.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_address' => 'nullable|string|max:1000',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'issue_date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:issue_date',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.details' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $items = $validated['items'];
        unset($validated['items']);

        $estimate = $this->estimateService->createEstimate($validated, $items);

        return redirect()->route('accounting.estimates.show', $estimate)
            ->with('success', 'Estimate created successfully.');
    }

    /**
     * Display the specified estimate.
     */
    public function show(Estimate $estimate): View
    {
        $estimate->load(['customer', 'project', 'createdBy', 'items', 'contract']);
        $companySettings = CompanySetting::getSettings();

        return view('accounting::estimates.show', compact('estimate', 'companySettings'));
    }

    /**
     * Show the form for editing the specified estimate.
     */
    public function edit(Estimate $estimate): View
    {
        if (!$estimate->canBeEdited()) {
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('error', 'This estimate cannot be edited.');
        }

        $estimate->load(['items']);
        $customers = \App\Models\Customer::orderBy('name')->get();
        $projects = \Modules\Project\Models\Project::orderBy('name')->get();
        $companySettings = CompanySetting::getSettings();

        return view('accounting::estimates.edit', compact(
            'estimate',
            'customers',
            'projects',
            'companySettings'
        ));
    }

    /**
     * Update the specified estimate.
     */
    public function update(Request $request, Estimate $estimate): RedirectResponse
    {
        if (!$estimate->canBeEdited()) {
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('error', 'This estimate cannot be edited.');
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_address' => 'nullable|string|max:1000',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'issue_date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:issue_date',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.details' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $items = $validated['items'];
        unset($validated['items']);

        $this->estimateService->updateEstimate($estimate, $validated, $items);

        return redirect()->route('accounting.estimates.show', $estimate)
            ->with('success', 'Estimate updated successfully.');
    }

    /**
     * Remove the specified estimate.
     */
    public function destroy(Estimate $estimate): RedirectResponse
    {
        try {
            $this->estimateService->deleteEstimate($estimate);
            return redirect()->route('accounting.estimates.index')
                ->with('success', 'Estimate deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Duplicate an estimate.
     */
    public function duplicate(Estimate $estimate): RedirectResponse
    {
        $newEstimate = $this->estimateService->duplicateEstimate($estimate);

        return redirect()->route('accounting.estimates.edit', $newEstimate)
            ->with('success', 'Estimate duplicated successfully. You can now edit the copy.');
    }

    /**
     * Mark estimate as sent.
     */
    public function markAsSent(Estimate $estimate): RedirectResponse
    {
        try {
            $this->estimateService->markAsSent($estimate);
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('success', 'Estimate marked as sent.');
        } catch (\Exception $e) {
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark estimate as approved.
     */
    public function markAsApproved(Estimate $estimate): RedirectResponse
    {
        try {
            $this->estimateService->markAsApproved($estimate);
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('success', 'Estimate marked as approved.');
        } catch (\Exception $e) {
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark estimate as rejected.
     */
    public function markAsRejected(Estimate $estimate): RedirectResponse
    {
        try {
            $this->estimateService->markAsRejected($estimate);
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('success', 'Estimate marked as rejected.');
        } catch (\Exception $e) {
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Convert estimate to contract (simple, without project options).
     */
    public function convertToContract(Estimate $estimate): RedirectResponse
    {
        try {
            $contract = $this->estimateService->convertToContract($estimate);
            return redirect()->route('accounting.income.contracts.show', $contract)
                ->with('success', 'Estimate converted to contract successfully.');
        } catch (\Exception $e) {
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Convert estimate to contract with project linking options.
     */
    public function convertToContractWithProject(Request $request, Estimate $estimate, EstimateConversionService $conversionService): RedirectResponse
    {
        $validated = $request->validate([
            'project_action' => 'required|in:none,link_existing,create_new',
            'project_id' => 'nullable|exists:projects,id',
            'linked_project_id' => 'nullable|exists:projects,id',
            'project_name' => 'nullable|string|max:255',
            'project_code' => 'nullable|string|max:50|unique:projects,code',
            'allocation_type' => 'nullable|in:percentage,amount',
            'allocation_value' => 'nullable|numeric|min:0',
            'contract_start_date' => 'nullable|date',
            'sync_to_project' => 'nullable|boolean',
        ]);

        // Determine the project ID based on action
        $projectId = null;
        if ($validated['project_action'] === 'link_existing') {
            $projectId = $validated['project_id'] ?? $validated['linked_project_id'] ?? null;
        }

        $options = [
            'project_action' => $validated['project_action'],
            'project_id' => $projectId,
            'project_name' => $validated['project_name'] ?? null,
            'project_code' => $validated['project_code'] ?? null,
            'allocation_type' => $validated['allocation_type'] ?? 'percentage',
            'allocation_value' => $validated['allocation_value'] ?? 100,
            'contract_start_date' => $validated['contract_start_date'] ?? now()->toDateString(),
            'sync_to_project' => isset($validated['sync_to_project']) ? (bool) $validated['sync_to_project'] : true,
        ];

        $result = $conversionService->convertToContractWithProject($estimate, $options);

        if (!$result['success']) {
            return redirect()->route('accounting.estimates.show', $estimate)
                ->with('error', $result['message']);
        }

        $message = 'Estimate converted to contract successfully.';
        if ($result['project']) {
            $message .= ' Linked to project: ' . $result['project']->name;
        }

        return redirect()->route('accounting.income.contracts.show', $result['contract'])
            ->with('success', $message);
    }

    /**
     * Export estimate as PDF.
     */
    public function exportPdf(Estimate $estimate): Response
    {
        $estimate->load(['customer', 'project', 'items']);
        $companySettings = CompanySetting::getSettings();

        $pdf = Pdf::loadView('accounting::estimates.pdf', compact('estimate', 'companySettings'));
        $pdf->setPaper('A4');

        return $pdf->download('estimate-' . $estimate->estimate_number . '.pdf');
    }
}
