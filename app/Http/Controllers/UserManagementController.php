<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): View
    {
        // Check authorization
        if (!auth()->user()->can('manage-users')) {
            abort(403, 'Unauthorized to manage users.');
        }

        $query = User::with('roles');

        // Filter by role
        if ($request->has('role_id') && $request->role_id) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('id', $request->role_id);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name or email
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->orderBy('name')->paginate(15);
        $roles = Role::active()->orderBy('display_name')->get();

        // Calculate statistics
        $statistics = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'roles_count' => Role::count(),
        ];

        return view('administration.users.index', compact('users', 'roles', 'statistics'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        if (!auth()->user()->can('manage-users')) {
            abort(403, 'Unauthorized to create users.');
        }

        $roles = Role::where('is_active', true)->orderBy('display_name')->get();

        return view('administration.users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-users')) {
            abort(403, 'Unauthorized to create users.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->has('is_active'),
        ]);

        // Assign roles
        if ($request->roles) {
            $user->roles()->attach($request->roles);
        }

        return redirect()
            ->route('administration.users.show', $user)
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): View
    {
        if (!auth()->user()->can('manage-users')) {
            abort(403, 'Unauthorized to view user details.');
        }

        $user->load('roles.permissions');

        return view('administration.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        if (!auth()->user()->can('manage-users')) {
            abort(403, 'Unauthorized to edit users.');
        }

        $roles = Role::where('is_active', true)->orderBy('display_name')->get();
        $user->load('roles');

        return view('administration.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        if (!auth()->user()->can('manage-users')) {
            abort(403, 'Unauthorized to edit users.');
        }

        // Debug logging
        \Log::info('User update attempt', [
            'user_id' => $user->id,
            'request_roles' => $request->roles,
            'request_all' => $request->all(),
        ]);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'roles' => 'nullable|array',
            'roles.*' => 'nullable|exists:roles,id',
            'is_active' => 'nullable|boolean',
        ];

        if ($request->password) {
            $rules['password'] = ['confirmed', Password::defaults()];
        }

        try {
            $request->validate($rules);
            \Log::info('Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->has('is_active'),
        ];

        if ($request->password) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Sync roles
        $rolesToSync = $request->roles ?? [];
        \Log::info('Syncing roles', ['roles_to_sync' => $rolesToSync]);
        $user->roles()->sync($rolesToSync);
        \Log::info('Roles synced successfully', ['user_roles_after' => $user->fresh()->roles->pluck('id')->toArray()]);

        return redirect()
            ->route('administration.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        if (!auth()->user()->can('manage-users')) {
            abort(403, 'Unauthorized to delete users.');
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with('error', 'You cannot delete your own account.');
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()
            ->route('administration.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if (!auth()->user()->can('manage-users')) {
            abort(403, 'Unauthorized to modify users.');
        }

        // Prevent self-deactivation
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "User {$status} successfully.");
    }
}