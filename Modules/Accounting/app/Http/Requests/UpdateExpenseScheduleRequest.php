<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add proper authorization logic later
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:expense_categories,id',
            'subcategory_id' => 'nullable|exists:expense_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0|max:999999.99',
            'frequency_type' => 'required|in:weekly,bi-weekly,monthly,quarterly,yearly',
            'frequency_value' => 'required|integer|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'skip_weekends' => 'boolean',
            'excluded_dates' => 'nullable|array',
            'excluded_dates.*' => 'date',
            // Project linking fields
            'project_id' => 'nullable|exists:projects,id',
            'sync_to_project' => 'boolean',
            // Payment fields (for paid expenses)
            'paid_from_account_id' => 'nullable|exists:accounts,id',
            'paid_date' => 'nullable|date',
            'paid_amount' => 'nullable|numeric|min:0|max:999999.99',
            'payment_notes' => 'nullable|string|max:500',
        ];
    }
}