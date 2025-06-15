<?php

namespace Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HR\Http\Requests\StoreEmployeeRequest;
use Modules\HR\Http\Requests\UpdateEmployeeRequest;
use Modules\HR\Models\Employee;
use Modules\AssetManager\Models\Asset;
use Modules\Payroll\Services\FinalPayrollService;
use Carbon\Carbon;

/**
 * Employee Controller
 *
 * Handles CRUD operations for employee management including off-boarding workflows.
 *
 * @author Dev Agent
 */
class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees.
     */
    public function index(): View
    {
        $employees = Employee::active()->latest()->get();

        return view('hr::employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): View
    {
        return view('hr::employees.create');
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return redirect()
            ->route('hr.employees.index')
            ->with('success', "Employee {$employee->name} created successfully.");
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee): View
    {
        $employee->load(['documents', 'salaryHistories']);

        return view('hr::employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee): View
    {
        return view('hr::employees.edit', compact('employee'));
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $validatedData = $request->validated();

        // If salary is being changed and a reason is provided, temporarily set it on the model
        // The observer will pick this up and include it in the salary history record
        if (isset($validatedData['salary_change_reason'])) {
            $employee->salary_change_reason = $validatedData['salary_change_reason'];
            // Don't include it in the update data as it's not a database field
            unset($validatedData['salary_change_reason']);
        }

        $employee->update($validatedData);

        return redirect()
            ->route('hr.employees.index')
            ->with('success', "Employee {$employee->name} updated successfully.");
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()
            ->route('hr.employees.index')
            ->with('success', "Employee {$employee->name} deleted successfully.");
    }

    /**
     * Show the off-boarding page for the specified employee.
     */
    public function showOffboarding(Employee $employee): View
    {
        // Load employee's current assets
        $employee->load(['currentAssets']);

        // Get final payroll calculation
        $finalPayrollService = new FinalPayrollService();
        $payrollCalculation = $finalPayrollService->calculateFinalPay($employee, Carbon::now());

        return view('hr::employees.offboarding', compact('employee', 'payrollCalculation'));
    }

    /**
     * Process the off-boarding for the specified employee.
     */
    public function processOffboarding(Request $request, Employee $employee): RedirectResponse
    {
        $request->validate([
            'termination_date' => 'required|date|before_or_equal:today',
            'status' => 'required|in:terminated,resigned',
            'notes' => 'nullable|string|max:1000',
        ]);

        $terminationDate = Carbon::parse($request->termination_date);

        // Calculate final payroll
        $finalPayrollService = new FinalPayrollService();
        $payrollCalculation = $finalPayrollService->calculateFinalPay($employee, $terminationDate);

        // Update employee status and termination date
        $employee->update([
            'status' => $request->status,
            'termination_date' => $terminationDate,
        ]);

        // Mark all current assets as returned
        $currentAssets = $employee->currentAssets;
        foreach ($currentAssets as $asset) {
            $employee->assets()->updateExistingPivot($asset->id, [
                'returned_date' => $terminationDate,
                'notes' => $request->notes ?? 'Returned during off-boarding process',
            ]);

            // Update asset status to available
            $asset->update(['status' => 'available']);
        }

        // Store final payroll calculation
        $finalPayrollService->storeFinalPayrollCalculation($payrollCalculation);

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('success', "Employee {$employee->name} has been successfully processed for {$request->status}. Final pay calculation: $" . number_format($payrollCalculation['pro_rated_amount'], 2));
    }
}
