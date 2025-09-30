<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Helpers\BusinessUnitHelper;

class BusinessUnitController extends Controller
{
    public function index(): View
    {
        if (!auth()->user()->can('manage-business-units') && !auth()->user()->can('view-all-business-units')) {
            abort(403, 'Unauthorized to view business units.');
        }

        $businessUnits = BusinessUnit::with(['users', 'departments', 'contracts'])
            ->withCount(['departments', 'contracts'])
            ->orderBy('name')
            ->get();

        $statistics = [
            'total_business_units' => BusinessUnit::count(),
            'active_business_units' => BusinessUnit::where('is_active', true)->count(),
            'head_office_units' => BusinessUnit::where('type', 'head_office')->count(),
            'total_products' => $businessUnits->sum('departments_count'),
            'total_contracts' => $businessUnits->sum('contracts_count'),
        ];

        return view('administration.business-units.index', compact('businessUnits', 'statistics'));
    }

    public function create(): View
    {
        if (!auth()->user()->can('manage-business-units')) {
            abort(403, 'Unauthorized to create business units.');
        }

        $sectors = \App\Models\Sector::active()->orderBy('name')->get();

        return view('administration.business-units.create', compact('sectors'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-business-units')) {
            abort(403, 'Unauthorized to create business units.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:business_units',
            'description' => 'nullable|string',
            'type' => 'required|in:business_unit,head_office',
            'sector_id' => 'nullable|integer|exists:sectors,id',
        ]);

        $businessUnit = BusinessUnit::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'type' => $request->type,
            'sector_id' => $request->sector_id ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()
            ->route('administration.business-units.show', $businessUnit)
            ->with('success', 'Business Unit created successfully.');
    }

    public function show(BusinessUnit $businessUnit): View
    {
        if (!auth()->user()->can('manage-business-units') && !auth()->user()->can('view-all-business-units')) {
            abort(403, 'Unauthorized to view business unit details.');
        }

        $businessUnit->load([
            'users' => function($query) {
                $query->with('roles');
            },
            'departments' => function($query) {
                $query->with('contracts');
            },
            'contracts' => function($query) {
                $query->with('departments');
            }
        ]);

        $statistics = [
            'total_users' => $businessUnit->users->count(),
            'active_products' => $businessUnit->departments->where('is_active', true)->count(),
            'total_budget' => $businessUnit->departments->where('is_active', true)->sum('budget_allocation'),
            'active_contracts' => $businessUnit->contracts->where('is_active', true)->count(),
            'total_contract_value' => $businessUnit->contracts->where('is_active', true)->sum('total_amount'),
        ];

        return view('administration.business-units.show', compact('businessUnit', 'statistics'));
    }

    public function edit(BusinessUnit $businessUnit): View
    {
        if (!auth()->user()->can('manage-business-units')) {
            abort(403, 'Unauthorized to edit business units.');
        }

        $sectors = \App\Models\Sector::active()->orderBy('name')->get();

        return view('administration.business-units.edit', compact('businessUnit', 'sectors'));
    }

    public function update(Request $request, BusinessUnit $businessUnit): RedirectResponse
    {
        if (!auth()->user()->can('manage-business-units')) {
            abort(403, 'Unauthorized to edit business units.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:business_units,code,' . $businessUnit->id,
            'description' => 'nullable|string',
            'type' => 'required|in:business_unit,head_office',
            'sector_id' => 'nullable|integer|exists:sectors,id',
        ]);

        $businessUnit->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'type' => $request->type,
            'sector_id' => $request->sector_id ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()
            ->route('administration.business-units.show', $businessUnit)
            ->with('success', 'Business Unit updated successfully.');
    }

    public function destroy(BusinessUnit $businessUnit): RedirectResponse
    {
        if (!auth()->user()->can('manage-business-units')) {
            abort(403, 'Unauthorized to delete business units.');
        }

        // Check if business unit has products or contracts
        if ($businessUnit->departments()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete business unit that has products.');
        }

        if ($businessUnit->contracts()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete business unit that has contracts.');
        }

        $businessUnit->delete();

        return redirect()
            ->route('administration.business-units.index')
            ->with('success', 'Business Unit deleted successfully.');
    }

    public function toggleStatus(BusinessUnit $businessUnit): RedirectResponse
    {
        if (!auth()->user()->can('manage-business-units')) {
            abort(403, 'Unauthorized to modify business units.');
        }

        $businessUnit->update(['is_active' => !$businessUnit->is_active]);

        $status = $businessUnit->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Business Unit {$status} successfully.");
    }

    public function manageUsers(BusinessUnit $businessUnit): View
    {
        if (!auth()->user()->can('assign-users-to-business-units')) {
            abort(403, 'Unauthorized to manage business unit users.');
        }

        $businessUnit->load('users.roles');
        $allUsers = User::with('roles')->orderBy('name')->get();
        $assignedUserIds = $businessUnit->users->pluck('id')->toArray();

        return view('administration.business-units.manage-users', compact(
            'businessUnit',
            'allUsers',
            'assignedUserIds'
        ));
    }

    public function assignUser(Request $request, BusinessUnit $businessUnit): RedirectResponse
    {
        if (!auth()->user()->can('assign-users-to-business-units')) {
            abort(403, 'Unauthorized to assign users to business units.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,manager,admin',
        ]);

        $user = User::findOrFail($request->user_id);

        // Check if user is already assigned
        if ($businessUnit->users()->where('user_id', $user->id)->exists()) {
            return redirect()->back()->with('error', 'User is already assigned to this business unit.');
        }

        $businessUnit->users()->attach($user->id, ['role' => $request->role]);

        return redirect()->back()->with('success', "User {$user->name} assigned successfully.");
    }

    public function unassignUser(Request $request, BusinessUnit $businessUnit): RedirectResponse
    {
        if (!auth()->user()->can('assign-users-to-business-units')) {
            abort(403, 'Unauthorized to unassign users from business units.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $businessUnit->users()->detach($user->id);

        return redirect()->back()->with('success', "User {$user->name} unassigned successfully.");
    }

    public function switchBusinessUnit(Request $request): RedirectResponse
    {
        $request->validate([
            'business_unit_id' => 'required|exists:business_units,id',
        ]);

        $businessUnitId = $request->business_unit_id;

        // Verify user has access to this business unit
        if (!BusinessUnitHelper::isSuperAdmin() &&
            !in_array($businessUnitId, BusinessUnitHelper::getAccessibleBusinessUnitIds())) {
            abort(403, 'You do not have access to this business unit.');
        }

        BusinessUnitHelper::setCurrentBusinessUnit($businessUnitId);

        $businessUnit = BusinessUnit::find($businessUnitId);

        return redirect()->back()->with('success', "Switched to {$businessUnit->name}");
    }
}