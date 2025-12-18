<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JiraSyncLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sync_date',
        'started_at',
        'completed_at',
        'status',
        'total_records',
        'successful_records',
        'failed_records',
        'error_details',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'sync_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'error_details' => 'array',
    ];

    /**
     * Get the latest sync log
     */
    public static function latest()
    {
        return self::orderBy('started_at', 'desc')->first();
    }

    /**
     * Check if a sync is currently in progress
     */
    public static function isInProgress()
    {
        return self::where('status', 'in_progress')->exists();
    }

    /**
     * Mark the sync as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the sync as failed
     */
    public function markAsFailed($errors = [])
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_details' => $errors,
        ]);
    }

    /**
     * Update sync progress
     */
    public function updateProgress($successCount, $failureCount)
    {
        $this->update([
            'successful_records' => $successCount,
            'failed_records' => $failureCount,
            'total_records' => $successCount + $failureCount,
        ]);
    }
}