<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add proper authorization logic later
    }

    public function rules(): array
    {
        $rules = [
            'expense_type_category' => 'required|exists:expense_types,id',
            'category_id' => 'required|exists:expense_categories,id',
            'subcategory_id' => 'nullable|exists:expense_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0|max:999999.99',
            'expense_type' => 'required|in:recurring,one_time',
            'business_unit_id' => 'nullable|exists:business_units,id',

            // Project linking fields
            'project_id' => 'nullable|exists:projects,id',
            'auto_sync_to_project' => 'boolean',

            // Payment fields
            'mark_as_paid' => 'boolean',
            'paid_from_account_id' => 'required_if:mark_as_paid,1|nullable|exists:accounts,id',
            'paid_date' => 'required_if:mark_as_paid,1|nullable|date',
            'paid_amount' => 'nullable|numeric|min:0|max:999999.99',
            'payment_notes' => 'nullable|string|max:1000',
        ];

        // Add conditional rules based on expense type
        if ($this->input('expense_type') === 'recurring') {
            $rules = array_merge($rules, [
                'frequency_type' => 'required|in:weekly,bi-weekly,monthly,quarterly,yearly',
                'frequency_value' => 'required|integer|min:1|max:100',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
            ]);
        } else {
            $rules = array_merge($rules, [
                'expense_date' => 'required|date',
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'expense_type_category.required' => 'Please select an expense type.',
            'expense_type_category.exists' => 'The selected expense type does not exist.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category does not exist.',
            'amount.required' => 'Please enter an amount.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount cannot be negative.',
            'end_date.after' => 'End date must be after start date.',
        ];
    }
}