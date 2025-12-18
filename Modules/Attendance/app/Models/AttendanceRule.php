<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * AttendanceRule Model
 *
 * Manages attendance rules for flexible hours, late penalties, permissions, and WFH policies
 *
 * @property int $id
 * @property string $rule_name
 * @property string $rule_type
 * @property array $config
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @author GitHub Copilot
 */
class AttendanceRule extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   */
  protected $fillable = [
    'rule_name',
    'rule_type',
    'config',
  ];

  /**
   * The attributes that should be cast.
   */
  protected $casts = [
    'config' => 'array',
  ];

  /**
   * Rule type constants
   */
  public const TYPE_FLEXIBLE_HOURS = 'flexible_hours';
  public const TYPE_LATE_PENALTY = 'late_penalty';
  public const TYPE_PERMISSION = 'permission';
  public const TYPE_WFH_POLICY = 'wfh_policy';

  /**
   * Get the available rule types
   */
  public static function getAvailableTypes(): array
  {
    return [
      self::TYPE_FLEXIBLE_HOURS,
      self::TYPE_LATE_PENALTY,
      self::TYPE_PERMISSION,
      self::TYPE_WFH_POLICY,
    ];
  }

  /**
   * Get the active flexible hours rule
   */
  public static function getFlexibleHoursRule(): ?self
  {
    return self::where('rule_type', self::TYPE_FLEXIBLE_HOURS)->first();
  }

  /**
   * Get the active permission rule
   */
  public static function getPermissionRule(): ?self
  {
    return self::where('rule_type', self::TYPE_PERMISSION)->first();
  }

  /**
   * Get the active WFH policy rule
   */
  public static function getWfhPolicyRule(): ?self
  {
    return self::where('rule_type', self::TYPE_WFH_POLICY)->first();
  }

  /**
   * Get all late penalty rules ordered by late_minutes ascending
   */
  public static function getLatePenaltyRules(): \Illuminate\Database\Eloquent\Collection
  {
    return self::where('rule_type', self::TYPE_LATE_PENALTY)
      ->get()
      ->sortBy(function ($rule) {
        return $rule->config['late_minutes'] ?? 0;
      });
  }

  /**
   * Calculate the penalty minutes for a given late duration
   *
   * @param int $lateMinutes The number of minutes late
   * @return int The penalty in minutes (0 if no penalty applies)
   */
  public static function calculateLatePenalty(int $lateMinutes): int
  {
    if ($lateMinutes <= 0) {
      return 0;
    }

    $rules = self::getLatePenaltyRules();
    $penalty = 0;

    // Find the highest applicable penalty tier
    foreach ($rules as $rule) {
      $ruleThreshold = $rule->config['late_minutes'] ?? 0;
      $rulePenalty = $rule->config['penalty_minutes'] ?? 0;

      if ($lateMinutes >= $ruleThreshold) {
        $penalty = $rulePenalty;
      }
    }

    return $penalty;
  }

  /**
   * Create a new factory instance for the model.
   */
  protected static function newFactory()
  {
    return \Modules\Attendance\Database\Factories\AttendanceRuleFactory::new();
  }

  /**
   * Scope a query to only include rules of a specific type.
   */
  public function scopeByType($query, string $type)
  {
    return $query->where('rule_type', $type);
  }
}
