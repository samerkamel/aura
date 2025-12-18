<?php

namespace Modules\Project\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Payroll\Models\JiraWorklog;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'code',
        'description',
        'jira_project_id',
        'needs_monthly_report',
        'is_active',
    ];

    protected $casts = [
        'needs_monthly_report' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get worklogs for this project within a date range.
     * Matches worklogs by issue_key prefix (e.g., VIS-123 matches project with code VIS).
     */
    public function getWorklogsInPeriod($startDate, $endDate)
    {
        return JiraWorklog::where('issue_key', 'LIKE', $this->code . '-%')
            ->whereBetween('worklog_started', [$startDate, $endDate])
            ->get();
    }

    /**
     * Get employee hours summary for this project within a date range.
     * Returns array of employee_id => total_hours.
     */
    public function getEmployeeHoursInPeriod($startDate, $endDate)
    {
        return JiraWorklog::where('issue_key', 'LIKE', $this->code . '-%')
            ->whereBetween('worklog_started', [$startDate, $endDate])
            ->selectRaw('employee_id, SUM(time_spent_hours) as total_hours')
            ->groupBy('employee_id')
            ->pluck('total_hours', 'employee_id')
            ->toArray();
    }

    /**
     * Scope for active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for projects needing monthly reports.
     */
    public function scopeNeedsMonthlyReport($query)
    {
        return $query->where('needs_monthly_report', true)->where('is_active', true);
    }

    /**
     * Get the report lines for this project.
     */
    public function reportLines()
    {
        return $this->hasMany(ProjectReportLine::class);
    }

    /**
     * Get the customer that owns this project.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
