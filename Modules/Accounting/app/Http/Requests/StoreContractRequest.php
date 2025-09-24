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
            'contract_number' => 'required|string|max:255|unique:contracts,contract_number',
            'description' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0|max:99999999.99',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:draft,active,completed,cancelled',
            'contact_info' => 'nullable|array',
            'contact_info.email' => 'nullable|email',
            'contact_info.phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
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
            'end_date.after' => 'End date must be after start date.',
        ];
    }
}