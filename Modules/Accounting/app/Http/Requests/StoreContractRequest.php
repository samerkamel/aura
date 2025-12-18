<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add proper authorization logic later
    }

    public function rules(): array
    {
        return [
            'client_name' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'contract_number' => 'required|string|max:255|unique:contracts,contract_number',
            'description' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0|max:99999999.99',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:draft,active,completed,cancelled',
            'contact_info' => 'nullable|array',
            'contact_info.email' => 'nullable|email',
            'contact_info.phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
        ];
    }

    public function messages(): array
    {
        return [
            'client_name.required' => 'Please enter the client name.',
            'contract_number.required' => 'Please enter a contract number.',
            'contract_number.unique' => 'This contract number already exists.',
            'total_amount.required' => 'Please enter the total contract amount.',
            'total_amount.numeric' => 'Amount must be a valid number.',
            'start_date.required' => 'Please enter the contract start date.',
            'end_date.required' => 'Please enter the contract end date.',
            'end_date.after' => 'End date must be after start date.',
        ];
    }
}