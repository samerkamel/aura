<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Employee Request
 *
 * Handles validation for updating existing employee records.
 * Validates required fields, email format, uniqueness (except current record), and salary constraints.
 *
 * @author Dev Agent
 */
class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Validation constants
     */
    private const MAX_STRING_LENGTH = 255;

    private const MIN_SALARY = 0;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'email' => [
                'required',
                'email',
                Rule::unique('employees', 'email')->ignore($this->route('employee')),
                'max:' . self::MAX_STRING_LENGTH,
            ],
            'position' => ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'start_date' => ['nullable', 'date'],
            'base_salary' => ['required', 'numeric', 'min:' . self::MIN_SALARY],
            'salary_change_reason' => ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'contact_info' => ['nullable', 'array'],
            'contact_info.phone' => ['nullable', 'string', 'max:20'],
            'contact_info.address' => ['nullable', 'string', 'max:500'],
            'bank_info' => ['nullable', 'array'],
            'bank_info.bank_name' => ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'bank_info.account_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The employee name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'base_salary.required' => 'The base salary is required.',
            'base_salary.numeric' => 'The base salary must be a valid number.',
            'base_salary.min' => 'The base salary cannot be negative.',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up contact_info and bank_info arrays, removing empty values
        if ($this->has('contact_info')) {
            $contactInfo = array_filter($this->contact_info ?: [], function ($value) {
                return ! empty($value);
            });
            $this->merge(['contact_info' => empty($contactInfo) ? null : $contactInfo]);
        }

        if ($this->has('bank_info')) {
            $bankInfo = array_filter($this->bank_info ?: [], function ($value) {
                return ! empty($value);
            });
            $this->merge(['bank_info' => empty($bankInfo) ? null : $bankInfo]);
        }
    }
}
