<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * ExpenseAttachment Model
 *
 * Represents an attachment (receipt, invoice, document) for an expense.
 */
class ExpenseAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_schedule_id',
        'file_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Get the expense this attachment belongs to.
     */
    public function expenseSchedule(): BelongsTo
    {
        return $this->belongsTo(ExpenseSchedule::class);
    }

    /**
     * Get the user who uploaded this attachment.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    /**
     * Get the full URL to the attachment.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if this is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Check if this is a PDF.
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Get file icon class based on mime type.
     */
    public function getIconClassAttribute(): string
    {
        if ($this->is_image) {
            return 'ti-photo';
        }
        if ($this->is_pdf) {
            return 'ti-file-type-pdf';
        }
        if (str_contains($this->mime_type ?? '', 'spreadsheet') || str_contains($this->original_name, '.xls')) {
            return 'ti-file-spreadsheet';
        }
        if (str_contains($this->mime_type ?? '', 'document') || str_contains($this->original_name, '.doc')) {
            return 'ti-file-text';
        }
        return 'ti-file';
    }

    /**
     * Delete the file when the model is deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }
}
