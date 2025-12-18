<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HR\Models\Employee;

class ProjectReportLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_report_id',
        'project_id',
        'employee_id',
        'hours',
        'rate',
        'amount',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the parent report.
     */
    public function report()
    {
        return $this->belongsTo(ProjectReport::class, 'project_report_id');
    }

    /**
     * Get the project.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Calculate and set the amount based on hours and rate.
     */
    public function calculateAmount()
    {
        $this->amount = $this->hours * $this->rate;
        return $this;
    }
}
