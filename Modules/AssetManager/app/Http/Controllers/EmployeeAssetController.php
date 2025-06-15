<?php

namespace Modules\AssetManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\AssetManager\Models\Asset;
use Modules\HR\Models\Employee;
use Carbon\Carbon;

/**
 * EmployeeAssetController
 *
 * Handles the assignment and un-assignment of assets to employees.
 * Manages the many-to-many relationship between employees and assets.
 *
 * @author Dev Agent
 */
class EmployeeAssetController extends Controller
{
    /**
     * Assign an asset to an employee
     *
     * @param Request $request
     * @param Employee $employee
     * @return JsonResponse|RedirectResponse
     */
    public function assign(Request $request, Employee $employee)
    {
        $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'assigned_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'asset_id.required' => 'Please select an asset.',
            'asset_id.exists' => 'The selected asset does not exist.',
            'assigned_date.required' => 'Assignment date is required.',
            'assigned_date.date' => 'Please enter a valid assignment date.',
            'assigned_date.before_or_equal' => 'Assignment date cannot be in the future.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ]);

        $asset = Asset::findOrFail($request->asset_id);

        // Check if asset is already assigned
        if ($asset->isAssigned()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This asset is already assigned to another employee.'
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'This asset is already assigned to another employee.');
        }

        // Check if asset is available for assignment
        if ($asset->status !== 'available') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This asset is not available for assignment.'
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'This asset is not available for assignment.');
        }

        // Assign asset to employee
        $asset->employees()->attach($employee->id, [
            'assigned_date' => $request->assigned_date,
            'notes' => $request->notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update asset status to assigned
        $asset->update(['status' => 'assigned']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Asset '{$asset->name}' has been assigned to {$employee->name}."
            ]);
        }

        return redirect()->back()
            ->with('success', "Asset '{$asset->name}' has been assigned to {$employee->name}.");
    }

    /**
     * Un-assign an asset from an employee
     *
     * @param Request $request
     * @param Employee $employee
     * @return JsonResponse|RedirectResponse
     */
    public function unassign(Request $request, Employee $employee)
    {
        $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'returned_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'asset_id.required' => 'Asset ID is required.',
            'asset_id.exists' => 'The selected asset does not exist.',
            'returned_date.required' => 'Return date is required.',
            'returned_date.date' => 'Please enter a valid return date.',
            'returned_date.before_or_equal' => 'Return date cannot be in the future.',
            'notes.max' => 'Return notes cannot exceed 500 characters.',
        ]);

        $asset = Asset::findOrFail($request->asset_id);

        // Find the current assignment (where returned_date is null)
        $assignment = $asset->employees()
            ->where('employee_id', $employee->id)
            ->wherePivotNull('returned_date')
            ->first();

        if (!$assignment) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This asset is not currently assigned to this employee.'
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'This asset is not currently assigned to this employee.');
        }

        // Validate that return date is not before assignment date
        $assignedDate = Carbon::parse($assignment->pivot->assigned_date);
        $returnDate = Carbon::parse($request->returned_date);

        if ($returnDate->lt($assignedDate)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return date cannot be before the assignment date.'
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'Return date cannot be before the assignment date.');
        }

        // Update the pivot record with return information
        $asset->employees()->updateExistingPivot($employee->id, [
            'returned_date' => $request->returned_date,
            'notes' => $assignment->pivot->notes . ($request->notes ?
                "\n\nReturn notes: " . $request->notes : ''),
            'updated_at' => now(),
        ]);

        // Update asset status back to available
        $asset->update(['status' => 'available']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Asset '{$asset->name}' has been returned by {$employee->name}."
            ]);
        }

        return redirect()->back()
            ->with('success', "Asset '{$asset->name}' has been returned by {$employee->name}.");
    }

    /**
     * Show assignment form for a specific asset
     *
     * @param Asset $asset
     * @return View
     */
    public function showAssignForm(Asset $asset): View
    {
        // Only show form for available assets
        if ($asset->status !== 'available') {
            abort(404, 'Asset is not available for assignment.');
        }

        $employees = Employee::orderBy('name')->get();

        return view('assetmanager::assignments.assign', compact('asset', 'employees'));
    }

    /**
     * Show unassign form for a specific asset and employee
     *
     * @param Asset $asset
     * @param Employee $employee
     * @return View
     */
    public function showUnassignForm(Asset $asset, Employee $employee): View
    {
        // Verify that the asset is assigned to this employee
        $assignment = $asset->employees()
            ->where('employee_id', $employee->id)
            ->wherePivotNull('returned_date')
            ->first();

        if (!$assignment) {
            abort(404, 'Asset is not assigned to this employee.');
        }

        return view('assetmanager::assignments.unassign', compact('asset', 'employee', 'assignment'));
    }
}
