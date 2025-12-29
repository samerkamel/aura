<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ExpenseImport Model
 *
 * Represents an expense import session for tracking bulk imports from CSV/Excel files.
 */
class ExpenseImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'valid_rows',
        'warning_rows',
        'error_rows',
        'imported_rows',
        'column_mappings',
        'summary',
        'notes',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'column_mappings' => 'array',
        'summary' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get all rows for this import.
     */
    public function rows(): HasMany
    {
        return $this->hasMany(ExpenseImportRow::class)->orderBy('row_number');
    }

    /**
     * Get the user who created this import.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'parsing' => 'bg-info',
            'reviewing' => 'bg-warning',
            'previewing' => 'bg-primary',
            'executing' => 'bg-info',
            'completed' => 'bg-success',
            'failed' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'parsing' => 'Parsing',
            'reviewing' => 'Reviewing',
            'previewing' => 'Previewing',
            'executing' => 'Executing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Update row counts from database.
     */
    public function updateRowCounts(): void
    {
        $this->update([
            'total_rows' => $this->rows()->count(),
            'valid_rows' => $this->rows()->where('status', 'valid')->count(),
            'warning_rows' => $this->rows()->where('status', 'warning')->count(),
            'error_rows' => $this->rows()->where('status', 'error')->count(),
            'imported_rows' => $this->rows()->where('status', 'imported')->count(),
        ]);
    }

    /**
     * Get unique values for a column.
     */
    public function getUniqueValues(string $column): array
    {
        return $this->rows()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->pluck($column)
            ->toArray();
    }

    /**
     * Get unique unmapped values for a column (where corresponding _id is null).
     */
    public function getUnmappedValues(string $rawColumn, string $idColumn): array
    {
        return $this->rows()
            ->whereNotNull($rawColumn)
            ->where($rawColumn, '!=', '')
            ->whereNull($idColumn)
            ->distinct()
            ->pluck($rawColumn)
            ->toArray();
    }

    /**
     * Get unique category values for expense items only (is_income = false).
     */
    public function getExpenseCategoryValues(): array
    {
        return $this->rows()
            ->whereNotNull('category_raw')
            ->where('category_raw', '!=', '')
            ->where('is_income', false)
            ->distinct()
            ->pluck('category_raw')
            ->toArray();
    }

    /**
     * Get unmapped expense category values.
     */
    public function getUnmappedExpenseCategories(): array
    {
        return $this->rows()
            ->whereNotNull('category_raw')
            ->where('category_raw', '!=', '')
            ->where('is_income', false)
            ->whereNull('category_id')
            ->distinct()
            ->pluck('category_raw')
            ->toArray();
    }

    /**
     * Get unique category values for income items (for product mapping).
     */
    public function getIncomeProductValues(): array
    {
        return $this->rows()
            ->whereNotNull('category_raw')
            ->where('category_raw', '!=', '')
            ->where('is_income', true)
            ->distinct()
            ->pluck('category_raw')
            ->toArray();
    }

    /**
     * Get unmapped income product values (is_income = true and product_id is null).
     */
    public function getUnmappedIncomeProducts(): array
    {
        return $this->rows()
            ->whereNotNull('category_raw')
            ->where('category_raw', '!=', '')
            ->where('is_income', true)
            ->whereNull('product_id')
            ->distinct()
            ->pluck('category_raw')
            ->toArray();
    }

    /**
     * Get rows grouped by a raw value.
     */
    public function getRowsGroupedBy(string $column): array
    {
        return $this->rows()
            ->whereNotNull($column)
            ->get()
            ->groupBy($column)
            ->map(function ($rows) {
                return $rows->count();
            })
            ->toArray();
    }
}
