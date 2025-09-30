<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
  use HasFactory, Notifiable, HasApiTokens;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
    'is_active',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Get the business units this user has access to.
   */
  public function businessUnits(): BelongsToMany
  {
    return $this->belongsToMany(BusinessUnit::class, 'business_unit_user')
                ->withPivot('role')
                ->withTimestamps();
  }

  /**
   * Get the roles that belong to the user.
   */
  public function roles(): BelongsToMany
  {
    return $this->belongsToMany(Role::class);
  }

  /**
   * Check if user has a specific role.
   */
  public function hasRole(string $roleName): bool
  {
    return $this->roles()->where('name', $roleName)->exists();
  }

  /**
   * Check if user has any of the given roles.
   */
  public function hasAnyRole(array $roles): bool
  {
    return $this->roles()->whereIn('name', $roles)->exists();
  }

  /**
   * Check if user has a specific permission.
   */
  public function hasPermission(string $permissionName): bool
  {
    return $this->roles()->whereHas('permissions', function ($query) use ($permissionName) {
      $query->where('name', $permissionName);
    })->exists();
  }

  /**
   * Get all permissions for this user.
   */
  public function getAllPermissions()
  {
    return Permission::whereHas('roles', function ($query) {
      $query->whereIn('role_id', $this->roles()->pluck('id'));
    })->get();
  }

  /**
   * Assign role to user.
   */
  public function assignRole(Role|string $role): void
  {
    if (is_string($role)) {
      $role = Role::where('name', $role)->first();
    }

    if ($role && !$this->hasRole($role->name)) {
      $this->roles()->attach($role->id);
    }
  }

  /**
   * Remove role from user.
   */
  public function removeRole(Role|string $role): void
  {
    if (is_string($role)) {
      $role = Role::where('name', $role)->first();
    }

    if ($role) {
      $this->roles()->detach($role->id);
    }
  }

  /**
   * Check if user can perform action (alias for hasPermission).
   */
  public function can($ability, $arguments = []): bool
  {
    if (is_string($ability)) {
      return $this->hasPermission($ability);
    }

    return parent::can($ability, $arguments);
  }

  /**
   * Check if user has access to a specific business unit.
   */
  public function hasAccessToBusinessUnit($businessUnitId): bool
  {
    return $this->businessUnits()->where('business_unit_id', $businessUnitId)->exists();
  }

  /**
   * Get user's accessible business unit IDs.
   */
  public function getAccessibleBusinessUnitIds(): array
  {
    return $this->businessUnits()->pluck('business_unit_id')->toArray();
  }

  /**
   * Check if user can access all business units (has super admin role).
   */
  public function canAccessAllBusinessUnits(): bool
  {
    return $this->hasRole('super-admin');
  }
}
