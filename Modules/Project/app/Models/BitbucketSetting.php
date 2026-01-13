<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;

class BitbucketSetting extends Model
{
    protected $fillable = [
        'workspace',
        'email',
        'api_token',
        'sync_enabled',
        'sync_frequency',
        'last_sync_at',
    ];

    protected $casts = [
        'sync_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
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
     * Get the singleton instance (there's only one row).
     */
    public static function getInstance(): self
    {
        return self::firstOrCreate(['id' => 1]);
    }

    /**
     * Check if Bitbucket is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->workspace) && !empty($this->email) && !empty($this->api_token);
    }

    /**
     * Get the base URL for Bitbucket API.
     */
    public function getApiBaseUrl(): string
    {
        return 'https://api.bitbucket.org/2.0';
    }
}
