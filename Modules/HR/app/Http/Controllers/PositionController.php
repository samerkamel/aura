<?php

namespace Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\HR\Models\Position;

/**
 * Position Controller
 *
 * Handles CRUD operations for employee positions/job titles.
 * Salary range information is restricted based on permissions.
 *
 * @author Dev Agent
 */
class PositionController extends Controller
{
    /**
     * Display a listing of positions.
     */
    public function index(Request $request): View
    {
        $query = Position::query()->withCount('employees');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('title_ar', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $positions = $query->ordered()->paginate(15)->withQueryString();

        // Get unique departments for filter dropdown
        $departments = Position::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values();

        // Check if user can view salary information
        $canViewSalary = Gate::allows('view-employee-financial');

        return view('hr::positions.index', compact('positions', 'departments', 'canViewSalary'));
    }

    /**
     * Show the form for creating a new position.
     */
    public function create(): View
    {
        $canEditSalary = Gate::allows('edit-employee-financial');

        return view('hr::positions.create', [
            'levels' => Position::LEVELS,
            'canEditSalary' => $canEditSalary,
        ]);
    }

    /**
     * Store a newly created position.
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'title' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'level' => 'nullable|string|in:' . implode(',', array_keys(Position::LEVELS)),
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        // Only validate salary fields if user has permission
        if (Gate::allows('edit-employee-financial')) {
            $rules['min_salary'] = 'nullable|numeric|min:0';
            $rules['max_salary'] = 'nullable|numeric|min:0|gte:min_salary';
        }

        $validated = $request->validate($rules);

        // Remove salary fields if user doesn't have permission
        if (!Gate::allows('edit-employee-financial')) {
            unset($validated['min_salary'], $validated['max_salary']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Position::create($validated);

        return redirect()->route('hr.positions.index')
            ->with('success', 'Position created successfully.');
    }

    /**
     * Display the specified position.
     */
    public function show(Position $position): View
    {
        $position->load(['employees' => function ($query) {
            $query->orderBy('name');
        }]);

        $canViewSalary = Gate::allows('view-employee-financial');

        return view('hr::positions.show', compact('position', 'canViewSalary'));
    }

    /**
     * Show the form for editing the specified position.
     */
    public function edit(Position $position): View
    {
        $canEditSalary = Gate::allows('edit-employee-financial');

        return view('hr::positions.edit', [
            'position' => $position,
            'levels' => Position::LEVELS,
            'canEditSalary' => $canEditSalary,
        ]);
    }

    /**
     * Update the specified position.
     */
    public function update(Request $request, Position $position): RedirectResponse
    {
        $rules = [
            'title' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'level' => 'nullable|string|in:' . implode(',', array_keys(Position::LEVELS)),
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        // Only validate salary fields if user has permission
        if (Gate::allows('edit-employee-financial')) {
            $rules['min_salary'] = 'nullable|numeric|min:0';
            $rules['max_salary'] = 'nullable|numeric|min:0|gte:min_salary';
        }

        $validated = $request->validate($rules);

        // Remove salary fields if user doesn't have permission
        if (!Gate::allows('edit-employee-financial')) {
            unset($validated['min_salary'], $validated['max_salary']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $position->update($validated);

        return redirect()->route('hr.positions.index')
            ->with('success', 'Position updated successfully.');
    }

    /**
     * Remove the specified position.
     */
    public function destroy(Position $position): RedirectResponse
    {
        // Check if position has employees assigned
        if ($position->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete position with assigned employees. Please reassign them first.');
        }

        $position->delete();

        return redirect()->route('hr.positions.index')
            ->with('success', 'Position deleted successfully.');
    }

    /**
     * Toggle the active status of a position.
     */
    public function toggleStatus(Position $position): RedirectResponse
    {
        $position->update(['is_active' => !$position->is_active]);

        $status = $position->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Position {$status} successfully.");
    }
}
