<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Department;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index(Request $request): View
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to view departments.');
        }

        $query = Department::query();

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name or code
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('head_of_department', 'like', '%' . $request->search . '%');
            });
        }

        $departments = $query->orderBy('name')->paginate(15);

        // Calculate statistics
        $statistics = [
            'total_departments' => Department::count(),
            'active_departments' => Department::where('is_active', true)->count(),
            'inactive_departments' => Department::where('is_active', false)->count(),
            'total_budget' => Department::where('is_active', true)->sum('budget_allocation'),
        ];

        return view('administration.departments.index', compact('departments', 'statistics'));
    }

    /**
     * Show the form for creating a new department.
     */
    public function create(): View
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to create departments.');
        }

        return view('administration.departments.create');
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to create departments.');
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:departments',
                'description' => 'nullable|string',
                'head_of_department' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'budget_allocation' => 'nullable|numeric|min:0',
            ]);

            $department = Department::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'head_of_department' => $request->head_of_department,
                'email' => $request->email,
                'phone' => $request->phone,
                'budget_allocation' => $request->budget_allocation,
                'is_active' => $request->has('is_active'),
            ]);

            return redirect()
                ->route('administration.departments.show', $department)
                ->with('success', 'Department created successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create department: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department): View
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to view department details.');
        }

        $department->load('contracts');

        return view('administration.departments.show', compact('department'));
    }

    /**
     * Show the form for editing the specified department.
     */
    public function edit(Department $department): View
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to edit departments.');
        }

        return view('administration.departments.edit', compact('department'));
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to edit departments.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'head_of_department' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'budget_allocation' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $department->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'head_of_department' => $request->head_of_department,
            'email' => $request->email,
            'phone' => $request->phone,
            'budget_allocation' => $request->budget_allocation,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()
            ->route('administration.departments.show', $department)
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department): RedirectResponse
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to delete departments.');
        }

        // Check if department has contracts
        if ($department->contracts()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete department that has assigned contracts.');
        }

        $department->delete();

        return redirect()
            ->route('administration.departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    /**
     * Toggle department status.
     */
    public function toggleStatus(Department $department): RedirectResponse
    {
        if (!auth()->user()->can('manage-departments')) {
            abort(403, 'Unauthorized to modify departments.');
        }

        $department->update(['is_active' => !$department->is_active]);

        $status = $department->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Department {$status} successfully.");
    }
}
