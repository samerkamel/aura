<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * ExpenseSchedule Model
 *
 * Represents recurring expense schedules in the cash flow management system.
 * Supports various frequencies and date calculations for projections.
 */
class ExpenseSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'category_id',
        'subcategory_id',
        'name',
        'description',
        'amount',
        'frequency_type',
        'frequency_value',
        'start_date',
        'end_date',
        'is_active',
        'skip_weekends',
        'excluded_dates',
        'expense_type',
        'expense_date',
        'payment_status',
        'paid_from_account_id',
        'paid_date',
        'paid_amount',
        'payment_notes',
        'payment_attachment_path',
        'payment_attachment_original_name',
        'payment_attachment_mime_type',
        'payment_attachment_size',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'expense_date' => 'date',
        'paid_date' => 'date',
        'paid_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'skip_weekends' => 'boolean',
        'excluded_dates' => 'array',
        'frequency_value' => 'integer',
    ];

    /**
     * Get the category this expense schedule belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * Get the subcategory this expense schedule belongs to.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'subcategory_id');
    }

    /**
     * Get the account this expense was paid from.
     */
    public function paidFromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'paid_from_account_id');
    }

    /**
     * Scope to get only active schedules.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get schedules active within a date range.
     */
    public function scopeActiveInPeriod(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->where('is_active', true)
                    ->where('start_date', '<=', $endDate)
                    ->where(function ($q) use ($startDate) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $startDate);
                    });
    }

    /**
     * Get the monthly equivalent amount for this schedule.
     */
    public function getMonthlyEquivalentAmountAttribute(): float
    {
        $multiplier = match($this->frequency_type) {
            'weekly' => 4.33 / $this->frequency_value, // Average weeks per month
            'bi-weekly' => 2.17 / $this->frequency_value, // Average bi-weeks per month
            'monthly' => 1 / $this->frequency_value,
            'quarterly' => 1 / ($this->frequency_value * 3),
            'yearly' => 1 / ($this->frequency_value * 12),
            default => 1,
        };

        return $this->amount * $multiplier;
    }

    /**
     * Get the yearly equivalent amount for this schedule.
     */
    public function getYearlyEquivalentAmountAttribute(): float
    {
        return $this->monthly_equivalent_amount * 12;
    }

    /**
     * Calculate next occurrence date from a given date.
     */
    public function getNextOccurrenceAfter(Carbon $date): ?Carbon
    {
        if (!$this->is_active) {
            return null;
        }

        if ($this->end_date && $date->gt($this->end_date)) {
            return null;
        }

        // If the date is before our start date, the next occurrence is the start date
        if ($date->lt($this->start_date)) {
            $next = $this->start_date->copy();
        } else {
            // Calculate how many periods have passed since start_date
            $next = $this->calculateNextOccurrenceFromStart($date);
        }

        if (!$next) {
            return null;
        }

        // Apply weekend skipping and excluded dates
        return $this->adjustForConstraints($next);
    }

    /**
     * Calculate the next occurrence based on start date and frequency.
     */
    private function calculateNextOccurrenceFromStart(Carbon $fromDate): ?Carbon
    {
        $startDate = $this->start_date->copy();

        // If we're already at or before the start date, return start date
        if ($fromDate->lte($startDate)) {
            return $startDate;
        }

        // Start from the schedule's start date and keep adding intervals until we find the next date after fromDate
        $current = $startDate->copy();
        $maxIterations = 1000; // Safety counter
        $iteration = 0;

        while ($current->lte($fromDate) && $iteration < $maxIterations) {
            $iteration++;

            $next = match($this->frequency_type) {
                'weekly' => $current->copy()->addWeeks($this->frequency_value),
                'bi-weekly' => $current->copy()->addWeeks($this->frequency_value * 2),
                'monthly' => $current->copy()->addMonths($this->frequency_value),
                'quarterly' => $current->copy()->addMonths($this->frequency_value * 3),
                'yearly' => $current->copy()->addYears($this->frequency_value),
                default => null,
            };

            if (!$next) {
                return null;
            }

            $current = $next;
        }

        return $current;
    }

    /**
     * Apply weekend skipping and excluded date constraints.
     */
    private function adjustForConstraints(Carbon $date): Carbon
    {
        $adjusted = $date->copy();

        // Apply weekend skipping
        if ($this->skip_weekends) {
            while ($adjusted->isWeekend()) {
                $adjusted->addDay();
            }
        }

        // Check excluded dates and find next valid date
        $maxAttempts = 10; // Prevent infinite loops
        $attempts = 0;

        while ($this->excluded_dates &&
               in_array($adjusted->format('Y-m-d'), $this->excluded_dates) &&
               $attempts < $maxAttempts) {

            $adjusted = $this->calculateNextDate($adjusted);
            if ($this->skip_weekends && $adjusted) {
                while ($adjusted->isWeekend()) {
                    $adjusted->addDay();
                }
            }
            $attempts++;
        }

        return $adjusted;
    }

    /**
     * Calculate all occurrence dates within a period.
     */
    public function getOccurrencesInPeriod(Carbon $startDate, Carbon $endDate): array
    {
        // Simple implementation to avoid infinite loops
        if (!$this->is_active) {
            return [];
        }

        $occurrences = [];
        $current = max($this->start_date, $startDate)->copy();

        // Safety counter to prevent infinite loops
        $safety = 0;
        $maxIterations = 365; // Maximum iterations per schedule

        while ($current->lte($endDate) && $safety < $maxIterations) {
            $safety++;

            if ($this->end_date && $current->gt($this->end_date)) {
                break;
            }

            // Apply weekend skipping
            $occurrence = $current->copy();
            if ($this->skip_weekends) {
                $weekendSafety = 0;
                while ($occurrence->isWeekend() && $weekendSafety < 7) {
                    $occurrence->addDay();
                    $weekendSafety++;
                }
            }

            // Check excluded dates and add to results
            if (is_null($this->excluded_dates) || !in_array($occurrence->format('Y-m-d'), $this->excluded_dates)) {
                $occurrences[] = $occurrence;
            }

            // Calculate next occurrence date
            $next = $this->calculateNextDate($current);
            if (!$next || $next->lte($current)) {
                // Prevent infinite loop if calculateNextDate fails
                break;
            }
            $current = $next;
        }

        return $occurrences;
    }

    /**
     * Calculate the next date based on frequency type and value.
     */
    private function calculateNextDate(Carbon $fromDate): ?Carbon
    {
        $next = $fromDate->copy();

        return match($this->frequency_type) {
            'weekly' => $next->addWeeks($this->frequency_value),
            'bi-weekly' => $next->addWeeks($this->frequency_value * 2),
            'monthly' => $next->addMonths($this->frequency_value),
            'quarterly' => $next->addMonths($this->frequency_value * 3),
            'yearly' => $next->addYears($this->frequency_value),
            default => null,
        };
    }

    /**
     * Get a human readable frequency description.
     */
    public function getFrequencyDescriptionAttribute(): string
    {
        $value = $this->frequency_value > 1 ? "Every {$this->frequency_value} " : '';

        return match($this->frequency_type) {
            'weekly' => $value . ($this->frequency_value === 1 ? 'week' : 'weeks'),
            'bi-weekly' => $value . 'bi-weeks',
            'monthly' => $value . ($this->frequency_value === 1 ? 'month' : 'months'),
            'quarterly' => $value . ($this->frequency_value === 1 ? 'quarter' : 'quarters'),
            'yearly' => $value . ($this->frequency_value === 1 ? 'year' : 'years'),
            default => 'Unknown frequency',
        };
    }

    /**
     * Get the next payment date for this schedule.
     */
    public function getNextPaymentDateAttribute(): ?Carbon
    {
        return $this->getNextOccurrenceAfter(now());
    }

    /**
     * Scope to get one-time expenses.
     */
    public function scopeOneTime(Builder $query): Builder
    {
        return $query->where('expense_type', 'one_time');
    }

    /**
     * Scope to get recurring expenses.
     */
    public function scopeRecurring(Builder $query): Builder
    {
        return $query->where('expense_type', 'recurring');
    }

    /**
     * Scope to get paid expenses.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope to get pending expenses.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Get payment status display name.
     */
    public function getPaymentStatusDisplayAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'Pending',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->payment_status),
        };
    }

    /**
     * Get payment status badge class.
     */
    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'bg-warning',
            'paid' => 'bg-success',
            'overdue' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    /**
     * Get expense type display name.
     */
    public function getExpenseTypeDisplayAttribute(): string
    {
        return match($this->expense_type) {
            'recurring' => 'Recurring',
            'one_time' => 'One-time',
            default => ucfirst($this->expense_type),
        };
    }

    /**
     * Get full category name including subcategory.
     */
    public function getFullCategoryNameAttribute(): string
    {
        $categoryName = $this->category?->name ?? 'Uncategorized';

        if ($this->subcategory) {
            return $categoryName . ' > ' . $this->subcategory->name;
        }

        return $categoryName;
    }

    /**
     * Mark expense as paid.
     */
    public function markAsPaid(float $amount, int $accountId, ?string $notes = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_amount' => $amount,
            'paid_from_account_id' => $accountId,
            'paid_date' => now(),
            'payment_notes' => $notes,
        ]);

        // Update account balance
        if ($this->paidFromAccount) {
            $this->paidFromAccount->updateBalance($amount, 'subtract');
        }
    }

    /**
     * Check if this is a one-time expense.
     */
    public function getIsOneTimeAttribute(): bool
    {
        return $this->expense_type === 'one_time';
    }

    /**
     * Check if payment has an attachment.
     */
    public function hasPaymentAttachment(): bool
    {
        return !empty($this->payment_attachment_path);
    }

    /**
     * Get the payment attachment download URL.
     */
    public function getPaymentAttachmentUrlAttribute(): ?string
    {
        if (!$this->hasPaymentAttachment()) {
            return null;
        }

        return route('accounting.expenses.payment-attachment', $this->id);
    }
}