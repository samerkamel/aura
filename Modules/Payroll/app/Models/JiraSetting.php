<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

class JiraSetting extends Model
{
    protected $fillable = [
        'base_url',
        'email',
        'api_token',
        'billable_projects',
        'sync_enabled',
        'sync_frequency',
    ];

    protected $casts = [
        'sync_enabled' => 'boolean',
    ];

    /**
     * Encrypt the API token when setting it.
     */
    public function setApiTokenAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['api_token'] = encrypt($value);
        } else {
            $this->attributes['api_token'] = null;
        }
    }

    /**
     * Decrypt the API token when getting it.
     */
    public function getApiTokenAttribute($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get billable projects as an array.
     */
    public function getBillableProjectsArrayAttribute(): array
    {
        if (empty($this->billable_projects)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->billable_projects)));
    }

    /**
     * Get the singleton instance (there's only one row).
     */
    public static function getInstance(): self
    {
        return self::firstOrCreate(['id' => 1]);
    }

    /**
     * Check if Jira is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->base_url) && !empty($this->email) && !empty($this->api_token);
    }
}
