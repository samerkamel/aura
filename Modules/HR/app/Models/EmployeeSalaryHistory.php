<?php

namespace Modules\HR\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmployeeSalaryHistory extends Model
{
    use HasFactory;

    protected $table = 'employee_salary_history';

    protected $fillable = [
        'employee_id',
        'base_salary',
        'currency',
        'effective_date',
        'end_date',
        'reason',
        'notes',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'base_salary' => 'decimal:2',
    ];

    /**
     * Reason labels for display
     */
    public const REASON_LABELS = [
        'initial' => 'Initial Salary',
        'annual_review' => 'Annual Review',
        'promotion' => 'Promotion',
        'adjustment' => 'Adjustment',
        'correction' => 'Correction',
        'other' => 'Other',
    ];

    /**
     * Get the employee this salary record belongs to.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who approved this salary change.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created this salary record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get salary records effective at a specific date.
     */
    public function scopeEffectiveAt($query, $date)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $query->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
    }

    /**
     * Scope to get current (active) salary records.
     */
    public function scopeCurrent($query)
    {
        return $query->whereNull('end_date');
    }

    /**
     * Scope to order by effective date descending (most recent first).
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('effective_date', 'desc');
    }

    /**
     * Check if this salary record is currently active.
     */
    public function isCurrent(): bool
    {
        return is_null($this->end_date);
    }

    /**
     * Check if this salary was effective at a given date.
     */
    public function wasEffectiveAt(Carbon $date): bool
    {
        $isAfterStart = $this->effective_date->lte($date);
        $isBeforeEnd = is_null($this->end_date) || $this->end_date->gte($date);

        return $isAfterStart && $isBeforeEnd;
    }

    /**
     * Get the reason label for display.
     */
    public function getReasonLabelAttribute(): string
    {
        return self::REASON_LABELS[$this->reason] ?? ucfirst($this->reason);
    }

    /**
     * Get formatted salary for display.
     */
    public function getFormattedSalaryAttribute(): string
    {
        return number_format($this->base_salary, 2) . ' ' . $this->currency;
    }

    /**
     * Calculate the salary change percentage from previous record.
     */
    public function getChangePercentageAttribute(): ?float
    {
        $previous = self::where('employee_id', $this->employee_id)
            ->where('effective_date', '<', $this->effective_date)
            ->orderBy('effective_date', 'desc')
            ->first();

        if (!$previous || $previous->base_salary == 0) {
            return null;
        }

        return (($this->base_salary - $previous->base_salary) / $previous->base_salary) * 100;
    }
}
