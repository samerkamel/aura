<?php

namespace Modules\Settings\Models;

use Carbon\Carbon;
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
        'cycle_start_day',
        'fiscal_year_start_month',
        'bank_details',
    ];

    protected $casts = [
        'bank_details' => 'array',
        'default_vat_rate' => 'decimal:2',
        'cycle_start_day' => 'integer',
        'fiscal_year_start_month' => 'integer',
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

    /**
     * Get the current fiscal/payroll period start date.
     * If cycle_start_day is 26, period runs from 26th to 25th of next month.
     */
    public function getCurrentPeriodStart(): Carbon
    {
        $today = Carbon::today();
        $cycleDay = $this->cycle_start_day ?? 1;

        // If we're before the cycle start day, the period started last month
        if ($today->day < $cycleDay) {
            return $today->copy()->subMonth()->day($cycleDay)->startOfDay();
        }

        return $today->copy()->day($cycleDay)->startOfDay();
    }

    /**
     * Get the current fiscal/payroll period end date.
     */
    public function getCurrentPeriodEnd(): Carbon
    {
        $periodStart = $this->getCurrentPeriodStart();
        $cycleDay = $this->cycle_start_day ?? 1;

        // Period ends the day before the next cycle start
        return $periodStart->copy()->addMonth()->day($cycleDay)->subDay()->endOfDay();
    }

    /**
     * Get the period start date for a given date.
     */
    public function getPeriodStartForDate(Carbon $date): Carbon
    {
        $cycleDay = $this->cycle_start_day ?? 1;

        if ($date->day < $cycleDay) {
            return $date->copy()->subMonth()->day($cycleDay)->startOfDay();
        }

        return $date->copy()->day($cycleDay)->startOfDay();
    }

    /**
     * Get the period end date for a given date.
     */
    public function getPeriodEndForDate(Carbon $date): Carbon
    {
        $periodStart = $this->getPeriodStartForDate($date);
        $cycleDay = $this->cycle_start_day ?? 1;

        return $periodStart->copy()->addMonth()->day($cycleDay)->subDay()->endOfDay();
    }

    /**
     * Get the current fiscal year start date.
     */
    public function getFiscalYearStart(): Carbon
    {
        $today = Carbon::today();
        $cycleDay = $this->cycle_start_day ?? 1;
        $fiscalMonth = $this->fiscal_year_start_month ?? 1;

        // Create the fiscal year start for this calendar year
        $fiscalYearStart = Carbon::create($today->year, $fiscalMonth, $cycleDay)->startOfDay();

        // If we're before the fiscal year start, use previous year
        if ($today->lt($fiscalYearStart)) {
            $fiscalYearStart->subYear();
        }

        return $fiscalYearStart;
    }

    /**
     * Get the current fiscal year end date.
     */
    public function getFiscalYearEnd(): Carbon
    {
        $fiscalYearStart = $this->getFiscalYearStart();
        $cycleDay = $this->cycle_start_day ?? 1;

        // Fiscal year ends the day before the next fiscal year starts
        return $fiscalYearStart->copy()->addYear()->day($cycleDay)->subDay()->endOfDay();
    }

    /**
     * Get the fiscal year start for a specific year.
     * For fiscal years starting late in the year (month >= 7), the fiscal year "X"
     * starts in calendar year "X-1". For example, FY2026 with December start
     * begins on Dec 26, 2025.
     */
    public function getFiscalYearStartForYear(int $year): Carbon
    {
        $cycleDay = $this->cycle_start_day ?? 1;
        $fiscalMonth = $this->fiscal_year_start_month ?? 1;

        // For fiscal years starting in the second half of the year (July onwards),
        // the fiscal year named "X" starts in calendar year "X-1"
        $calendarYear = $fiscalMonth >= 7 ? $year - 1 : $year;

        return Carbon::create($calendarYear, $fiscalMonth, $cycleDay)->startOfDay();
    }

    /**
     * Get the fiscal year end for a specific year.
     */
    public function getFiscalYearEndForYear(int $year): Carbon
    {
        $fiscalYearStart = $this->getFiscalYearStartForYear($year);
        $cycleDay = $this->cycle_start_day ?? 1;

        return $fiscalYearStart->copy()->addYear()->day($cycleDay)->subDay()->endOfDay();
    }

    /**
     * Get the period label for a given date (e.g., "Dec 26 - Jan 25, 2025").
     */
    public function getPeriodLabel(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::today();
        $start = $this->getPeriodStartForDate($date);
        $end = $this->getPeriodEndForDate($date);

        if ($start->year === $end->year) {
            return $start->format('M d') . ' - ' . $end->format('M d, Y');
        }

        return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
    }

    /**
     * Get the fiscal year label (e.g., "FY 2024-2025").
     */
    public function getFiscalYearLabel(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::today();
        $fiscalStart = $this->getFiscalYearStart();
        $fiscalEnd = $this->getFiscalYearEnd();

        return 'FY ' . $fiscalStart->year . '-' . $fiscalEnd->year;
    }

    /**
     * Check if a date falls within the current period.
     */
    public function isInCurrentPeriod(Carbon $date): bool
    {
        $start = $this->getCurrentPeriodStart();
        $end = $this->getCurrentPeriodEnd();

        return $date->between($start, $end);
    }

    /**
     * Check if a date falls within the current fiscal year.
     */
    public function isInCurrentFiscalYear(Carbon $date): bool
    {
        $start = $this->getFiscalYearStart();
        $end = $this->getFiscalYearEnd();

        return $date->between($start, $end);
    }
}
