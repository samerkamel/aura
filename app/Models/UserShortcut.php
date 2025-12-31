<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserShortcut extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'icon',
        'subtitle',
        'slug',
        'required_roles',
        'sort_order',
    ];

    protected $casts = [
        'required_roles' => 'array',
    ];

    /**
     * Get the user that owns the shortcut.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get shortcuts ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Check if the user still has access to this shortcut.
     */
    public function isAccessible(): bool
    {
        if (empty($this->required_roles)) {
            return true;
        }

        $userRoles = $this->user->roles->pluck('name')->toArray();

        // Super admin has access to everything
        if (in_array('super-admin', $userRoles)) {
            return true;
        }

        // Check if 'all' is in required roles
        if (in_array('all', $this->required_roles)) {
            return true;
        }

        // Check if user has any of the required roles
        return !empty(array_intersect($this->required_roles, $userRoles));
    }
}
