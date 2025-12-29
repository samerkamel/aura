<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'perfex_id',
        'name',
        'email',
        'phone',
        'address',
        'company_name',
        'tax_id',
        'website',
        'contact_persons',
        'notes',
        'status',
        'type',
    ];

    protected $casts = [
        'contact_persons' => 'array',
    ];

    /**
     * Get the contracts for this customer.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(\Modules\Accounting\Models\Contract::class);
    }

    /**
     * Get the projects for this customer.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(\Modules\Project\Models\Project::class);
    }

    /**
     * Get the invoices for this customer.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(\Modules\Invoicing\Models\Invoice::class);
    }

    /**
     * Scope to filter active customers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Get the display name for the customer.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'company' && $this->company_name) {
            return $this->company_name;
        }
        return $this->name;
    }

    /**
     * Get the full contact information.
     */
    public function getFullContactAttribute(): string
    {
        $contact = $this->display_name;
        if ($this->email) {
            $contact .= ' (' . $this->email . ')';
        }
        return $contact;
    }

    /**
     * Get active contracts count.
     */
    public function getActiveContractsCountAttribute(): int
    {
        return $this->contracts()->where('status', 'active')->count();
    }

    /**
     * Get total contract value.
     */
    public function getTotalContractValueAttribute(): float
    {
        return $this->contracts()->where('status', 'active')->sum('total_amount');
    }
}