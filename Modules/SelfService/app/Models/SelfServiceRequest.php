<?php

namespace Modules\SelfService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Models\Employee;
use Modules\Leave\Models\LeavePolicy;
use Modules\Attendance\Services\WorkingDaysService;
use App\Models\User;

class SelfServiceRequest extends Model
{
    use HasFactory;

    // Request Types
    const TYPE_LEAVE = 'leave';
    const TYPE_WFH = 'wfh';
    const TYPE_PERMISSION = 'permission';

    // Status Constants
    const STATUS_PENDING_MANAGER = 'pending_manager';
    const STATUS_PENDING_ADMIN = 'pending_admin';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'request_type',
        'status',
        'start_date',
        'end_date',
        'leave_policy_id',
        'request_data',
        'notes',
        'manager_id',
        'manager_approved_at',
        'manager_approved_by',
        'admin_approved_at',
        'admin_approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'request_data' => 'array',
        'manager_approved_at' => 'datetime',
        'admin_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the employee who made the request.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the leave policy (for leave requests).
     */
    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    /**
     * Get the manager who should approve.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get the user who approved as manager.
     */
    public function managerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    /**
     * Get the user who approved as admin.
     */
    public function adminApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    /**
     * Get the user who rejected the request.
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Scope to filter by employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('request_type', $type);
    }

    /**
     * Scope for pending manager approval.
     */
    public function scopePendingManager($query)
    {
        return $query->where('status', self::STATUS_PENDING_MANAGER);
    }

    /**
     * Scope for pending admin approval.
     */
    public function scopePendingAdmin($query)
    {
        return $query->where('status', self::STATUS_PENDING_ADMIN);
    }

    /**
     * Scope for any pending status.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING_MANAGER, self::STATUS_PENDING_ADMIN]);
    }

    /**
     * Scope for approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for cancelled requests.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope for requests awaiting approval from a specific manager.
     */
    public function scopeAwaitingManagerApproval($query, $managerId)
    {
        return $query->where('manager_id', $managerId)
            ->where('status', self::STATUS_PENDING_MANAGER);
    }

    /**
     * Scope for requests in a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    // =====================
    // HELPER METHODS
    // =====================

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_MANAGER, self::STATUS_PENDING_ADMIN]);
    }

    /**
     * Check if request can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->isPending();
    }

    /**
     * Get the number of working days in the request.
     * Excludes weekends and public holidays.
     */
    public function getDaysCount(): int
    {
        if (!$this->end_date) {
            // For single day requests, check if it's a working day
            $workingDaysService = app(WorkingDaysService::class);
            return $workingDaysService->isWorkingDay($this->start_date) ? 1 : 0;
        }

        $workingDaysService = app(WorkingDaysService::class);
        return $workingDaysService->calculateWorkingDays($this->start_date, $this->end_date);
    }

    /**
     * Accessor for days_count property.
     */
    public function getDaysCountAttribute(): int
    {
        return $this->getDaysCount();
    }

    /**
     * Get human-readable status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_MANAGER => 'Pending Manager Approval',
            self::STATUS_PENDING_ADMIN => 'Pending Admin Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get Bootstrap badge class for status.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_MANAGER, self::STATUS_PENDING_ADMIN => 'bg-label-warning',
            self::STATUS_APPROVED => 'bg-label-success',
            self::STATUS_REJECTED => 'bg-label-danger',
            self::STATUS_CANCELLED => 'bg-label-secondary',
            default => 'bg-label-info',
        };
    }

    /**
     * Get human-readable type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->request_type) {
            self::TYPE_LEAVE => 'Leave Request',
            self::TYPE_WFH => 'WFH Request',
            self::TYPE_PERMISSION => 'Permission Request',
            default => ucfirst($this->request_type),
        };
    }

    /**
     * Get Bootstrap badge class for type.
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->request_type) {
            self::TYPE_LEAVE => 'bg-label-primary',
            self::TYPE_WFH => 'bg-label-info',
            self::TYPE_PERMISSION => 'bg-label-warning',
            default => 'bg-label-secondary',
        };
    }
}
