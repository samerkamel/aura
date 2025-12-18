<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Position Model
 *
 * Represents a job position/title within the organization.
 * Positions can have salary ranges and are assigned to employees.
 *
 * @property int $id
 * @property string $title
 * @property string|null $title_ar
 * @property string|null $department
 * @property string|null $level
 * @property float|null $min_salary
 * @property float|null $max_salary
 * @property string|null $description
 * @property string|null $requirements
 * @property bool $is_active
 *
 * @author Dev Agent
 */
class Position extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'title_ar',
        'department',
        'level',
        'min_salary',
        'max_salary',
        'description',
        'requirements',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Position levels.
     */
    public const LEVELS = [
        'intern' => 'Intern',
        'junior' => 'Junior',
        'mid' => 'Mid-Level',
        'senior' => 'Senior',
        'lead' => 'Lead',
        'manager' => 'Manager',
        'director' => 'Director',
        'executive' => 'Executive',
    ];

    /**
     * Get the employees with this position.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Scope a query to only include active positions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by title.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('department')->orderBy('title');
    }

    /**
     * Get the full title with level.
     */
    public function getFullTitleAttribute(): string
    {
        if ($this->level && isset(self::LEVELS[$this->level])) {
            return self::LEVELS[$this->level] . ' ' . $this->title;
        }
        return $this->title;
    }

    /**
     * Get the salary range formatted.
     */
    public function getSalaryRangeAttribute(): ?string
    {
        if ($this->min_salary && $this->max_salary) {
            return '$' . number_format($this->min_salary, 0) . ' - $' . number_format($this->max_salary, 0);
        } elseif ($this->min_salary) {
            return 'From $' . number_format($this->min_salary, 0);
        } elseif ($this->max_salary) {
            return 'Up to $' . number_format($this->max_salary, 0);
        }
        return null;
    }

    /**
     * Check if a salary is within the position's range.
     */
    public function isSalaryInRange(float $salary): bool
    {
        if ($this->min_salary && $salary < $this->min_salary) {
            return false;
        }
        if ($this->max_salary && $salary > $this->max_salary) {
            return false;
        }
        return true;
    }
}
