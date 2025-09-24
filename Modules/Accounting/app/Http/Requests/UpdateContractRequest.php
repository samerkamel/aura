<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name' => 'required|string|max:255',
            'contract_number' => 'required|string|max:255|unique:contracts,contract_number,' . $this->route('contract')->id,
            'description' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0|max:99999999.99',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:draft,active,completed,cancelled',
            'contact_info' => 'nullable|array',
            'notes' => 'nullable|string',
        ];
    }
}