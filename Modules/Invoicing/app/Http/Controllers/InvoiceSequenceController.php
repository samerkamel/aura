<?php

namespace Modules\Invoicing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Invoicing\Models\InvoiceSequence;
use Modules\Invoicing\Models\InternalSequence;
use App\Models\BusinessUnit;
use App\Models\Sector;
use App\Helpers\BusinessUnitHelper;

class InvoiceSequenceController extends Controller
{
    /**
     * Display a listing of invoice sequences.
     */
    public function index(): View
    {
        if (!auth()->user()->can('manage-invoice-sequences')) {
            abort(403, 'Unauthorized to view invoice sequences.');
        }

        $sequences = InvoiceSequence::with(['businessUnit'])->get();

        return view('invoicing::sequences.index', compact('sequences'));
    }

    /**
     * Show the form for creating a new invoice sequence.
     */
    public function create(): View
    {
        if (!auth()->user()->can('manage-invoice-sequences')) {
            abort(403, 'Unauthorized to create invoice sequences.');
        }

        $businessUnits = BusinessUnit::all();
        $sectors = Sector::all();

        return view('invoicing::sequences.create', compact('businessUnits', 'sectors'));
    }

    /**
     * Store a newly created invoice sequence.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoice-sequences')) {
            abort(403, 'Unauthorized to create invoice sequences.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:invoice_sequences,name',
            'prefix' => 'required|string|max:10',
            'format' => 'required|string|max:255',
            'starting_number' => 'required|integer|min:1',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'sector_ids' => 'nullable|array',
            'sector_ids.*' => 'exists:sectors,id',
            'description' => 'nullable|string',
        ]);

        InvoiceSequence::create([
            'name' => $request->name,
            'prefix' => $request->prefix,
            'format' => $request->format,
            'current_number' => 0,
            'starting_number' => $request->starting_number,
            'business_unit_id' => $request->business_unit_id,
            'sector_ids' => $request->sector_ids ? json_encode($request->sector_ids) : null,
            'is_active' => $request->has('is_active'),
            'description' => $request->description,
        ]);

        return redirect()
            ->route('invoicing.sequences.index')
            ->with('success', 'Invoice sequence created successfully.');
    }

    /**
     * Display the specified invoice sequence.
     */
    public function show(InvoiceSequence $invoiceSequence): View
    {
        if (!auth()->user()->can('manage-invoice-sequences')) {
            abort(403, 'Unauthorized to view invoice sequence details.');
        }

        $invoiceSequence->load(['businessUnit', 'invoices']);

        return view('invoicing::sequences.show', compact('invoiceSequence'));
    }

    /**
     * Show the form for editing the specified invoice sequence.
     */
    public function edit(InvoiceSequence $invoiceSequence): View
    {
        if (!auth()->user()->can('manage-invoice-sequences')) {
            abort(403, 'Unauthorized to edit invoice sequences.');
        }

        $businessUnits = BusinessUnit::all();
        $sectors = Sector::all();

        return view('invoicing::sequences.edit', compact('invoiceSequence', 'businessUnits', 'sectors'));
    }

    /**
     * Update the specified invoice sequence.
     */
    public function update(Request $request, InvoiceSequence $invoiceSequence): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoice-sequences')) {
            abort(403, 'Unauthorized to edit invoice sequences.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:invoice_sequences,name,' . $invoiceSequence->id,
            'prefix' => 'required|string|max:10',
            'format' => 'required|string|max:255',
            'starting_number' => 'required|integer|min:1',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'sector_ids' => 'nullable|array',
            'sector_ids.*' => 'exists:sectors,id',
            'description' => 'nullable|string',
        ]);

        $invoiceSequence->update([
            'name' => $request->name,
            'prefix' => $request->prefix,
            'format' => $request->format,
            'starting_number' => $request->starting_number,
            'business_unit_id' => $request->business_unit_id,
            'sector_ids' => $request->sector_ids ? json_encode($request->sector_ids) : null,
            'is_active' => $request->has('is_active'),
            'description' => $request->description,
        ]);

        return redirect()
            ->route('invoicing.sequences.show', $invoiceSequence)
            ->with('success', 'Invoice sequence updated successfully.');
    }

    /**
     * Remove the specified invoice sequence.
     */
    public function destroy(InvoiceSequence $invoiceSequence): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoice-sequences')) {
            abort(403, 'Unauthorized to delete invoice sequences.');
        }

        // Check if sequence is being used
        if ($invoiceSequence->invoices()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete sequence that has been used for invoices.');
        }

        $invoiceSequence->delete();

        return redirect()
            ->route('invoicing.sequences.index')
            ->with('success', 'Invoice sequence deleted successfully.');
    }

    /**
     * Reset sequence current number.
     */
    public function reset(InvoiceSequence $invoiceSequence): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoice-sequences')) {
            abort(403, 'Unauthorized to reset invoice sequences.');
        }

        $invoiceSequence->update(['current_number' => 0]);

        return redirect()
            ->back()
            ->with('success', 'Invoice sequence reset successfully.');
    }

    /**
     * Toggle sequence active status.
     */
    public function toggle(InvoiceSequence $invoiceSequence): RedirectResponse
    {
        if (!auth()->user()->can('manage-invoice-sequences')) {
            abort(403, 'Unauthorized to modify invoice sequences.');
        }

        $invoiceSequence->update(['is_active' => !$invoiceSequence->is_active]);

        return redirect()
            ->back()
            ->with('success', 'Invoice sequence status updated.');
    }

    // Internal Sequence Management Methods

    /**
     * Display a listing of internal sequences.
     */
    public function internalIndex(): View
    {
        if (!auth()->user()->can('manage-internal-sequences')) {
            abort(403, 'Unauthorized to view internal sequences.');
        }

        $sequences = InternalSequence::with(['businessUnit'])->get();

        return view('invoicing::internal-sequences.index', compact('sequences'));
    }

    /**
     * Show the form for creating a new internal sequence.
     */
    public function internalCreate(): View
    {
        if (!auth()->user()->can('manage-internal-sequences')) {
            abort(403, 'Unauthorized to create internal sequences.');
        }

        $businessUnits = BusinessUnit::all();
        $sectors = Sector::all();

        return view('invoicing::internal-sequences.create', compact('businessUnits', 'sectors'));
    }

    /**
     * Store a newly created internal sequence.
     */
    public function internalStore(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-internal-sequences')) {
            abort(403, 'Unauthorized to create internal sequences.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:internal_sequences,name',
            'prefix' => 'required|string|max:10',
            'format' => 'required|string|max:255',
            'starting_number' => 'required|integer|min:1',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'sector_ids' => 'nullable|array',
            'sector_ids.*' => 'exists:sectors,id',
            'description' => 'nullable|string',
        ]);

        InternalSequence::create([
            'name' => $request->name,
            'prefix' => $request->prefix,
            'format' => $request->format,
            'current_number' => 0,
            'starting_number' => $request->starting_number,
            'business_unit_id' => $request->business_unit_id,
            'sector_ids' => $request->sector_ids ? json_encode($request->sector_ids) : null,
            'is_active' => $request->has('is_active'),
            'description' => $request->description,
        ]);

        return redirect()
            ->route('invoicing.internal-sequences.index')
            ->with('success', 'Internal sequence created successfully.');
    }

    /**
     * Display the specified internal sequence.
     */
    public function internalShow(InternalSequence $internalSequence): View
    {
        if (!auth()->user()->can('manage-internal-sequences')) {
            abort(403, 'Unauthorized to view internal sequence details.');
        }

        $internalSequence->load(['businessUnit', 'internalTransactions']);

        return view('invoicing::internal-sequences.show', compact('internalSequence'));
    }

    /**
     * Show the form for editing the specified internal sequence.
     */
    public function internalEdit(InternalSequence $internalSequence): View
    {
        if (!auth()->user()->can('manage-internal-sequences')) {
            abort(403, 'Unauthorized to edit internal sequences.');
        }

        $businessUnits = BusinessUnit::all();
        $sectors = Sector::all();

        return view('invoicing::internal-sequences.edit', compact('internalSequence', 'businessUnits', 'sectors'));
    }

    /**
     * Update the specified internal sequence.
     */
    public function internalUpdate(Request $request, InternalSequence $internalSequence): RedirectResponse
    {
        if (!auth()->user()->can('manage-internal-sequences')) {
            abort(403, 'Unauthorized to edit internal sequences.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:internal_sequences,name,' . $internalSequence->id,
            'prefix' => 'required|string|max:10',
            'format' => 'required|string|max:255',
            'starting_number' => 'required|integer|min:1',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'sector_ids' => 'nullable|array',
            'sector_ids.*' => 'exists:sectors,id',
            'description' => 'nullable|string',
        ]);

        $internalSequence->update([
            'name' => $request->name,
            'prefix' => $request->prefix,
            'format' => $request->format,
            'starting_number' => $request->starting_number,
            'business_unit_id' => $request->business_unit_id,
            'sector_ids' => $request->sector_ids ? json_encode($request->sector_ids) : null,
            'is_active' => $request->has('is_active'),
            'description' => $request->description,
        ]);

        return redirect()
            ->route('invoicing.internal-sequences.show', $internalSequence)
            ->with('success', 'Internal sequence updated successfully.');
    }

    /**
     * Remove the specified internal sequence.
     */
    public function internalDestroy(InternalSequence $internalSequence): RedirectResponse
    {
        if (!auth()->user()->can('manage-internal-sequences')) {
            abort(403, 'Unauthorized to delete internal sequences.');
        }

        // Check if sequence is being used
        if ($internalSequence->internalTransactions()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete sequence that has been used for internal transactions.');
        }

        $internalSequence->delete();

        return redirect()
            ->route('invoicing.internal-sequences.index')
            ->with('success', 'Internal sequence deleted successfully.');
    }

    /**
     * Reset internal sequence current number.
     */
    public function internalReset(InternalSequence $internalSequence): RedirectResponse
    {
        if (!auth()->user()->can('manage-internal-sequences')) {
            abort(403, 'Unauthorized to reset internal sequences.');
        }

        $internalSequence->update(['current_number' => 0]);

        return redirect()
            ->back()
            ->with('success', 'Internal sequence reset successfully.');
    }

    /**
     * Toggle internal sequence active status.
     */
    public function internalToggle(InternalSequence $internalSequence): RedirectResponse
    {
        if (!auth()->user()->can('manage-internal-sequences')) {
            abort(403, 'Unauthorized to modify internal sequences.');
        }

        $internalSequence->update(['is_active' => !$internalSequence->is_active]);

        return redirect()
            ->back()
            ->with('success', 'Internal sequence status updated.');
    }
}
