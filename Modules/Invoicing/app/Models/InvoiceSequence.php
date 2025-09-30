<?php

namespace Modules\Invoicing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * InvoiceSequence Model
 *
 * Manages invoice numbering sequences with configurable formats.
 * Supports sector-based and business unit-based sequences.
 */
class InvoiceSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prefix',
        'format',
        'current_number',
        'starting_number',
        'sector_ids',
        'business_unit_id',
        'is_active',
        'description',
    ];

    protected $casts = [
        'current_number' => 'integer',
        'starting_number' => 'integer',
        'sector_ids' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the business unit this sequence belongs to.
     */
    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessUnit::class);
    }

    /**
     * Get invoices using this sequence.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the sectors this sequence is restricted to.
     */
    public function sectors()
    {
        if (!$this->sector_ids || empty($this->sector_ids)) {
            return collect([]);
        }

        // Ensure sector_ids is an array
        $sectorIds = is_array($this->sector_ids) ? $this->sector_ids : json_decode($this->sector_ids, true);

        if (!is_array($sectorIds) || empty($sectorIds)) {
            return collect([]);
        }

        return \App\Models\Sector::whereIn('id', $sectorIds)->get();
    }

    /**
     * Scope to get only active sequences.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get sequences for a specific business unit.
     */
    public function scopeForBusinessUnit(Builder $query, int $businessUnitId): Builder
    {
        return $query->where(function ($q) use ($businessUnitId) {
            $q->where('business_unit_id', $businessUnitId)
              ->orWhereNull('business_unit_id');
        });
    }

    /**
     * Scope to get sequences for a specific sector.
     */
    public function scopeForSector(Builder $query, int $sectorId): Builder
    {
        return $query->where(function ($q) use ($sectorId) {
            $q->whereJsonContains('sector_ids', $sectorId)
              ->orWhereNull('sector_ids');
        });
    }

    /**
     * Generate the next invoice number.
     */
    public function generateInvoiceNumber(): string
    {
        $this->increment('current_number');
        return $this->formatInvoiceNumber($this->current_number);
    }

    /**
     * Preview what the next invoice number would be without incrementing.
     */
    public function previewNextInvoiceNumber(): string
    {
        $nextNumber = $this->current_number + 1;
        return $this->formatInvoiceNumber($nextNumber);
    }

    /**
     * Format invoice number with proper placeholder replacement.
     */
    private function formatInvoiceNumber(int $number): string
    {
        $format = $this->format;

        // Basic replacements
        $replacements = [
            '{PREFIX}' => $this->prefix ?? '',
            '{YEAR}' => now()->year,
            '{MONTH}' => now()->format('m'),
        ];

        // Replace basic placeholders
        foreach ($replacements as $placeholder => $value) {
            $format = str_replace($placeholder, $value, $format);
        }

        // Handle {NUMBER:X} format with padding
        $format = preg_replace_callback('/\{NUMBER:(\d+)\}/', function ($matches) use ($number) {
            $padding = (int) $matches[1];
            return str_pad($number, $padding, '0', STR_PAD_LEFT);
        }, $format);

        // Handle simple {NUMBER} format (default padding of 4)
        $format = str_replace('{NUMBER}', str_pad($number, 4, '0', STR_PAD_LEFT), $format);

        return $format;
    }

    /**
     * Reset sequence to starting number.
     */
    public function resetSequence(): void
    {
        $this->update(['current_number' => $this->starting_number - 1]);
    }

    /**
     * Check if sequence can be used for a specific business unit and sector.
     */
    public function canBeUsedFor(int $businessUnitId, int $sectorId = null): bool
    {
        // Check business unit access
        if ($this->business_unit_id && $this->business_unit_id !== $businessUnitId) {
            return false;
        }

        // Check sector access
        if ($sectorId && $this->sector_ids && !in_array($sectorId, $this->sector_ids)) {
            return false;
        }

        return $this->is_active;
    }

    /**
     * Get available format placeholders.
     */
    public static function getFormatPlaceholders(): array
    {
        return [
            '{PREFIX}' => 'Sequence prefix',
            '{YEAR}' => 'Current year (4 digits)',
            '{MONTH}' => 'Current month (2 digits)',
            '{NUMBER}' => 'Sequential number (padded to 4 digits)',
        ];
    }

    /**
     * Get format example based on current format.
     */
    public function getFormatExampleAttribute(): string
    {
        $replacements = [
            '{PREFIX}' => $this->prefix ?? 'INV',
            '{YEAR}' => now()->year,
            '{MONTH}' => now()->format('m'),
            '{NUMBER}' => str_pad($this->current_number + 1, 4, '0', STR_PAD_LEFT),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->format
        );
    }
}