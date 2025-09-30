<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SectorController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the sectors.
     */
    public function index()
    {
        $this->authorize('manage-sectors');

        $sectors = Sector::withCount(['businessUnits', 'activeBusinessUnits'])
            ->orderBy('name')
            ->paginate(15);

        return view('administration.sectors.index', compact('sectors'));
    }

    /**
     * Show the form for creating a new sector.
     */
    public function create()
    {
        $this->authorize('manage-sectors');

        return view('administration.sectors.create');
    }

    /**
     * Store a newly created sector in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('manage-sectors');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:sectors,code',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $sector = Sector::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            return redirect()->route('administration.sectors.index')
                ->with('success', 'Sector created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error creating sector: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified sector.
     */
    public function show(Sector $sector)
    {
        $this->authorize('view-sectors');

        $sector->load(['businessUnits' => function ($query) {
            $query->withCount(['products', 'contracts']);
        }]);

        return view('administration.sectors.show', compact('sector'));
    }

    /**
     * Show the form for editing the specified sector.
     */
    public function edit(Sector $sector)
    {
        $this->authorize('manage-sectors');

        return view('administration.sectors.edit', compact('sector'));
    }

    /**
     * Update the specified sector in storage.
     */
    public function update(Request $request, Sector $sector)
    {
        $this->authorize('manage-sectors');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('sectors')->ignore($sector->id),
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $sector->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            return redirect()->route('administration.sectors.index')
                ->with('success', 'Sector updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error updating sector: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified sector from storage.
     */
    public function destroy(Sector $sector)
    {
        $this->authorize('manage-sectors');

        // Check if sector has business units
        if ($sector->businessUnits()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete sector that has business units assigned to it.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $sector->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sector deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting sector: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the status of the specified sector.
     */
    public function toggleStatus(Sector $sector)
    {
        $this->authorize('manage-sectors');

        try {
            $sector->update(['is_active' => !$sector->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Sector status updated successfully.',
                'is_active' => $sector->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating sector status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sectors for API/AJAX requests.
     */
    public function apiIndex(Request $request)
    {
        $sectors = Sector::active()
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'sectors' => $sectors
        ]);
    }
}