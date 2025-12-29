<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use Modules\Invoicing\Models\Invoice;
use Modules\Invoicing\Models\InvoiceItem;
use Modules\Invoicing\Models\InvoicePayment;
use Modules\Accounting\Models\Estimate;
use Modules\Accounting\Models\EstimateItem;
use Modules\Accounting\Models\ExpenseSchedule;
use Modules\Accounting\Models\ExpenseCategory;
use Modules\Accounting\Models\CreditNote;
use Modules\Accounting\Models\CreditNoteItem;
use Carbon\Carbon;

class MigratePerfexData extends Command
{
    protected $signature = 'perfex:migrate
                            {--type= : Type of data to migrate (customers, invoices, proposals, expenses, credit-notes, all)}
                            {--dry-run : Preview changes without actually migrating}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Migrate data from Perfex CRM SQL backup to Aura';

    protected $sqlFile;
    protected $dryRun = false;
    protected $stats = [];

    // Customer ID mapping (Perfex ID => Aura ID)
    protected $customerMap = [];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $type = $this->option('type') ?? 'all';

        $this->sqlFile = base_path('docs/2025-12-29-14-27-16_backup.sql');

        if (!file_exists($this->sqlFile)) {
            $this->error('SQL backup file not found at: ' . $this->sqlFile);
            return 1;
        }

        $this->info('Perfex CRM Data Migration');
        $this->info('========================');
        $this->info('SQL File: ' . $this->sqlFile);
        $this->info('Mode: ' . ($this->dryRun ? 'DRY RUN (no changes)' : 'LIVE'));
        $this->newLine();

        // Pre-load customer mapping
        $this->loadCustomerMapping();

        $types = $type === 'all'
            ? ['customers', 'invoices', 'proposals', 'expenses', 'credit-notes']
            : [$type];

        foreach ($types as $migrationType) {
            $this->migrateType($migrationType);
        }

        // Print summary
        $this->printSummary();

        return 0;
    }

    protected function loadCustomerMapping(): void
    {
        // Load existing customers by email or name for matching
        $this->customerMap = [];
    }

    protected function migrateType(string $type): void
    {
        switch ($type) {
            case 'customers':
                $this->migrateCustomers();
                break;
            case 'invoices':
                $this->migrateInvoices();
                break;
            case 'proposals':
                $this->migrateProposals();
                break;
            case 'expenses':
                $this->migrateExpenses();
                break;
            case 'credit-notes':
                $this->migrateCreditNotes();
                break;
            default:
                $this->warn("Unknown migration type: {$type}");
        }
    }

    protected function migrateCustomers(): void
    {
        $this->info('Migrating Customers...');

        // Read customer data from SQL
        $clients = $this->extractTableData('tblclients');
        $contacts = $this->extractTableData('tblcontacts');

        $this->stats['customers'] = ['total' => count($clients), 'updated' => 0, 'created' => 0, 'skipped' => 0];

        $bar = $this->output->createProgressBar(count($clients));
        $bar->start();

        foreach ($clients as $client) {
            $this->processClient($client, $contacts);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function processClient(array $client, array $allContacts): void
    {
        $perfexId = $client['userid'] ?? null;
        $email = trim($client['email'] ?? '');
        $company = trim($client['company'] ?? '');
        $name = $company ?: 'Unknown';

        // Find matching customer in Aura (by email or company name)
        $existingCustomer = null;
        if (!empty($email)) {
            $existingCustomer = Customer::where('email', $email)->first();
        }
        if (!$existingCustomer && !empty($company)) {
            $existingCustomer = Customer::where('company_name', $company)
                                       ->orWhere('name', $company)
                                       ->first();
        }

        // Get contacts for this client
        $clientContacts = array_filter($allContacts, fn($c) => ($c['userid'] ?? null) == $perfexId);
        $contactPersons = [];
        foreach ($clientContacts as $contact) {
            $contactPersons[] = [
                'name' => trim(($contact['firstname'] ?? '') . ' ' . ($contact['lastname'] ?? '')),
                'email' => $contact['email'] ?? '',
                'phone' => $contact['phonenumber'] ?? '',
                'title' => $contact['title'] ?? '',
                'is_primary' => ($contact['is_primary'] ?? 0) == 1,
            ];
        }

        $customerData = [
            'perfex_id' => $perfexId,
            'name' => $company ?: 'Unknown',
            'company_name' => $company,
            'email' => $email,
            'phone' => $client['phonenumber'] ?? '',
            'address' => $this->buildAddress($client),
            'tax_id' => $client['vat'] ?? null,
            'website' => $client['website'] ?? null,
            'notes' => $client['billing_street'] ?? null,
            'type' => 'company',
            'status' => ($client['active'] ?? 1) == 1 ? 'active' : 'inactive',
            'contact_persons' => $contactPersons,
        ];

        if ($this->dryRun) {
            if ($existingCustomer) {
                $this->stats['customers']['updated']++;
            } else {
                $this->stats['customers']['created']++;
            }
            $this->customerMap[$perfexId] = $existingCustomer->id ?? 'NEW';
            return;
        }

        if ($existingCustomer) {
            // Update existing customer
            $existingCustomer->update([
                'perfex_id' => $perfexId,
                'phone' => $existingCustomer->phone ?: $customerData['phone'],
                'address' => $existingCustomer->address ?: $customerData['address'],
                'tax_id' => $existingCustomer->tax_id ?: $customerData['tax_id'],
                'website' => $existingCustomer->website ?: $customerData['website'],
                'contact_persons' => array_merge($existingCustomer->contact_persons ?? [], $contactPersons),
            ]);
            $this->customerMap[$perfexId] = $existingCustomer->id;
            $this->stats['customers']['updated']++;
        } else {
            // Create new customer
            $newCustomer = Customer::create($customerData);
            $this->customerMap[$perfexId] = $newCustomer->id;
            $this->stats['customers']['created']++;
        }
    }

    protected function buildAddress(array $client): string
    {
        $parts = array_filter([
            $client['address'] ?? '',
            $client['city'] ?? '',
            $client['state'] ?? '',
            $client['zip'] ?? '',
            $client['country'] ?? '',
        ]);
        return implode(', ', $parts);
    }

    protected function buildCustomerMapFromDatabase(): void
    {
        // Build customer map from existing customers with perfex_id
        $customers = Customer::whereNotNull('perfex_id')->get(['id', 'perfex_id']);
        foreach ($customers as $customer) {
            $this->customerMap[$customer->perfex_id] = $customer->id;
        }
    }

    protected function migrateInvoices(): void
    {
        $this->info('Migrating Invoices...');

        // Ensure customer map is populated
        if (empty($this->customerMap)) {
            $this->buildCustomerMapFromDatabase();
        }

        $invoices = $this->extractTableData('tblinvoices');
        $items = $this->extractTableData('tblitemable');
        $itemTaxes = $this->extractTableData('tblitem_tax');
        $payments = $this->extractTableData('tblinvoicepaymentrecords');

        $this->stats['invoices'] = ['total' => count($invoices), 'created' => 0, 'skipped' => 0];
        $this->stats['invoice_items'] = ['total' => 0, 'created' => 0];
        $this->stats['invoice_payments'] = ['total' => count($payments), 'created' => 0];

        $bar = $this->output->createProgressBar(count($invoices));
        $bar->start();

        foreach ($invoices as $invoice) {
            $this->processInvoice($invoice, $items, $itemTaxes, $payments);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function processInvoice(array $invoice, array $allItems, array $allItemTaxes, array $allPayments): void
    {
        $perfexId = $invoice['id'] ?? null;
        $clientId = $invoice['clientid'] ?? null;

        // Skip if already migrated
        $existing = Invoice::where('perfex_id', $perfexId)->first();
        if ($existing) {
            $this->stats['invoices']['skipped']++;
            return;
        }

        // Get customer
        $customerId = $this->customerMap[$clientId] ?? null;
        if (!$customerId || $customerId === 'NEW') {
            // Try to find customer by matching
            $customerId = Customer::first()?->id;
        }

        if (!$customerId) {
            $this->stats['invoices']['skipped']++;
            return;
        }

        // Map status
        $statusMap = [
            1 => 'sent',      // Unpaid
            2 => 'paid',      // Paid
            3 => 'sent',      // Partially Paid
            4 => 'overdue',   // Overdue
            5 => 'cancelled', // Cancelled
            6 => 'draft',     // Draft
        ];
        $status = $statusMap[$invoice['status'] ?? 1] ?? 'draft';

        // Get items for this invoice
        $invoiceItems = array_filter($allItems, function($item) use ($perfexId) {
            return ($item['rel_id'] ?? null) == $perfexId && ($item['rel_type'] ?? '') === 'invoice';
        });
        $this->stats['invoice_items']['total'] += count($invoiceItems);

        // Get payments
        $invoicePayments = array_filter($allPayments, fn($p) => ($p['invoiceid'] ?? null) == $perfexId);

        $subtotal = (float)($invoice['subtotal'] ?? 0);
        $taxAmount = (float)($invoice['total_tax'] ?? 0);
        $total = (float)($invoice['total'] ?? $subtotal + $taxAmount);
        $paidAmount = array_sum(array_column($invoicePayments, 'amount'));

        $invoiceData = [
            'perfex_id' => $perfexId,
            'invoice_number' => $invoice['number'] ?? ('INV-' . $perfexId),
            'invoice_date' => $this->parseDate($invoice['date'] ?? null) ?? now(),
            'due_date' => $this->parseDate($invoice['duedate'] ?? null) ?? now()->addDays(30),
            'customer_id' => $customerId,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
            'paid_amount' => $paidAmount,
            'status' => $status,
            'notes' => $invoice['clientnote'] ?? null,
            'terms_conditions' => $invoice['terms'] ?? null,
            'reference' => 'PERFEX-' . $perfexId,
            'business_unit_id' => 1,
            'created_by' => 1,
        ];

        if ($this->dryRun) {
            $this->stats['invoices']['created']++;
            $this->stats['invoice_items']['created'] += count($invoiceItems);
            $this->stats['invoice_payments']['created'] += count($invoicePayments);
            return;
        }

        DB::beginTransaction();
        try {
            $newInvoice = Invoice::create($invoiceData);

            // Create items
            foreach ($invoiceItems as $index => $item) {
                $itemId = $item['id'] ?? null;
                $qty = (float)($item['qty'] ?? 1);
                $rate = (float)($item['rate'] ?? 0);
                $itemSubtotal = $qty * $rate;

                // Find tax rate for this item
                $itemTax = array_filter($allItemTaxes, function($tax) use ($itemId, $perfexId) {
                    return ($tax['itemid'] ?? null) == $itemId
                        && ($tax['rel_id'] ?? null) == $perfexId
                        && ($tax['rel_type'] ?? '') === 'invoice';
                });
                $taxRate = !empty($itemTax) ? (float)(array_values($itemTax)[0]['taxrate'] ?? 0) : 0;
                $taxAmount = $itemSubtotal * ($taxRate / 100);

                InvoiceItem::create([
                    'invoice_id' => $newInvoice->id,
                    'description' => $item['description'] ?? 'Item',
                    'long_description' => $item['long_description'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $rate,
                    'unit' => $item['unit'] ?? null,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => $itemSubtotal + $taxAmount,
                    'sort_order' => (int)($item['item_order'] ?? $index),
                ]);
                $this->stats['invoice_items']['created']++;
            }

            // Create payments
            foreach ($invoicePayments as $payment) {
                InvoicePayment::create([
                    'invoice_id' => $newInvoice->id,
                    'amount' => (float)($payment['amount'] ?? 0),
                    'payment_date' => $this->parseDate($payment['date'] ?? null) ?? now(),
                    'payment_method' => $this->mapPaymentMethod($payment['paymentmode'] ?? ''),
                    'reference_number' => $payment['transactionid'] ?? null,
                    'notes' => $payment['note'] ?? 'Migrated from Perfex',
                    'created_by' => 1,
                ]);
                $this->stats['invoice_payments']['created']++;
            }

            DB::commit();
            $this->stats['invoices']['created']++;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice migration failed', ['perfex_id' => $perfexId, 'error' => $e->getMessage()]);
            $this->stats['invoices']['skipped']++;
        }
    }

    protected function migrateProposals(): void
    {
        $this->info('Migrating Proposals to Estimates...');

        // Ensure customer map is populated
        if (empty($this->customerMap)) {
            $this->buildCustomerMapFromDatabase();
        }

        $proposals = $this->extractTableData('tblproposals');
        $items = $this->extractTableData('tblitemable');

        $this->stats['proposals'] = ['total' => count($proposals), 'created' => 0, 'skipped' => 0];
        $this->stats['proposal_items'] = ['total' => 0, 'created' => 0];

        $bar = $this->output->createProgressBar(count($proposals));
        $bar->start();

        foreach ($proposals as $proposal) {
            $this->processProposal($proposal, $items);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function processProposal(array $proposal, array $allItems): void
    {
        $perfexId = $proposal['id'] ?? null;
        $relId = $proposal['rel_id'] ?? null;
        $relType = $proposal['rel_type'] ?? '';

        // Skip if already migrated
        $existing = Estimate::where('perfex_id', $perfexId)->first();
        if ($existing) {
            $this->stats['proposals']['skipped']++;
            return;
        }

        // Get customer
        $customerId = null;
        if ($relType === 'customer' && $relId) {
            $customerId = $this->customerMap[$relId] ?? null;
        }

        // Map status
        $statusMap = [
            1 => 'draft',     // Draft
            2 => 'sent',      // Sent
            3 => 'sent',      // Open
            4 => 'sent',      // Revised
            5 => 'rejected',  // Declined
            6 => 'approved',  // Accepted
        ];
        $status = $statusMap[$proposal['status'] ?? 1] ?? 'draft';

        // Get items for this proposal
        $proposalItems = array_filter($allItems, function($item) use ($perfexId) {
            return ($item['rel_id'] ?? null) == $perfexId && ($item['rel_type'] ?? '') === 'proposal';
        });
        $this->stats['proposal_items']['total'] += count($proposalItems);

        $subtotal = (float)($proposal['subtotal'] ?? 0);
        $taxAmount = (float)($proposal['total_tax'] ?? 0);
        $total = (float)($proposal['total'] ?? $subtotal + $taxAmount);

        $estimateData = [
            'perfex_id' => $perfexId,
            'estimate_number' => 'PRP-' . $perfexId,
            'customer_id' => $customerId,
            'client_name' => $proposal['proposal_to'] ?? 'Unknown',
            'client_email' => $proposal['email'] ?? null,
            'client_address' => $proposal['address'] ?? null,
            'title' => $proposal['subject'] ?? 'Proposal',
            'description' => strip_tags($proposal['content'] ?? ''),
            'issue_date' => $this->parseDate($proposal['date'] ?? null) ?? now(),
            'valid_until' => $this->parseDate($proposal['open_till'] ?? null),
            'status' => $status,
            'subtotal' => $subtotal,
            'vat_rate' => 14,
            'vat_amount' => $taxAmount,
            'total' => $total,
            'notes' => null,
            'created_by' => 1,
        ];

        if ($status === 'sent' && isset($proposal['date_send'])) {
            $estimateData['sent_at'] = $this->parseDate($proposal['date_send']);
        }

        if ($this->dryRun) {
            $this->stats['proposals']['created']++;
            $this->stats['proposal_items']['created'] += count($proposalItems);
            return;
        }

        DB::beginTransaction();
        try {
            $newEstimate = Estimate::create($estimateData);

            // Create items
            foreach ($proposalItems as $index => $item) {
                EstimateItem::create([
                    'estimate_id' => $newEstimate->id,
                    'description' => $item['description'] ?? 'Item',
                    'details' => $item['long_description'] ?? null,
                    'quantity' => (float)($item['qty'] ?? 1),
                    'unit' => 'unit',
                    'unit_price' => (float)($item['rate'] ?? 0),
                    'amount' => (float)($item['qty'] ?? 1) * (float)($item['rate'] ?? 0),
                    'sort_order' => $index,
                ]);
                $this->stats['proposal_items']['created']++;
            }

            DB::commit();
            $this->stats['proposals']['created']++;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Proposal migration failed', ['perfex_id' => $perfexId, 'error' => $e->getMessage()]);
            $this->stats['proposals']['skipped']++;
        }
    }

    protected function migrateExpenses(): void
    {
        $this->info('Migrating Expenses...');

        $expenses = $this->extractTableData('tblexpenses');
        $categories = $this->extractTableData('tblexpenses_categories');

        $this->stats['expenses'] = ['total' => count($expenses), 'created' => 0, 'skipped' => 0];

        // First, ensure expense categories exist
        $categoryMap = [];
        foreach ($categories as $cat) {
            $catName = $cat['name'] ?? 'General';
            $existing = ExpenseCategory::where('name', $catName)->first();
            if (!$existing && !$this->dryRun) {
                $existing = ExpenseCategory::create([
                    'name' => $catName,
                    'description' => $cat['description'] ?? null,
                    'is_active' => true,
                ]);
            }
            $categoryMap[$cat['id'] ?? 0] = $existing->id ?? 1;
        }

        $bar = $this->output->createProgressBar(count($expenses));
        $bar->start();

        foreach ($expenses as $expense) {
            $this->processExpense($expense, $categoryMap);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function processExpense(array $expense, array $categoryMap): void
    {
        $perfexId = $expense['id'] ?? null;

        // Skip if already migrated
        $existing = ExpenseSchedule::where('perfex_id', $perfexId)->first();
        if ($existing) {
            $this->stats['expenses']['skipped']++;
            return;
        }

        $categoryId = $categoryMap[$expense['category'] ?? 0] ?? 1;
        $amount = (float)($expense['amount'] ?? 0);
        $taxAmount = (float)($expense['tax'] ?? 0) + (float)($expense['tax2'] ?? 0);
        $total = $amount + $taxAmount;
        $expenseDate = $this->parseDate($expense['date'] ?? null) ?? now();

        $expenseData = [
            'perfex_id' => $perfexId,
            'category_id' => $categoryId,
            'name' => $expense['expense_name'] ?? 'Expense',
            'description' => $expense['note'] ?? null,
            'amount' => $total,
            'expense_type' => 'one_time',
            'frequency_type' => 'monthly',
            'frequency_value' => 1,
            'start_date' => $expenseDate,
            'expense_date' => $expenseDate,
            'payment_status' => 'paid',
            'paid_amount' => $total,
            'paid_date' => $expenseDate,
            'payment_notes' => $expense['note'] ?? null,
            'is_active' => true,
        ];

        if ($this->dryRun) {
            $this->stats['expenses']['created']++;
            return;
        }

        try {
            ExpenseSchedule::create($expenseData);
            $this->stats['expenses']['created']++;
        } catch (\Exception $e) {
            Log::error('Expense migration failed', ['perfex_id' => $perfexId, 'error' => $e->getMessage()]);
            $this->stats['expenses']['skipped']++;
        }
    }

    protected function migrateCreditNotes(): void
    {
        $this->info('Migrating Credit Notes...');

        // Ensure customer map is populated
        if (empty($this->customerMap)) {
            $this->buildCustomerMapFromDatabase();
        }

        $creditNotes = $this->extractTableData('tblcreditnotes');
        $items = $this->extractTableData('tblitemable');

        $this->stats['credit_notes'] = ['total' => count($creditNotes), 'created' => 0, 'skipped' => 0];
        $this->stats['credit_note_items'] = ['total' => 0, 'created' => 0];

        $bar = $this->output->createProgressBar(count($creditNotes));
        $bar->start();

        foreach ($creditNotes as $creditNote) {
            $this->processCreditNote($creditNote, $items);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function processCreditNote(array $creditNote, array $allItems): void
    {
        $perfexId = $creditNote['id'] ?? null;
        $clientId = $creditNote['clientid'] ?? null;

        // Skip if already migrated
        $existing = CreditNote::where('perfex_id', $perfexId)->first();
        if ($existing) {
            $this->stats['credit_notes']['skipped']++;
            return;
        }

        // Get customer
        $customerId = $this->customerMap[$clientId] ?? null;
        if (!$customerId || $customerId === 'NEW') {
            $customerId = Customer::first()?->id;
        }

        if (!$customerId) {
            $this->stats['credit_notes']['skipped']++;
            return;
        }

        // Map status
        $statusMap = [
            1 => 'open',   // Open
            2 => 'closed', // Closed
            3 => 'void',   // Void
        ];
        $status = $statusMap[$creditNote['status'] ?? 1] ?? 'open';

        // Get items for this credit note
        $cnItems = array_filter($allItems, function($item) use ($perfexId) {
            return ($item['rel_id'] ?? null) == $perfexId && ($item['rel_type'] ?? '') === 'credit_note';
        });
        $this->stats['credit_note_items']['total'] += count($cnItems);

        $subtotal = (float)($creditNote['subtotal'] ?? 0);
        $taxAmount = (float)($creditNote['total_tax'] ?? 0);
        $total = (float)($creditNote['total'] ?? $subtotal + $taxAmount);
        $creditsUsed = (float)($creditNote['credits_used'] ?? 0);
        $remainingCredits = $total - $creditsUsed;

        // Get customer info
        $customer = Customer::find($customerId);

        $creditNoteData = [
            'credit_note_number' => $creditNote['number'] ?? ('CN-' . $perfexId),
            'customer_id' => $customerId,
            'client_name' => $customer->display_name ?? 'Unknown',
            'client_email' => $customer->email ?? null,
            'client_address' => $customer->address ?? null,
            'credit_note_date' => $this->parseDate($creditNote['date'] ?? null) ?? now(),
            'reference' => $creditNote['reference_no'] ?? null,
            'status' => $status,
            'subtotal' => $subtotal,
            'tax_rate' => 14,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'applied_amount' => $creditsUsed,
            'remaining_credits' => $remainingCredits,
            'notes' => $creditNote['clientnote'] ?? null,
            'created_by' => 1,
            'perfex_id' => $perfexId,
        ];

        if ($this->dryRun) {
            $this->stats['credit_notes']['created']++;
            $this->stats['credit_note_items']['created'] += count($cnItems);
            return;
        }

        DB::beginTransaction();
        try {
            $newCreditNote = CreditNote::create($creditNoteData);

            // Create items
            foreach ($cnItems as $index => $item) {
                CreditNoteItem::create([
                    'credit_note_id' => $newCreditNote->id,
                    'description' => $item['description'] ?? 'Item',
                    'details' => $item['long_description'] ?? null,
                    'quantity' => (float)($item['qty'] ?? 1),
                    'unit' => 'unit',
                    'unit_price' => (float)($item['rate'] ?? 0),
                    'amount' => (float)($item['qty'] ?? 1) * (float)($item['rate'] ?? 0),
                    'sort_order' => $index,
                ]);
                $this->stats['credit_note_items']['created']++;
            }

            DB::commit();
            $this->stats['credit_notes']['created']++;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Credit note migration failed', ['perfex_id' => $perfexId, 'error' => $e->getMessage()]);
            $this->stats['credit_notes']['skipped']++;
        }
    }

    protected function extractTableData(string $tableName): array
    {
        $content = file_get_contents($this->sqlFile);

        // Get column names from INSERT statement header - this backup format includes columns
        $columnsPattern = '/INSERT INTO `?' . preg_quote($tableName, '/') . '`?\s+\(([^)]+)\)\s+VALUES/i';
        $columns = [];
        if (preg_match($columnsPattern, $content, $colMatch)) {
            preg_match_all('/`(\w+)`/', $colMatch[1], $colNames);
            $columns = $colNames[1] ?? [];
        }

        // Find INSERT statements for this table - match until end of line since each INSERT is one row
        $pattern = '/INSERT INTO `?' . preg_quote($tableName, '/') . '`?\s+\([^)]+\)\s+VALUES\s*(\([^\n]+\));/im';

        if (!preg_match_all($pattern, $content, $matches)) {
            $this->warn("No data found for table: {$tableName}");
            return [];
        }

        $rows = [];

        // Parse INSERT values
        foreach ($matches[1] as $valuesBlock) {
            // Remove leading ( and trailing )
            $rowContent = trim($valuesBlock);
            if (str_starts_with($rowContent, '(')) {
                $rowContent = substr($rowContent, 1);
            }
            if (str_ends_with($rowContent, ')')) {
                $rowContent = substr($rowContent, 0, -1);
            }

            $values = $this->parseRowValues($rowContent);

            if (count($columns) === count($values)) {
                $rows[] = array_combine($columns, $values);
            } elseif (count($values) > 0) {
                // Use numeric keys if column count doesn't match
                $rows[] = $values;
            }
        }

        return $rows;
    }

    protected function parseRowValues(string $row): array
    {
        $values = [];
        $current = '';
        $inString = false;
        $stringChar = null;
        $escaped = false;

        for ($i = 0; $i < strlen($row); $i++) {
            $char = $row[$i];

            if ($escaped) {
                $current .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if (!$inString && ($char === "'" || $char === '"')) {
                $inString = true;
                $stringChar = $char;
                continue;
            }

            if ($inString && $char === $stringChar) {
                $inString = false;
                $stringChar = null;
                continue;
            }

            if (!$inString && $char === ',') {
                $values[] = $this->cleanValue(trim($current));
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $values[] = $this->cleanValue(trim($current));
        }

        return $values;
    }

    protected function cleanValue(string $value): ?string
    {
        if ($value === 'NULL' || $value === 'null') {
            return null;
        }
        return trim($value, "'\"");
    }

    protected function parseDate(?string $date): ?Carbon
    {
        if (empty($date) || $date === 'NULL' || $date === '0000-00-00') {
            return null;
        }
        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function mapPaymentMethod(string $method): string
    {
        $map = [
            'bank_transfer' => 'bank_transfer',
            'cash' => 'cash',
            'check' => 'check',
            'paypal' => 'paypal',
            'stripe' => 'credit_card',
            'credit_card' => 'credit_card',
        ];
        return $map[strtolower($method)] ?? 'other';
    }

    protected function printSummary(): void
    {
        $this->newLine();
        $this->info('Migration Summary');
        $this->info('=================');

        foreach ($this->stats as $type => $stats) {
            $this->info(ucfirst(str_replace('_', ' ', $type)) . ':');
            foreach ($stats as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
        }

        if ($this->dryRun) {
            $this->newLine();
            $this->warn('This was a DRY RUN - no changes were made.');
            $this->info('Run without --dry-run to apply changes.');
        }
    }
}
