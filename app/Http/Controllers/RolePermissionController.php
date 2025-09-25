<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionController extends Controller
{
    /**
     * Display roles listing.
     */
    public function index(Request $request): View
    {
        if (!auth()->user()->can('manage-roles-permissions')) {
            abort(403, 'Unauthorized to manage roles and permissions.');
        }

        $query = Role::with(['permissions', 'users']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('display_name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $roles = $query->orderBy('name')->paginate(15);

        // Calculate statistics
        $statistics = [
            'total_roles' => Role::count(),
            'active_roles' => Role::where('is_active', true)->count(),
            'inactive_roles' => Role::where('is_active', false)->count(),
            'total_permissions' => Permission::count(),
        ];

        return view('administration.roles.index', compact('roles', 'statistics'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): View
    {
        if (!auth()->user()->can('manage-roles-permissions')) {
            abort(403, 'Unauthorized to create roles.');
        }

        $permissions = Permission::orderBy('category')->orderBy('display_name')->get()->groupBy('category');

        return view('administration.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-roles-permissions')) {
            abort(403, 'Unauthorized to create roles.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        // Assign permissions
        if ($request->permissions) {
            $role->permissions()->attach($request->permissions);
        }

        return redirect()
            ->route('administration.roles.show', $role)
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): View
    {
        if (!auth()->user()->can('manage-roles-permissions')) {
            abort(403, 'Unauthorized to view role details.');
        }

        $role->load(['permissions', 'users']);
        $permissionsByCategory = $role->permissions->groupBy('category');

        return view('administration.roles.show', compact('role', 'permissionsByCategory'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): View
    {
        if (!auth()->user()->can('manage-roles-permissions')) {
            abort(403, 'Unauthorized to edit roles.');
        }

        $permissions = Permission::orderBy('category')->orderBy('display_name')->get()->groupBy('category');
        $role->load('permissions');

        return view('administration.roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        if (!auth()->user()->can('manage-roles-permissions')) {
            abort(403, 'Unauthorized to edit roles.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean',
        ]);

        $role->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        // Sync permissions
        $role->permissions()->sync($request->permissions ?? []);

        return redirect()
            ->route('administration.roles.show', $role)
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if (!auth()->user()->can('manage-roles-permissions')) {
            abort(403, 'Unauthorized to delete roles.');
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete role that has assigned users.');
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()
            ->route('administration.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Toggle role status.
     */
    public function toggleStatus(Role $role): RedirectResponse
    {
        if (!auth()->user()->can('manage-roles-permissions')) {
            abort(403, 'Unauthorized to modify roles.');
        }

        $role->update(['is_active' => !$role->is_active]);

        $status = $role->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Role {$status} successfully.");
    }

    /**
     * Display permissions listing.
     */
    public function permissions(Request $request): View
    {
        if (!auth()->user()->can('manage-roles-permissions')) {
            abort(403, 'Unauthorized to manage permissions.');
        }

        $query = Permission::with('roles');

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('display_name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $permissions = $query->orderBy('category')->orderBy('display_name')->paginate(20);
        $categories = Permission::distinct()->orderBy('category')->pluck('category');

        // Calculate statistics
        $statistics = [
            'total_permissions' => Permission::count(),
            'categories_count' => Permission::distinct('category')->count(),
            'assigned_permissions' => Permission::whereHas('roles')->count(),
            'unassigned_permissions' => Permission::whereDoesntHave('roles')->count(),
        ];

        return view('administration.permissions.index', compact('permissions', 'categories', 'statistics'));
    }
}
