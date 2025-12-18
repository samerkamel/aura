<?php

namespace Modules\SelfService\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\HR\Models\Employee;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasEmployee
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has a linked employee record.
     * The employee is linked via matching email addresses.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Find employee by matching email
        $employee = Employee::where('email', $user->email)->first();

        if (!$employee) {
            // If user doesn't have an employee record, show error
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No employee record found for your account. Please contact HR.',
                ], 403);
            }

            return redirect()->route('home')->with('error',
                'No employee record found for your account. Please contact HR to set up your employee profile.'
            );
        }

        // Check if employee is active
        if ($employee->status !== 'active') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your employee account is not active. Please contact HR.',
                ], 403);
            }

            return redirect()->route('home')->with('error',
                'Your employee account is not active. Please contact HR.'
            );
        }

        // Store employee in request for easy access in controllers
        $request->merge(['current_employee' => $employee]);

        // Also make it available as a request attribute
        $request->attributes->set('employee', $employee);

        return $next($request);
    }
}
