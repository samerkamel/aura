<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ExpenseImportRow Model
 *
 * Represents a single row in an expense import session.
 */
class ExpenseImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_import_id',
        'row_number',
        'raw_data',
        'expense_date',
        'year',
        'month',
        'item_description',
        'expense_type_raw',
        'expense_type_id',
        'category_raw',
        'category_id',
        'subcategory_raw',
        'subcategory_id',
        'customer_raw',
        'customer_id',
        'create_customer',
        'department_number',
        'account_amounts',
        'total_amount',
        'absolute_total',
        'comment',
        'is_income',
        'invoice_id',
        'income_without_invoice',
        'status',
        'validation_messages',
        'action',
        'created_expense_id',
        'created_payment_id',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'account_amounts' => 'array',
        'validation_messages' => 'array',
        'expense_date' => 'date',
        'total_amount' => 'decimal:2',
        'absolute_total' => 'decimal:2',
        'is_income' => 'boolean',
        'create_customer' => 'boolean',
        'income_without_invoice' => 'boolean',
    ];

    /**
     * Get the import this row belongs to.
     */
    public function expenseImport(): BelongsTo
    {
        return $this->belongsTo(ExpenseImport::class);
    }

    /**
     * Get the mapped expense type.
     */
    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }

    /**
     * Get the mapped category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Get the mapped subcategory.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'subcategory_id');
    }

    /**
     * Get the mapped customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    /**
     * Get the linked invoice for income rows.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\Modules\Invoicing\Models\Invoice::class);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-secondary',
            'valid' => 'bg-success',
            'warning' => 'bg-warning',
            'error' => 'bg-danger',
            'skipped' => 'bg-secondary',
            'imported' => 'bg-info',
            default => 'bg-secondary',
        };
    }

    /**
     * Get action display name.
     */
    public function getActionDisplayAttribute(): string
    {
        return match($this->action) {
            'create_expense' => 'Create Expense',
            'create_income' => 'Create Income',
            'link_invoice' => 'Link to Invoice',
            'skip' => 'Skip',
            'balance_swap' => 'Balance Swap (Skip)',
            default => 'Unknown',
        };
    }

    /**
     * Add a validation message.
     */
    public function addValidationMessage(string $type, string $message): void
    {
        $messages = $this->validation_messages ?? [];
        $messages[] = ['type' => $type, 'message' => $message];
        $this->validation_messages = $messages;
    }

    /**
     * Check if row has errors.
     */
    public function hasErrors(): bool
    {
        if (!$this->validation_messages) {
            return false;
        }

        return collect($this->validation_messages)
            ->contains('type', 'error');
    }

    /**
     * Check if row has warnings.
     */
    public function hasWarnings(): bool
    {
        if (!$this->validation_messages) {
            return false;
        }

        return collect($this->validation_messages)
            ->contains('type', 'warning');
    }

    /**
     * Get the primary account for this expense.
     */
    public function getPrimaryAccountAttribute(): ?array
    {
        if (!$this->account_amounts) {
            return null;
        }

        // Find account with largest absolute amount
        $maxAccount = null;
        $maxAmount = 0;

        foreach ($this->account_amounts as $accountId => $amount) {
            if (abs($amount) > $maxAmount) {
                $maxAmount = abs($amount);
                $maxAccount = ['id' => $accountId, 'amount' => $amount];
            }
        }

        return $maxAccount;
    }

    /**
     * Validate this row and update status.
     */
    public function validate(): void
    {
        $messages = [];
        $hasError = false;
        $hasWarning = false;

        // Check required fields
        if (!$this->expense_date) {
            $messages[] = ['type' => 'error', 'message' => 'Date is required'];
            $hasError = true;
        }

        if (!$this->item_description) {
            $messages[] = ['type' => 'error', 'message' => 'Item description is required'];
            $hasError = true;
        }

        // Check expense type mapping for non-income, non-investment rows
        if (!$this->is_income && $this->expense_type_raw !== 'Investment') {
            if (!$this->expense_type_id && $this->expense_type_raw) {
                $messages[] = ['type' => 'warning', 'message' => "Expense type '{$this->expense_type_raw}' not mapped"];
                $hasWarning = true;
            }
        }

        // Check category mapping
        if (!$this->category_id && $this->category_raw) {
            $messages[] = ['type' => 'warning', 'message' => "Category '{$this->category_raw}' not mapped"];
            $hasWarning = true;
        }

        // Check customer mapping
        if (!$this->customer_id && $this->customer_raw && !$this->create_customer) {
            $messages[] = ['type' => 'warning', 'message' => "Customer '{$this->customer_raw}' not mapped - will create new"];
            $hasWarning = true;
            $this->create_customer = true;
        }

        // Check income rows
        if ($this->is_income && !$this->invoice_id && !$this->income_without_invoice) {
            $messages[] = ['type' => 'warning', 'message' => 'Income row not linked to invoice'];
            $hasWarning = true;
        }

        // Determine action
        if ($this->expense_type_raw === 'Investment' && $this->category_raw === 'Balance Swap') {
            $this->action = 'balance_swap';
        } elseif ($this->is_income) {
            $this->action = $this->invoice_id ? 'link_invoice' : 'create_income';
        } else {
            $this->action = 'create_expense';
        }

        // Set status
        if ($hasError) {
            $this->status = 'error';
        } elseif ($hasWarning) {
            $this->status = 'warning';
        } else {
            $this->status = 'valid';
        }

        $this->validation_messages = $messages;
        $this->save();
    }
}
