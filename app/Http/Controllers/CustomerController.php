<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Modules\Payroll\Models\JiraWorklog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): View
    {
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to view customers.');
        }

        $query = Customer::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('company_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        $customers = $query->withCount(['contracts' => function($q) {
            $q->where('status', 'active');
        }])->orderBy('name')->paginate(15);

        // Calculate statistics
        $statistics = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'company_customers' => Customer::where('type', 'company')->count(),
            'individual_customers' => Customer::where('type', 'individual')->count(),
        ];

        return view('administration.customers.index', compact('customers', 'statistics'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): View
    {
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to create customers.');
        }

        return view('administration.customers.create');
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to create customers.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'type' => 'required|in:individual,company',
        ]);

        try {
            $customer = Customer::create($request->all());

            return redirect()
                ->route('administration.customers.show', $customer)
                ->with('success', 'Customer created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create customer: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified customer.
     */
    public function show(Request $request, Customer $customer): View
    {
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to view customer details.');
        }

        // Get year filter (default: current year, 'lifetime' for all)
        $selectedYear = $request->get('year', date('Y'));
        $isLifetime = $selectedYear === 'lifetime';

        // Build date range for filtering
        $startDate = $isLifetime ? null : Carbon::create($selectedYear, 1, 1)->startOfDay();
        $endDate = $isLifetime ? null : Carbon::create($selectedYear, 12, 31)->endOfDay();

        // Load contracts with filtering
        $contractsQuery = $customer->contracts()->with('products')->orderBy('created_at', 'desc');
        if (!$isLifetime) {
            $contractsQuery->where(function($q) use ($startDate, $endDate) {
                // Contract overlaps with the year
                $q->where(function($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $endDate)
                       ->where(function($q3) use ($startDate) {
                           $q3->whereNull('end_date')
                              ->orWhere('end_date', '>=', $startDate);
                       });
                })->orWhere(function($q2) use ($startDate, $endDate) {
                    // Or was created in the year
                    $q2->whereBetween('created_at', [$startDate, $endDate]);
                });
            });
        }
        $contracts = $contractsQuery->get();

        // Load projects with total hours
        $projectsQuery = $customer->projects()->active()->orderBy('name');
        $projects = $projectsQuery->get();

        // Calculate hours for each project
        foreach ($projects as $project) {
            $hoursQuery = JiraWorklog::where('issue_key', 'LIKE', $project->code . '-%');
            if (!$isLifetime) {
                $hoursQuery->whereBetween('worklog_started', [$startDate, $endDate]);
            }
            $project->filtered_hours = $hoursQuery->sum('time_spent_hours');
        }

        // Load invoices with filtering
        $invoicesQuery = $customer->invoices()->with('project')->orderBy('invoice_date', 'desc');
        if (!$isLifetime) {
            $invoicesQuery->whereBetween('invoice_date', [$startDate, $endDate]);
        }
        $invoices = $invoicesQuery->get();

        // Get available years for dropdown (from contracts, invoices, projects)
        $years = collect();

        // Years from contracts
        $contractYears = $customer->contracts()
            ->selectRaw('YEAR(start_date) as year')
            ->whereNotNull('start_date')
            ->distinct()
            ->pluck('year');
        $years = $years->merge($contractYears);

        // Years from invoices
        $invoiceYears = $customer->invoices()
            ->selectRaw('YEAR(invoice_date) as year')
            ->whereNotNull('invoice_date')
            ->distinct()
            ->pluck('year');
        $years = $years->merge($invoiceYears);

        // Add current year if not present
        $currentYear = (int) date('Y');
        $years = $years->push($currentYear)->unique()->filter()->sort()->reverse()->values();

        // Calculate totals
        $totals = [
            'projects_count' => $projects->count(),
            'projects_hours' => $projects->sum('filtered_hours'),
            'contracts_count' => $contracts->count(),
            'contracts_value' => $contracts->sum('total_amount'),
            'invoices_count' => $invoices->count(),
            'invoices_value' => $invoices->sum('total_amount'),
            'invoices_paid' => $invoices->sum('paid_amount'),
        ];

        return view('administration.customers.show', compact(
            'customer',
            'projects',
            'contracts',
            'invoices',
            'years',
            'selectedYear',
            'totals'
        ));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer): View
    {
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to edit customers.');
        }

        return view('administration.customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to edit customers.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'type' => 'required|in:individual,company',
        ]);

        try {
            $customer->update($request->all());

            return redirect()
                ->route('administration.customers.show', $customer)
                ->with('success', 'Customer updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update customer: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to delete customers.');
        }

        if ($customer->contracts()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete customer with existing contracts.']);
        }

        try {
            $customer->delete();
            return redirect()
                ->route('administration.customers.index')
                ->with('success', 'Customer deleted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete customer: ' . $e->getMessage()]);
        }
    }

    /**
     * API endpoint for customer dropdown (AJAX)
     */
    public function apiIndex(Request $request): JsonResponse
    {
        try {
            $query = Customer::active()->distinct()->orderBy('name');

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('company_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
                // Limit results when searching to prevent too many matches
                $query->limit(50);
            }

            // No limit when loading all customers for dropdown
            $customers = $query->get(['id', 'name', 'company_name', 'email', 'type']);

            return response()->json([
                'success' => true,
                'customers' => $customers->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'text' => $customer->display_name . ($customer->email ? ' (' . $customer->email . ')' : ''),
                        'name' => $customer->name,
                        'company_name' => $customer->company_name,
                        'email' => $customer->email,
                        'type' => $customer->type,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            \Log::error('Customer API Index Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'customers' => [],
                'error' => 'Failed to fetch customers'
            ], 500);
        }
    }

    /**
     * Store customer via AJAX (for inline creation)
     */
    public function apiStore(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage-customers')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'type' => 'required|in:individual,company',
        ]);

        try {
            // Check for existing customer with same name/company to prevent duplicates
            $existingQuery = Customer::active();
            if ($request->type === 'company' && $request->company_name) {
                $existingQuery->where('company_name', $request->company_name);
            } else {
                $existingQuery->where('name', $request->name)
                    ->where('type', $request->type);
            }

            $existing = $existingQuery->first();
            if ($existing) {
                // Return the existing customer instead of creating a duplicate
                return response()->json([
                    'success' => true,
                    'customer' => [
                        'id' => $existing->id,
                        'text' => $existing->display_name . ($existing->email ? ' (' . $existing->email . ')' : ''),
                        'name' => $existing->name,
                        'company_name' => $existing->company_name,
                        'email' => $existing->email,
                        'type' => $existing->type,
                    ],
                    'message' => 'Customer already exists and has been selected.',
                    'existing' => true
                ]);
            }

            $customer = Customer::create([
                'name' => $request->name,
                'email' => $request->email,
                'company_name' => $request->company_name,
                'type' => $request->type,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'text' => $customer->display_name . ($customer->email ? ' (' . $customer->email . ')' : ''),
                    'name' => $customer->name,
                    'company_name' => $customer->company_name,
                    'email' => $customer->email,
                    'type' => $customer->type,
                ],
                'message' => 'Customer created successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show CSV import form for customers.
     */
    public function importForm(): View
    {
        // Check authorization
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to import customers.');
        }

        return view('administration.customers.import');
    }

    /**
     * Process CSV import for customers.
     */
    public function import(Request $request): RedirectResponse
    {
        // Check authorization
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to import customers.');
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getPathname()));

            // Remove header row
            $header = array_shift($csvData);

            // Validate header format
            $expectedHeader = ['name', 'email', 'phone', 'address', 'company_name', 'tax_id', 'website', 'contact_persons', 'notes', 'type'];
            if (count(array_intersect($header, $expectedHeader)) < 2) { // At least name and type required
                return redirect()->back()
                    ->with('error', 'Invalid CSV format. Please download the sample CSV and follow the format.');
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($csvData as $rowIndex => $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Map CSV row to array using header
                    $data = array_combine($header, $row);

                    // Validate required fields
                    if (empty($data['name'])) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing required field 'name'";
                        $errorCount++;
                        continue;
                    }

                    // Validate email format if provided
                    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Invalid email format '" . $data['email'] . "'";
                        $errorCount++;
                        continue;
                    }

                    // Check for duplicate emails if provided
                    if (!empty($data['email']) && \App\Models\Customer::where('email', trim($data['email']))->exists()) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Email '" . trim($data['email']) . "' already exists";
                        $errorCount++;
                        continue;
                    }

                    // Parse contact persons if provided (JSON format expected)
                    $contactPersons = null;
                    if (!empty($data['contact_persons'])) {
                        $contactPersons = json_decode($data['contact_persons'], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            // If not valid JSON, treat as simple text
                            $contactPersons = [$data['contact_persons']];
                        }
                    }

                    // Validate type
                    $type = !empty($data['type']) ? $data['type'] : 'individual';
                    if (!in_array($type, ['individual', 'company'])) {
                        $type = 'individual'; // Default fallback
                    }

                    // Create the customer record
                    $customerData = [
                        'name' => trim($data['name']),
                        'email' => !empty($data['email']) ? trim($data['email']) : null,
                        'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
                        'address' => !empty($data['address']) ? trim($data['address']) : null,
                        'company_name' => !empty($data['company_name']) ? trim($data['company_name']) : null,
                        'tax_id' => !empty($data['tax_id']) ? trim($data['tax_id']) : null,
                        'website' => !empty($data['website']) ? trim($data['website']) : null,
                        'contact_persons' => $contactPersons,
                        'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
                        'type' => $type,
                        'status' => 'active',
                    ];

                    \App\Models\Customer::create($customerData);
                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }

            $message = "{$successCount} customers imported successfully.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} errors occurred.";
            }

            $messageType = $errorCount > 0 ? 'warning' : 'success';

            return redirect()->route('administration.customers.index')
                ->with($messageType, $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error processing CSV file: ' . $e->getMessage());
        }
    }

    /**
     * Download sample CSV file for customers import.
     */
    public function downloadSample()
    {
        // Check authorization
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to download sample files.');
        }

        $headers = ['name', 'email', 'phone', 'address', 'company_name', 'tax_id', 'website', 'contact_persons', 'notes', 'type'];

        $sampleData = [
            [
                'John Smith',
                'john@example.com',
                '+1-555-0123',
                '123 Main St, City, State 12345',
                '',
                '',
                '',
                '["John Smith - CEO"]',
                'Individual client for web development',
                'individual'
            ],
            [
                'Jane Doe',
                'jane@techcorp.com',
                '+1-555-0456',
                '456 Business Ave, Corporate City, State 67890',
                'TechCorp Solutions Ltd',
                'TAX123456',
                'https://techcorp.com',
                '["Jane Doe - CTO", "Mike Johnson - Project Manager"]',
                'Corporate client for enterprise solutions',
                'company'
            ],
            [
                'Ahmed Hassan',
                'ahmed@startupinc.com',
                '+1-555-0789',
                '789 Innovation Blvd, Tech Hub, State 54321',
                'Startup Inc',
                'TAX789012',
                'https://startupinc.com',
                '["Ahmed Hassan - Founder"]',
                'Startup client for mobile app development',
                'company'
            ]
        ];

        $csvContent = implode(',', $headers) . "\n";
        foreach ($sampleData as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }

        $filename = 'customers_sample_' . date('Y-m-d') . '.csv';

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Show the merge customers page.
     */
    public function mergeForm(Request $request): View
    {
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to merge customers.');
        }

        // Get all customers for selection
        $customers = Customer::withCount(['contracts', 'projects', 'invoices'])
            ->orderBy('name')
            ->get();

        // Pre-selected customers (from query string)
        $selectedIds = $request->get('ids', []);
        if (is_string($selectedIds)) {
            $selectedIds = explode(',', $selectedIds);
        }

        $selectedCustomers = [];
        if (!empty($selectedIds)) {
            $selectedCustomers = Customer::whereIn('id', $selectedIds)
                ->withCount(['contracts', 'projects', 'invoices'])
                ->orderBy('id')
                ->get();
        }

        return view('administration.customers.merge', compact('customers', 'selectedCustomers', 'selectedIds'));
    }

    /**
     * Preview merge results via AJAX.
     */
    public function mergePreview(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage-customers')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $customerIds = $request->input('customer_ids', []);

        if (count($customerIds) < 2) {
            return response()->json(['error' => 'Please select at least 2 customers to merge.'], 400);
        }

        $customers = Customer::whereIn('id', $customerIds)
            ->orderBy('id')
            ->get();

        if ($customers->count() < 2) {
            return response()->json(['error' => 'Invalid customer selection.'], 400);
        }

        $primary = $customers->first();
        $duplicates = $customers->slice(1);

        // Calculate what will be transferred
        $preview = [
            'primary' => [
                'id' => $primary->id,
                'name' => $primary->name,
                'company_name' => $primary->company_name,
                'email' => $primary->email,
            ],
            'duplicates' => [],
            'totals' => [
                'contracts' => 0,
                'projects' => 0,
                'invoices' => 0,
                'estimates' => 0,
                'credit_notes' => 0,
            ],
        ];

        foreach ($duplicates as $dup) {
            $contracts = \Modules\Accounting\Models\Contract::where('customer_id', $dup->id)->count();
            $projects = \Modules\Project\Models\Project::where('customer_id', $dup->id)->count();
            $invoices = \Modules\Invoicing\Models\Invoice::where('customer_id', $dup->id)->count();
            $estimates = \Modules\Accounting\Models\Estimate::where('customer_id', $dup->id)->count();
            $creditNotes = \Modules\Accounting\Models\CreditNote::where('customer_id', $dup->id)->count();

            $preview['duplicates'][] = [
                'id' => $dup->id,
                'name' => $dup->name,
                'company_name' => $dup->company_name,
                'email' => $dup->email,
                'contracts' => $contracts,
                'projects' => $projects,
                'invoices' => $invoices,
                'estimates' => $estimates,
                'credit_notes' => $creditNotes,
            ];

            $preview['totals']['contracts'] += $contracts;
            $preview['totals']['projects'] += $projects;
            $preview['totals']['invoices'] += $invoices;
            $preview['totals']['estimates'] += $estimates;
            $preview['totals']['credit_notes'] += $creditNotes;
        }

        return response()->json(['success' => true, 'preview' => $preview]);
    }

    /**
     * Process the merge of customers.
     */
    public function merge(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('manage-customers')) {
            abort(403, 'Unauthorized to merge customers.');
        }

        $customerIds = $request->input('customer_ids', []);

        if (count($customerIds) < 2) {
            return redirect()->back()->with('error', 'Please select at least 2 customers to merge.');
        }

        // Get customers ordered by ID (earliest = primary)
        $customers = Customer::whereIn('id', $customerIds)
            ->orderBy('id')
            ->get();

        if ($customers->count() < 2) {
            return redirect()->back()->with('error', 'Invalid customer selection.');
        }

        $primary = $customers->first();
        $duplicates = $customers->slice(1);
        $duplicateIds = $duplicates->pluck('id')->toArray();

        DB::beginTransaction();

        try {
            // 1. Transfer all related records to primary customer
            $transferredCounts = [
                'contracts' => 0,
                'projects' => 0,
                'invoices' => 0,
                'estimates' => 0,
                'credit_notes' => 0,
                'expense_import_rows' => 0,
            ];

            // Contracts
            $transferredCounts['contracts'] = \Modules\Accounting\Models\Contract::whereIn('customer_id', $duplicateIds)
                ->update(['customer_id' => $primary->id]);

            // Projects
            $transferredCounts['projects'] = \Modules\Project\Models\Project::whereIn('customer_id', $duplicateIds)
                ->update(['customer_id' => $primary->id]);

            // Invoices
            $transferredCounts['invoices'] = \Modules\Invoicing\Models\Invoice::whereIn('customer_id', $duplicateIds)
                ->update(['customer_id' => $primary->id]);

            // Estimates
            $transferredCounts['estimates'] = \Modules\Accounting\Models\Estimate::whereIn('customer_id', $duplicateIds)
                ->update(['customer_id' => $primary->id]);

            // Credit Notes
            $transferredCounts['credit_notes'] = \Modules\Accounting\Models\CreditNote::whereIn('customer_id', $duplicateIds)
                ->update(['customer_id' => $primary->id]);

            // Expense Import Rows
            $transferredCounts['expense_import_rows'] = \Modules\Accounting\Models\ExpenseImportRow::whereIn('customer_id', $duplicateIds)
                ->update(['customer_id' => $primary->id]);

            // 2. Merge customer information (fill in missing fields from duplicates)
            $fieldsToMerge = ['email', 'phone', 'address', 'company_name', 'tax_id', 'website', 'perfex_id'];

            foreach ($duplicates as $dup) {
                foreach ($fieldsToMerge as $field) {
                    if (empty($primary->$field) && !empty($dup->$field)) {
                        $primary->$field = $dup->$field;
                    }
                }
            }

            // 3. Merge contact_persons (combine arrays, remove duplicates by serialization)
            $allContacts = $primary->contact_persons ?? [];
            if (!is_array($allContacts)) {
                $allContacts = [];
            }
            foreach ($duplicates as $dup) {
                if (!empty($dup->contact_persons) && is_array($dup->contact_persons)) {
                    $allContacts = array_merge($allContacts, $dup->contact_persons);
                }
            }
            // Remove duplicates by comparing serialized values
            $uniqueContacts = [];
            $seen = [];
            foreach ($allContacts as $contact) {
                $key = is_array($contact) ? json_encode($contact) : (string)$contact;
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $uniqueContacts[] = $contact;
                }
            }
            $primary->contact_persons = $uniqueContacts;

            // 4. Concatenate notes
            $allNotes = [$primary->notes];
            foreach ($duplicates as $dup) {
                if (!empty($dup->notes)) {
                    $allNotes[] = "--- Merged from Customer #{$dup->id} ({$dup->display_name}) ---\n{$dup->notes}";
                }
            }
            $primary->notes = implode("\n\n", array_filter($allNotes));

            // Save primary customer
            $primary->save();

            // 5. Delete duplicate customers
            $deletedCount = Customer::whereIn('id', $duplicateIds)->delete();

            DB::commit();

            // Build success message
            $transferDetails = [];
            foreach ($transferredCounts as $type => $count) {
                if ($count > 0) {
                    $transferDetails[] = "{$count} " . str_replace('_', ' ', $type);
                }
            }

            $message = "Successfully merged {$deletedCount} customer(s) into {$primary->display_name} (ID: {$primary->id}).";
            if (!empty($transferDetails)) {
                $message .= " Transferred: " . implode(', ', $transferDetails) . ".";
            }

            Log::info('Customers merged', [
                'primary_id' => $primary->id,
                'merged_ids' => $duplicateIds,
                'transferred' => $transferredCounts,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('administration.customers.show', $primary)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Customer merge failed', [
                'customer_ids' => $customerIds,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('error', 'Merge failed: ' . $e->getMessage());
        }
    }
}