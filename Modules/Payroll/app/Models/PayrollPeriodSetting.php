<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PayrollPeriodSetting extends Model
{
    protected $fillable = [
        'period_start',
        'target_billable_hours',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'target_billable_hours' => 'decimal:2',
    ];

    /**
     * Get the setting for a specific period.
     */
    public static function forPeriod(Carbon $periodDate): ?self
    {
        return self::where('period_start', $periodDate->copy()->startOfMonth()->toDateString())->first();
    }

    /**
     * Get or create setting for a specific period.
     */
    public static function getOrCreateForPeriod(Carbon $periodDate): self
    {
        return self::firstOrCreate(
            ['period_start' => $periodDate->copy()->startOfMonth()->toDateString()],
            ['target_billable_hours' => null]
        );
    }
}
