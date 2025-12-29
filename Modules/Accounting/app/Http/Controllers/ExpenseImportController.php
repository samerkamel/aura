<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Accounting\Models\ExpenseImport;
use Modules\Accounting\Models\ExpenseImportRow;
use Modules\Accounting\Models\ExpenseType;
use Modules\Accounting\Models\ExpenseCategory;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Accounting\Models\Account;
use Modules\Invoicing\Models\Invoice;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseImportController extends Controller
{
    /**
     * Account column mapping from CSV headers to account names.
     */
    private array $accountColumns = [
        'Samer' => 'Samer',
        'Simon' => 'Simon',
        'Fadi' => 'Fadi',
        'Adel' => 'Adel',
        'CapEx Cash' => 'CapEx Cash',
        'Cash' => 'Cash',
        'Bank (QNB)EGP' => 'Bank (QNB)EGP',
        'Margins' => 'Margins',
    ];

    /**
     * Display a listing of imports.
     */
    public function index(): View
    {
        $imports = ExpenseImport::with('createdBy')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('accounting::expense-imports.index', compact('imports'));
    }

    /**
     * Show the form for uploading a new file.
     */
    public function create(): View
    {
        return view('accounting::expense-imports.create');
    }

    /**
     * Parse uploaded file and create import session.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'notes' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        // Create import record
        $import = ExpenseImport::create([
            'file_name' => $fileName,
            'status' => 'parsing',
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        try {
            // Parse the file
            $this->parseFile($import, $file);

            // Update status
            $import->update(['status' => 'reviewing']);
            $import->updateRowCounts();

            return redirect()
                ->route('accounting.expense-imports.show', $import)
                ->with('success', "File parsed successfully. {$import->total_rows} rows imported for review.");

        } catch (\Exception $e) {
            Log::error('Expense import parsing failed: ' . $e->getMessage(), [
                'import_id' => $import->id,
                'file' => $fileName,
                'trace' => $e->getTraceAsString(),
            ]);

            $import->update(['status' => 'failed']);

            return redirect()
                ->route('accounting.expense-imports.index')
                ->with('error', 'Failed to parse file: ' . $e->getMessage());
        }
    }

    /**
     * Parse CSV/Excel file and create import rows.
     */
    private function parseFile(ExpenseImport $import, $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv' || $extension === 'txt') {
            $this->parseCsv($import, $file->getPathname());
        } else {
            // For xlsx/xls, we'd need a library like PhpSpreadsheet
            // For now, convert to CSV or use simple parsing
            $this->parseCsv($import, $file->getPathname());
        }
    }

    /**
     * Parse CSV file.
     */
    private function parseCsv(ExpenseImport $import, string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Could not open file');
        }

        // Load accounts for mapping
        $accounts = Account::pluck('id', 'name')->toArray();

        // Load expense types for auto-mapping
        $expenseTypes = ExpenseType::pluck('id', 'name')->toArray();
        $expenseTypesByCode = ExpenseType::pluck('id', 'code')->toArray();

        // Load categories for auto-mapping
        $categories = ExpenseCategory::whereNull('parent_id')->pluck('id', 'name')->toArray();

        // Load customers for auto-mapping
        $customers = Customer::pluck('id', 'company_name')->toArray();
        $customersByName = Customer::pluck('id', 'name')->toArray();

        $rowNumber = 0;
        $headerRow = null;
        $columnMapping = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip first row (title row)
            if ($rowNumber === 1) {
                continue;
            }

            // Second row is headers
            if ($rowNumber === 2) {
                $headerRow = $row;
                $columnMapping = $this->detectColumnMapping($row);
                $import->update(['column_mappings' => $columnMapping]);
                continue;
            }

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Parse the data row
            $rowData = $this->parseDataRow($row, $columnMapping, $accounts, $expenseTypes, $expenseTypesByCode, $categories, $customers, $customersByName);

            if ($rowData) {
                $rowData['expense_import_id'] = $import->id;
                $rowData['row_number'] = $rowNumber;

                // Safely combine header and row data (handle mismatched lengths)
                $headers = $headerRow ?? array_keys($row);
                $headerCount = count($headers);
                $rowCount = count($row);

                if ($rowCount < $headerCount) {
                    // Pad row with empty values
                    $row = array_pad($row, $headerCount, '');
                } elseif ($rowCount > $headerCount) {
                    // Trim excess columns
                    $row = array_slice($row, 0, $headerCount);
                }

                $rowData['raw_data'] = array_combine($headers, $row);

                ExpenseImportRow::create($rowData);
            }
        }

        fclose($handle);

        // Validate all rows
        foreach ($import->rows as $row) {
            $row->validate();
        }
    }

    /**
     * Detect column mapping from header row.
     */
    private function detectColumnMapping(array $headers): array
    {
        $mapping = [];

        foreach ($headers as $index => $header) {
            $header = trim($header);

            // Standard columns
            if (stripos($header, 'Date') !== false && !isset($mapping['date'])) {
                $mapping['date'] = $index;
            } elseif ($header === 'Year') {
                $mapping['year'] = $index;
            } elseif ($header === 'Month') {
                $mapping['month'] = $index;
            } elseif ($header === 'Item') {
                $mapping['item'] = $index;
            } elseif ($header === 'Type') {
                $mapping['type'] = $index;
            } elseif ($header === 'Category') {
                $mapping['category'] = $index;
            } elseif ($header === 'Sub Category') {
                $mapping['subcategory'] = $index;
            } elseif ($header === 'Customer') {
                $mapping['customer'] = $index;
            } elseif ($header === 'Department' || $header === 'Inv #') {
                $mapping['department'] = $index;
            } elseif ($header === 'Total (EGP)') {
                $mapping['total'] = $index;
            } elseif ($header === 'Absolute Total') {
                $mapping['absolute_total'] = $index;
            } elseif ($header === 'Comment') {
                $mapping['comment'] = $index;
            }

            // Account columns
            foreach ($this->accountColumns as $columnName => $accountName) {
                if ($header === $columnName) {
                    $mapping['accounts'][$accountName] = $index;
                }
            }
        }

        return $mapping;
    }

    /**
     * Sanitize string by removing null bytes and control characters.
     */
    private function sanitizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        // Remove null bytes and control characters except newline/tab
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value));
    }

    /**
     * Parse a data row using column mapping.
     */
    private function parseDataRow(array $row, array $mapping, array $accounts, array $expenseTypes, array $expenseTypesByCode, array $categories, array $customers, array $customersByName): ?array
    {
        // Get basic fields (sanitize all strings to remove null bytes)
        $dateStr = $this->sanitizeString($row[$mapping['date'] ?? 1] ?? null);
        $item = $this->sanitizeString($row[$mapping['item'] ?? 4] ?? '');
        $typeRaw = $this->sanitizeString($row[$mapping['type'] ?? 5] ?? '');
        $categoryRaw = $this->sanitizeString($row[$mapping['category'] ?? 6] ?? '');
        $subcategoryRaw = $this->sanitizeString($row[$mapping['subcategory'] ?? 7] ?? '');
        $customerRaw = $this->sanitizeString($row[$mapping['customer'] ?? 8] ?? '');
        $department = $this->sanitizeString($row[$mapping['department'] ?? 9] ?? '');
        $totalStr = $this->sanitizeString($row[$mapping['total'] ?? 18] ?? '0');
        $absoluteTotalStr = $this->sanitizeString($row[$mapping['absolute_total'] ?? 19] ?? '0');
        $comment = $this->sanitizeString($row[$mapping['comment'] ?? 20] ?? '');

        // Skip if no item description
        if (empty($item)) {
            return null;
        }

        // Parse date (already sanitized above)
        $expenseDate = null;
        if (!empty($dateStr)) {
            try {
                // Try DD/MM/YYYY format first
                $expenseDate = Carbon::createFromFormat('d/m/Y', $dateStr);
            } catch (\Exception $e) {
                try {
                    // Try other formats
                    $expenseDate = Carbon::parse($dateStr);
                } catch (\Exception $e) {
                    $expenseDate = null;
                }
            }
        }

        // Parse amounts
        $total = $this->parseAmount($totalStr);
        $absoluteTotal = $this->parseAmount($absoluteTotalStr);

        // Determine if this is income (negative total)
        $isIncome = $total < 0;

        // Parse account amounts
        $accountAmounts = [];
        if (isset($mapping['accounts'])) {
            foreach ($mapping['accounts'] as $accountName => $colIndex) {
                $amount = $this->parseAmount($row[$colIndex] ?? '');
                if ($amount != 0) {
                    $accountId = $accounts[$accountName] ?? null;
                    if ($accountId) {
                        $accountAmounts[$accountId] = $amount;
                    }
                }
            }
        }

        // Auto-map expense type
        $expenseTypeId = null;
        if ($typeRaw) {
            // Try direct name match
            $expenseTypeId = $expenseTypes[$typeRaw] ?? null;

            // Try mapping common names
            if (!$expenseTypeId) {
                $typeMapping = [
                    'Cost of Sales' => 'CoS',
                    'OpEx' => 'OpEx',
                    'Income' => null, // Income is handled separately
                    'Tax' => 'Tax',
                    'Payroll' => 'OpEx', // Map payroll to OpEx
                    'Investment' => null, // Balance swaps are skipped
                ];

                $mappedCode = $typeMapping[$typeRaw] ?? null;
                if ($mappedCode) {
                    $expenseTypeId = $expenseTypesByCode[$mappedCode] ?? null;
                }
            }
        }

        // Auto-map category
        $categoryId = $categories[$categoryRaw] ?? null;

        // Auto-map customer
        $customerId = $customers[$customerRaw] ?? $customersByName[$customerRaw] ?? null;

        return [
            'expense_date' => $expenseDate,
            'year' => $expenseDate?->year ?? ($row[$mapping['year'] ?? 2] ?? null),
            'month' => $expenseDate?->month ?? ($row[$mapping['month'] ?? 3] ?? null),
            'item_description' => $item,
            'expense_type_raw' => $typeRaw,
            'expense_type_id' => $expenseTypeId,
            'category_raw' => $categoryRaw,
            'category_id' => $categoryId,
            'subcategory_raw' => $subcategoryRaw,
            'customer_raw' => $customerRaw,
            'customer_id' => $customerId,
            'department_number' => $department,
            'account_amounts' => $accountAmounts,
            'total_amount' => $total,
            'absolute_total' => $absoluteTotal,
            'comment' => $comment,
            'is_income' => $isIncome,
            'status' => 'pending',
        ];
    }

    /**
     * Parse amount string to float.
     */
    private function parseAmount(string $amountStr): float
    {
        $amountStr = trim($amountStr);

        if (empty($amountStr) || $amountStr === '-') {
            return 0;
        }

        // Check for negative (parentheses)
        $isNegative = false;
        if (preg_match('/^\s*\((.+)\)\s*$/', $amountStr, $matches)) {
            $isNegative = true;
            $amountStr = $matches[1];
        }

        // Remove currency symbols and spaces
        $amountStr = preg_replace('/[^\d.,\-]/', '', $amountStr);

        // Handle European format (1.234,56) vs US format (1,234.56)
        // If there's a comma after a dot, it's European format
        if (preg_match('/\.\d{3},/', $amountStr)) {
            // European: dots are thousands, comma is decimal
            $amountStr = str_replace('.', '', $amountStr);
            $amountStr = str_replace(',', '.', $amountStr);
        } else {
            // US format or simple format - remove commas
            $amountStr = str_replace(',', '', $amountStr);
        }

        $amount = floatval($amountStr);

        return $isNegative ? -$amount : $amount;
    }

    /**
     * Show the review/edit page for an import.
     */
    public function show(ExpenseImport $expenseImport): View
    {
        $expenseImport->load(['rows', 'createdBy']);

        // Get lookup data for dropdowns
        $expenseTypes = ExpenseType::active()->orderBy('sort_order')->get();
        $categories = ExpenseCategory::getFlatTree(activeOnly: true);
        $accounts = Account::active()->orderBy('name')->get();
        $customers = Customer::orderBy('company_name')->get();

        // Get unique unmapped values for mapping UI (only show items that need mapping)
        $unmappedTypes = $expenseImport->getUnmappedValues('expense_type_raw', 'expense_type_id');
        $unmappedCategories = $expenseImport->getUnmappedValues('category_raw', 'category_id');
        $unmappedCustomers = $expenseImport->getUnmappedValues('customer_raw', 'customer_id');

        // Get all unique values for "show all" toggle
        $allTypes = $expenseImport->getUniqueValues('expense_type_raw');
        $allCategories = $expenseImport->getUniqueValues('category_raw');
        $allCustomers = $expenseImport->getUniqueValues('customer_raw');

        // Count totals for display
        $mappingCounts = [
            'types' => ['unmapped' => count($unmappedTypes), 'total' => count($allTypes)],
            'categories' => ['unmapped' => count($unmappedCategories), 'total' => count($allCategories)],
            'customers' => ['unmapped' => count($unmappedCustomers), 'total' => count($allCustomers)],
        ];

        return view('accounting::expense-imports.show', compact(
            'expenseImport',
            'expenseTypes',
            'categories',
            'accounts',
            'customers',
            'unmappedTypes',
            'unmappedCategories',
            'unmappedCustomers',
            'allTypes',
            'allCategories',
            'allCustomers',
            'mappingCounts'
        ));
    }

    /**
     * Update a single row via AJAX.
     */
    public function updateRow(Request $request, ExpenseImportRow $expenseImportRow): JsonResponse
    {
        $data = $request->validate([
            'expense_type_id' => 'nullable|exists:expense_types,id',
            'category_id' => 'nullable|exists:expense_categories,id',
            'subcategory_id' => 'nullable|exists:expense_categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'income_without_invoice' => 'nullable|boolean',
            'action' => 'nullable|in:create_expense,create_income,link_invoice,skip,balance_swap',
            'item_description' => 'nullable|string',
            'expense_date' => 'nullable|date',
            'create_customer' => 'nullable|boolean',
        ]);

        $expenseImportRow->update($data);
        $expenseImportRow->validate();

        return response()->json([
            'success' => true,
            'row' => $expenseImportRow->fresh(),
        ]);
    }

    /**
     * Bulk update multiple rows via AJAX.
     */
    public function bulkUpdate(Request $request, ExpenseImport $expenseImport): JsonResponse
    {
        $data = $request->validate([
            'row_ids' => 'required|array',
            'row_ids.*' => 'exists:expense_import_rows,id',
            'field' => 'required|string',
            'value' => 'nullable',
        ]);

        $field = $data['field'];
        $value = $data['value'];
        $rowIds = $data['row_ids'];

        // Validate field is allowed
        $allowedFields = [
            'expense_type_id',
            'category_id',
            'subcategory_id',
            'customer_id',
            'invoice_id',
            'income_without_invoice',
            'action',
            'create_customer',
        ];

        if (!in_array($field, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Invalid field'], 400);
        }

        // Update all selected rows
        ExpenseImportRow::whereIn('id', $rowIds)
            ->where('expense_import_id', $expenseImport->id)
            ->update([$field => $value ?: null]);

        // Re-validate affected rows
        $rows = ExpenseImportRow::whereIn('id', $rowIds)->get();
        foreach ($rows as $row) {
            $row->validate();
        }

        // Update import counts
        $expenseImport->updateRowCounts();

        return response()->json([
            'success' => true,
            'message' => count($rowIds) . ' rows updated',
            'counts' => [
                'total' => $expenseImport->total_rows,
                'valid' => $expenseImport->valid_rows,
                'warning' => $expenseImport->warning_rows,
                'error' => $expenseImport->error_rows,
            ],
        ]);
    }

    /**
     * Map a raw value to an entity for all matching rows.
     */
    public function mapValue(Request $request, ExpenseImport $expenseImport): JsonResponse
    {
        $data = $request->validate([
            'field' => 'required|string|in:expense_type,category,subcategory,customer',
            'raw_value' => 'required|string',
            'mapped_id' => 'nullable|integer',
            'create_new' => 'nullable|boolean',
        ]);

        $rawField = $data['field'] . '_raw';
        $idField = $data['field'] . '_id';

        if ($data['field'] === 'customer' && $data['create_new']) {
            // Flag rows to create new customer
            ExpenseImportRow::where('expense_import_id', $expenseImport->id)
                ->where($rawField, $data['raw_value'])
                ->update([
                    'create_customer' => true,
                    $idField => null,
                ]);
        } else {
            // Map to existing entity
            ExpenseImportRow::where('expense_import_id', $expenseImport->id)
                ->where($rawField, $data['raw_value'])
                ->update([$idField => $data['mapped_id']]);
        }

        // Re-validate affected rows
        $rows = ExpenseImportRow::where('expense_import_id', $expenseImport->id)
            ->where($rawField, $data['raw_value'])
            ->get();

        foreach ($rows as $row) {
            $row->validate();
        }

        $expenseImport->updateRowCounts();

        return response()->json([
            'success' => true,
            'message' => 'Mapping updated for ' . count($rows) . ' rows',
        ]);
    }

    /**
     * Show preview of what will be imported.
     */
    public function preview(ExpenseImport $expenseImport): View
    {
        $expenseImport->update(['status' => 'previewing']);

        // Group rows by action
        $rowsByAction = $expenseImport->rows()
            ->with(['expenseType', 'category', 'subcategory', 'customer', 'invoice'])
            ->get()
            ->groupBy('action');

        // Calculate summary
        $summary = [
            'expenses_to_create' => $rowsByAction->get('create_expense', collect())->count(),
            'expenses_total' => $rowsByAction->get('create_expense', collect())->sum('absolute_total'),
            'income_to_create' => $rowsByAction->get('create_income', collect())->count(),
            'income_total' => abs($rowsByAction->get('create_income', collect())->sum('total_amount')),
            'invoices_to_link' => $rowsByAction->get('link_invoice', collect())->count(),
            'invoices_total' => abs($rowsByAction->get('link_invoice', collect())->sum('total_amount')),
            'balance_swaps' => $rowsByAction->get('balance_swap', collect())->count(),
            'skipped' => $rowsByAction->get('skip', collect())->count(),
            'customers_to_create' => $expenseImport->rows()->where('create_customer', true)->distinct('customer_raw')->count('customer_raw'),
        ];

        // Get rows with errors/warnings
        $errorRows = $expenseImport->rows()->where('status', 'error')->get();
        $warningRows = $expenseImport->rows()->where('status', 'warning')->get();

        return view('accounting::expense-imports.preview', compact(
            'expenseImport',
            'rowsByAction',
            'summary',
            'errorRows',
            'warningRows'
        ));
    }

    /**
     * Execute the import (dry run or commit).
     */
    public function execute(Request $request, ExpenseImport $expenseImport): RedirectResponse
    {
        $isDryRun = $request->input('dry_run', false);

        $expenseImport->update(['status' => 'executing']);

        $results = [
            'expenses_created' => 0,
            'income_created' => 0,
            'invoices_linked' => 0,
            'customers_created' => 0,
            'errors' => [],
        ];

        try {
            DB::beginTransaction();

            // First, create any new customers
            $customerMap = $this->createCustomers($expenseImport, $results);

            // Process each non-skipped row
            foreach ($expenseImport->rows()->whereNotIn('action', ['skip', 'balance_swap'])->get() as $row) {
                try {
                    $this->processRow($row, $customerMap, $results, $isDryRun);
                } catch (\Exception $e) {
                    $results['errors'][] = "Row {$row->row_number}: " . $e->getMessage();
                    $row->update(['status' => 'error']);
                    $row->addValidationMessage('error', $e->getMessage());
                    $row->save();
                }
            }

            if ($isDryRun) {
                DB::rollBack();
                $message = 'Dry run completed. No changes were saved.';
            } else {
                DB::commit();
                $expenseImport->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'summary' => $results,
                ]);
                $expenseImport->updateRowCounts();
                $message = 'Import completed successfully!';
            }

            return redirect()
                ->route('accounting.expense-imports.show', $expenseImport)
                ->with('success', $message)
                ->with('import_results', $results);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Expense import execution failed: ' . $e->getMessage(), [
                'import_id' => $expenseImport->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $expenseImport->update(['status' => 'failed']);

            return redirect()
                ->route('accounting.expense-imports.show', $expenseImport)
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Create customers for rows marked to create new customer.
     */
    private function createCustomers(ExpenseImport $expenseImport, array &$results): array
    {
        $customerMap = [];

        $newCustomerNames = $expenseImport->rows()
            ->where('create_customer', true)
            ->whereNull('customer_id')
            ->distinct()
            ->pluck('customer_raw')
            ->filter();

        foreach ($newCustomerNames as $customerName) {
            $customer = Customer::create([
                'company_name' => $customerName,
                'name' => $customerName,
                'type' => 'company',
                'is_active' => true,
            ]);

            $customerMap[$customerName] = $customer->id;
            $results['customers_created']++;

            // Update all rows with this customer name
            $expenseImport->rows()
                ->where('customer_raw', $customerName)
                ->update(['customer_id' => $customer->id]);
        }

        return $customerMap;
    }

    /**
     * Process a single import row.
     */
    private function processRow(ExpenseImportRow $row, array $customerMap, array &$results, bool $isDryRun): void
    {
        // Update customer_id if we just created the customer
        if ($row->create_customer && !$row->customer_id && isset($customerMap[$row->customer_raw])) {
            $row->customer_id = $customerMap[$row->customer_raw];
        }

        switch ($row->action) {
            case 'create_expense':
                $this->createExpense($row, $results, $isDryRun);
                break;

            case 'create_income':
                $this->createIncome($row, $results, $isDryRun);
                break;

            case 'link_invoice':
                $this->linkInvoice($row, $results, $isDryRun);
                break;
        }
    }

    /**
     * Create an expense from import row.
     */
    private function createExpense(ExpenseImportRow $row, array &$results, bool $isDryRun): void
    {
        $primaryAccount = $row->primary_account;

        $expense = ExpenseSchedule::create([
            'name' => $row->item_description,
            'description' => $row->comment,
            'amount' => abs($row->absolute_total),
            'category_id' => $row->category_id,
            'subcategory_id' => $row->subcategory_id,
            'expense_type' => 'one_time',
            'expense_date' => $row->expense_date,
            'start_date' => $row->expense_date,
            'frequency_type' => 'monthly',
            'frequency_value' => 1,
            'is_active' => false, // One-time expenses are not active for projections
            'payment_status' => 'paid',
            'paid_from_account_id' => $primaryAccount['id'] ?? null,
            'paid_date' => $row->expense_date,
            'paid_amount' => abs($row->absolute_total),
            'payment_notes' => "Imported from expense import #{$row->expense_import_id}",
        ]);

        if (!$isDryRun) {
            $row->update([
                'status' => 'imported',
                'created_expense_id' => $expense->id,
            ]);
        }

        $results['expenses_created']++;
    }

    /**
     * Create income record from import row.
     */
    private function createIncome(ExpenseImportRow $row, array &$results, bool $isDryRun): void
    {
        // For now, just mark as imported
        // Income without invoice could be tracked separately
        if (!$isDryRun) {
            $row->update(['status' => 'imported']);
        }

        $results['income_created']++;
    }

    /**
     * Link income row to an invoice.
     */
    private function linkInvoice(ExpenseImportRow $row, array &$results, bool $isDryRun): void
    {
        if (!$row->invoice_id) {
            throw new \Exception('No invoice linked');
        }

        $invoice = Invoice::find($row->invoice_id);
        if (!$invoice) {
            throw new \Exception('Invoice not found');
        }

        // Create payment on the invoice
        $primaryAccount = $row->primary_account;

        $payment = $invoice->payments()->create([
            'amount' => abs($row->total_amount),
            'payment_date' => $row->expense_date,
            'payment_method' => 'imported',
            'reference_number' => "IMPORT-{$row->expense_import_id}-{$row->row_number}",
            'notes' => "Imported payment: {$row->item_description}",
            'account_id' => $primaryAccount['id'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Update invoice payment status
        $invoice->updatePaymentStatus();

        if (!$isDryRun) {
            $row->update([
                'status' => 'imported',
                'created_payment_id' => $payment->id,
            ]);
        }

        $results['invoices_linked']++;
    }

    /**
     * Search invoices for linking (AJAX).
     */
    public function searchInvoices(Request $request): JsonResponse
    {
        $search = $request->input('q', '');
        $customerId = $request->input('customer_id');

        $query = Invoice::with('customer')
            ->whereIn('status', ['sent', 'overdue', 'paid']);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('company_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->orderByDesc('invoice_date')
            ->limit(20)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'text' => "{$invoice->invoice_number} - {$invoice->customer->display_name} ({$invoice->formatted_total})",
                    'invoice_number' => $invoice->invoice_number,
                    'customer' => $invoice->customer->display_name,
                    'total' => $invoice->total_amount,
                    'status' => $invoice->status,
                ];
            });

        return response()->json(['results' => $invoices]);
    }

    /**
     * Delete an import session.
     */
    public function destroy(ExpenseImport $expenseImport): RedirectResponse
    {
        if ($expenseImport->status === 'completed') {
            return redirect()
                ->route('accounting.expense-imports.index')
                ->with('error', 'Cannot delete a completed import.');
        }

        $expenseImport->delete();

        return redirect()
            ->route('accounting.expense-imports.index')
            ->with('success', 'Import deleted successfully.');
    }
}
