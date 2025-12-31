<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Employee Model
 *
 * Represents an employee in the HR system with their personal information,
 * contact details, position, and salary information.
 *
 * @author Dev Agent
 */
class Employee extends Model
{
  use HasFactory;

  /**
   * Available teams that employees can belong to.
   * These correspond to income line items in the accounting system.
   */
  public const TEAMS = [
    'PHP' => 'PHP',
    '.NET' => '.NET',
    'Mobile' => 'Mobile',
    'Design' => 'Design',
    'Websites' => 'Websites',
  ];

  /**
   * The attributes that are mass assignable.
   */
  protected $fillable = [
    'name',
    'name_ar',
    'email',
    'personal_email',
    'attendance_id',
    'national_id',
    'national_insurance_number',
    'position',
    'position_id',
    'team',
    'manager_id',
    'start_date',
    'contact_info',
    'emergency_contact',
    'bank_info',
    'base_salary',
    'hourly_rate',
    'status',
    'termination_date',
    'jira_account_id',
    'jira_author_name',
    'billable_hours_applicable',
  ];

  /**
   * The attributes that should be cast.
   */
  protected $casts = [
    'start_date' => 'date',
    'termination_date' => 'date',
    'contact_info' => 'array',
    'emergency_contact' => 'array',
    'base_salary' => 'decimal:2',
    'hourly_rate' => 'decimal:2',
    'billable_hours_applicable' => 'boolean',
  ];

  /**
   * Encrypt the bank_info when setting it.
   */
  public function setBankInfoAttribute($value): void
  {
    if (is_array($value)) {
      $this->attributes['bank_info'] = encrypt(json_encode($value));
    } elseif (is_null($value)) {
      $this->attributes['bank_info'] = null;
    } else {
      $this->attributes['bank_info'] = encrypt($value);
    }
  }

  /**
   * Decrypt the bank_info when getting it.
   */
  public function getBankInfoAttribute($value): ?array
  {
    if (is_null($value)) {
      return null;
    }

    try {
      $decrypted = decrypt($value);

      return is_string($decrypted) ? json_decode($decrypted, true) : $decrypted;
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Get the employee's full name with proper formatting.
   */
  public function getFullNameAttribute(): string
  {
    return $this->name;
  }

  /**
   * Scope a query to only include active employees.
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeActive($query)
  {
    return $query->where('status', 'active');
  }

  /**
   * Scope a query to only include terminated employees.
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeTerminated($query)
  {
    return $query->whereIn('status', ['terminated', 'resigned']);
  }

  /**
   * Scope a query to only include resigned employees.
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeResigned($query)
  {
    return $query->where('status', 'resigned');
  }

  /**
   * Get the bank account number from the encrypted bank_info.
   */
  public function getBankAccountNumberAttribute(): ?string
  {
    return $this->bank_info['account_number'] ?? null;
  }

  /**
   * Get the position of this employee.
   * Named 'positionRelation' to avoid conflict with legacy 'position' string column.
   */
  public function positionRelation(): BelongsTo
  {
    return $this->belongsTo(Position::class, 'position_id');
  }

  /**
   * Get the manager of this employee.
   */
  public function manager(): BelongsTo
  {
    return $this->belongsTo(Employee::class, 'manager_id');
  }

  /**
   * Get the employees that report to this employee.
   */
  public function subordinates()
  {
    return $this->hasMany(Employee::class, 'manager_id');
  }

  /**
   * Check if this employee is a manager (has subordinates).
   */
  public function isManager(): bool
  {
    return $this->subordinates()->exists();
  }

  /**
   * Relationship to documents
   */
  public function documents()
  {
    return $this->hasMany(EmployeeDocument::class);
  }

  /**
   * Relationship to salary history records.
   */
  public function salaryHistory(): HasMany
  {
    return $this->hasMany(EmployeeSalaryHistory::class)->orderBy('effective_date', 'desc');
  }

  /**
   * Get the salary that was effective at a specific date.
   * Falls back to base_salary if no history found.
   */
  public function getSalaryAt(Carbon $date): ?float
  {
    $salaryRecord = $this->salaryHistory()
        ->effectiveAt($date)
        ->first();

    return $salaryRecord?->base_salary ?? $this->base_salary;
  }

  /**
   * Get the current salary from history (or fallback to base_salary).
   */
  public function getCurrentSalaryFromHistory(): ?float
  {
    $currentSalary = $this->salaryHistory()
        ->current()
        ->first();

    return $currentSalary?->base_salary ?? $this->base_salary;
  }

  /**
   * Add a new salary change record.
   * Automatically closes the previous salary record.
   */
  public function addSalaryChange(
    float $newSalary,
    Carbon $effectiveDate,
    string $reason = 'adjustment',
    ?string $notes = null,
    ?int $approvedBy = null
  ): EmployeeSalaryHistory {
    // Close the current salary record
    $this->salaryHistory()
        ->current()
        ->update(['end_date' => $effectiveDate->copy()->subDay()]);

    // Create new salary record
    $salaryRecord = $this->salaryHistory()->create([
        'base_salary' => $newSalary,
        'currency' => 'EGP',
        'effective_date' => $effectiveDate,
        'end_date' => null,
        'reason' => $reason,
        'notes' => $notes,
        'approved_by' => $approvedBy,
        'created_by' => auth()->id(),
    ]);

    // Also update the base_salary field for backwards compatibility
    $this->update(['base_salary' => $newSalary]);

    return $salaryRecord;
  }

  /**
   * Get salary history with change percentages for display.
   */
  public function getSalaryHistoryWithChanges(): \Illuminate\Support\Collection
  {
    return $this->salaryHistory()
        ->orderBy('effective_date', 'desc')
        ->get()
        ->map(function ($record) {
            $record->change_percentage = $record->change_percentage;
            return $record;
        });
  }

  /**
   * Get the assets assigned to this employee.
   */
  public function assets()
  {
    return $this->belongsToMany(\Modules\AssetManager\Models\Asset::class, 'asset_employee')
      ->withPivot(['assigned_date', 'returned_date', 'notes'])
      ->withTimestamps();
  }

  /**
   * Get the assets currently assigned to this employee.
   */
  public function currentAssets()
  {
    return $this->belongsToMany(\Modules\AssetManager\Models\Asset::class, 'asset_employee')
      ->withPivot(['assigned_date', 'returned_date', 'notes'])
      ->wherePivotNull('returned_date')
      ->withTimestamps();
  }

  /**
   * Get the billable hours records for this employee.
   */
  public function billableHours()
  {
    return $this->hasMany(\Modules\Payroll\Models\BillableHour::class);
  }

  /**
   * Get the Jira worklog entries for this employee.
   */
  public function jiraWorklogs()
  {
    return $this->hasMany(\Modules\Payroll\Models\JiraWorklog::class);
  }

  /**
   * Get the projects this employee is assigned to.
   */
  public function projects()
  {
    return $this->belongsToMany(\Modules\Project\Models\Project::class, 'project_employee')
      ->withPivot(['role', 'auto_assigned', 'assigned_at'])
      ->withTimestamps();
  }

  /**
   * Get the user account linked to this employee.
   */
  public function user()
  {
    return $this->hasOne(\App\Models\User::class, 'email', 'email');
  }

  /**
   * Create a new factory instance for the model.
   */
  protected static function newFactory()
  {
    return \Modules\HR\Database\Factories\EmployeeFactory::new();
  }
}
