<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Models\Employee;
use Modules\Attendance\Database\Factories\AttendanceLogFactory;

/**
 * AttendanceLog Model
 *
 * Stores raw attendance logs imported from CSV files
 *
 * @property int $id
 * @property int $employee_id
 * @property \DateTime $timestamp
 * @property string $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AttendanceLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'timestamp',
        'type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'timestamp' => 'datetime',
    ];

    /**
     * Get the employee that this attendance log belongs to.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AttendanceLogFactory::new();
    }
}
