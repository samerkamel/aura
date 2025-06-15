<?php

namespace Modules\LetterGenerator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Modules\LetterGenerator\Database\Factories\LetterTemplateFactory;

/**
 * Letter Template Model
 *
 * Represents a template for generating employee documents with placeholders
 * that can be replaced with actual employee data.
 *
 * @author Dev Agent
 */
class LetterTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'language',
        'content',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'language' => 'string',
    ];

    // protected static function newFactory(): LetterTemplateFactory
    // {
    //     // return LetterTemplateFactory::new();
    // }
}
