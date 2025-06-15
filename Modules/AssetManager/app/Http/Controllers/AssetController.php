<?php

namespace Modules\AssetManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\AssetManager\Models\Asset;
use Modules\AssetManager\Http\Requests\StoreAssetRequest;
use Modules\AssetManager\Http\Requests\UpdateAssetRequest;
use Modules\HR\Models\Employee;

/**
 * AssetController
 *
 * Manages CRUD operations for company assets.
 * Provides functionality to create, view, edit, and delete assets in the master list.
 *
 * @author Dev Agent
 */
class AssetController extends Controller
{
    /**
     * Display a listing of all assets
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $query = Asset::with('currentEmployee');

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type if provided
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('serial_number', 'like', '%' . $search . '%');
            });
        }

        $assets = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get unique asset types for filter dropdown
        $assetTypes = Asset::distinct()->pluck('type')->sort();

        // Get available employees for assignment
        $employees = Employee::orderBy('name')->get();

        return view('assetmanager::assets.index', compact('assets', 'assetTypes', 'employees'));
    }

    /**
     * Show the form for creating a new asset
     *
     * @return View
     */
    public function create(): View
    {
        return view('assetmanager::assets.create');
    }

    /**
     * Store a newly created asset in storage
     *
     * @param StoreAssetRequest $request
     * @return RedirectResponse
     */
    public function store(StoreAssetRequest $request): RedirectResponse
    {
        Asset::create($request->validated());

        return redirect()->route('assetmanager.assets.index')
            ->with('success', 'Asset created successfully.');
    }

    /**
     * Display the specified asset
     *
     * @param Asset $asset
     * @return View
     */
    public function show(Asset $asset): View
    {
        $asset->load(['employees' => function ($query) {
            $query->withPivot(['assigned_date', 'returned_date', 'notes'])
                ->orderBy('asset_employee.assigned_date', 'desc');
        }]);

        return view('assetmanager::assets.show', compact('asset'));
    }

    /**
     * Show the form for editing the specified asset
     *
     * @param Asset $asset
     * @return View
     */
    public function edit(Asset $asset): View
    {
        return view('assetmanager::assets.edit', compact('asset'));
    }

    /**
     * Update the specified asset in storage
     *
     * @param UpdateAssetRequest $request
     * @param Asset $asset
     * @return RedirectResponse
     */
    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $asset->update($request->validated());

        return redirect()->route('assetmanager.assets.index')
            ->with('success', 'Asset updated successfully.');
    }

    /**
     * Remove the specified asset from storage
     *
     * @param Asset $asset
     * @return RedirectResponse
     */
    public function destroy(Asset $asset): RedirectResponse
    {
        // Check if asset is currently assigned
        if ($asset->isAssigned()) {
            return redirect()->route('assetmanager.assets.index')
                ->with('error', 'Cannot delete an asset that is currently assigned to an employee.');
        }

        $asset->delete();

        return redirect()->route('assetmanager.assets.index')
            ->with('success', 'Asset deleted successfully.');
    }
}
