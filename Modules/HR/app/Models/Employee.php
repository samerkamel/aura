<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
   * The attributes that are mass assignable.
   */
  protected $fillable = [
    'name',
    'email',
    'position',
    'start_date',
    'contact_info',
    'bank_info',
    'base_salary',
    'status',
    'termination_date',
  ];

  /**
   * The attributes that should be cast.
   */
  protected $casts = [
    'start_date' => 'date',
    'termination_date' => 'date',
    'contact_info' => 'array',
    'base_salary' => 'decimal:2',
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
   * Relationship to documents
   */
  public function documents()
  {
    return $this->hasMany(EmployeeDocument::class);
  }

  /**
   * Relationship to salary histories
   */
  public function salaryHistories()
  {
    return $this->hasMany(SalaryHistory::class)->orderBy('change_date', 'desc');
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
   * Create a new factory instance for the model.
   */
  protected static function newFactory()
  {
    return \Modules\HR\Database\Factories\EmployeeFactory::new();
  }
}
