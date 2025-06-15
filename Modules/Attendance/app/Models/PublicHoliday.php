<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Modules\Attendance\Database\Factories\PublicHolidayFactory;

/**
 * PublicHoliday Model
 *
 * Represents a public holiday with name and date
 * Used for attendance calculation to exclude holidays from work days
 *
 * @property int $id
 * @property string $name
 * @property \Carbon\Carbon $date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @author Dev Agent
 */
class PublicHoliday extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'date'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date' => 'date'
    ];

    /**
     * Scope to get holidays for a specific year
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }

    /**
     * Scope to get holidays for the current year
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentYear($query)
    {
        return $query->forYear(now()->year);
    }

    /**
     * Get holidays ordered by date
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedByDate($query)
    {
        return $query->orderBy('date');
    }

    /**
     * Create a new factory instance for the model
     *
     * @return \Modules\Attendance\Database\Factories\PublicHolidayFactory
     */
    protected static function newFactory(): PublicHolidayFactory
    {
        return PublicHolidayFactory::new();
    }
}
