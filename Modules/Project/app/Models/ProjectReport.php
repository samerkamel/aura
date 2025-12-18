<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class ProjectReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'total_hours',
        'total_amount',
        'projects_data',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_hours' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'projects_data' => 'array',
    ];

    /**
     * Get the report lines.
     */
    public function lines()
    {
        return $this->hasMany(ProjectReportLine::class);
    }

    /**
     * Get the user who created the report.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get lines grouped by project.
     */
    public function getLinesByProject()
    {
        return $this->lines()
            ->with(['project', 'employee'])
            ->get()
            ->groupBy('project_id');
    }

    /**
     * Calculate and update totals from lines.
     */
    public function recalculateTotals()
    {
        $this->total_hours = $this->lines()->sum('hours');
        $this->total_amount = $this->lines()->sum('amount');
        $this->save();
    }
}
