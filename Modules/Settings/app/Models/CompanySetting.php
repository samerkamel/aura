<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'company_name_ar',
        'logo_path',
        'dashboard_logo_path',
        'address',
        'address_ar',
        'phone',
        'email',
        'website',
        'tax_id',
        'commercial_register',
        'default_vat_rate',
        'currency',
        'bank_details',
    ];

    protected $casts = [
        'bank_details' => 'array',
        'default_vat_rate' => 'decimal:2',
    ];

    /**
     * Get the singleton company settings instance.
     * Creates a default record if none exists.
     */
    public static function getSettings(): self
    {
        return Cache::remember('company_settings', 3600, function () {
            $settings = static::first();

            if (!$settings) {
                $settings = static::create([
                    'company_name' => 'Company Name',
                    'default_vat_rate' => 14.00,
                    'currency' => 'EGP',
                ]);
            }

            return $settings;
        });
    }

    /**
     * Clear the cached settings.
     */
    public static function clearCache(): void
    {
        Cache::forget('company_settings');
    }

    /**
     * Get the full URL for the logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        $url = Storage::disk('public')->url($this->logo_path);

        // Force HTTPS in production
        if (app()->environment('production') || request()->secure()) {
            $url = str_replace('http://', 'https://', $url);
        }

        return $url;
    }

    /**
     * Get the logo as a base64 data URI for PDF embedding.
     */
    public function getLogoBase64Attribute(): ?string
    {
        if (!$this->logo_path || !Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        $content = Storage::disk('public')->get($this->logo_path);
        $mimeType = Storage::disk('public')->mimeType($this->logo_path);

        return 'data:' . $mimeType . ';base64,' . base64_encode($content);
    }

    /**
     * Get the full URL for the dashboard logo.
     */
    public function getDashboardLogoUrlAttribute(): ?string
    {
        if (!$this->dashboard_logo_path) {
            return null;
        }

        $url = Storage::disk('public')->url($this->dashboard_logo_path);

        // Force HTTPS in production
        if (app()->environment('production') || request()->secure()) {
            $url = str_replace('http://', 'https://', $url);
        }

        return $url;
    }

    /**
     * Get formatted bank details.
     */
    public function getFormattedBankDetailsAttribute(): string
    {
        if (!$this->bank_details) {
            return '';
        }

        $lines = [];
        if (!empty($this->bank_details['bank_name'])) {
            $lines[] = $this->bank_details['bank_name'];
        }
        if (!empty($this->bank_details['account_name'])) {
            $lines[] = 'Account: ' . $this->bank_details['account_name'];
        }
        if (!empty($this->bank_details['account_number'])) {
            $lines[] = 'Account No: ' . $this->bank_details['account_number'];
        }
        if (!empty($this->bank_details['iban'])) {
            $lines[] = 'IBAN: ' . $this->bank_details['iban'];
        }
        if (!empty($this->bank_details['swift'])) {
            $lines[] = 'SWIFT: ' . $this->bank_details['swift'];
        }

        return implode("\n", $lines);
    }

    /**
     * Boot method to clear cache on save.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            static::clearCache();
        });
    }
}
