<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Update Employee Request
 *
 * Handles validation for updating existing employee records.
 * Salary fields are only validated and processed if user has permission.
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
        $rules = [
            'name' => ['required', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'name_ar' => ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'email' => [
                'required',
                'email',
                Rule::unique('employees', 'email')->ignore($this->route('employee')),
                'max:' . self::MAX_STRING_LENGTH,
            ],
            'personal_email' => ['nullable', 'email', 'max:' . self::MAX_STRING_LENGTH],
            'attendance_id' => ['nullable', 'string', 'max:50'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'national_insurance_number' => ['nullable', 'string', 'max:50'],
            'jira_account_id' => ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'jira_author_name' => ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'billable_hours_applicable' => ['nullable', 'boolean'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'team' => ['nullable', 'string', 'in:' . implode(',', array_keys(\Modules\HR\Models\Employee::TEAMS))],
            'start_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,resigned,terminated'],
            'termination_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'contact_info' => ['nullable', 'array'],
            'contact_info.mobile_number' => ['nullable', 'string', 'max:20'],
            'contact_info.secondary_number' => ['nullable', 'string', 'max:20'],
            'contact_info.current_address' => ['nullable', 'string', 'max:500'],
            'contact_info.permanent_address' => ['nullable', 'string', 'max:500'],
            'bank_info' => ['nullable', 'array'],
            'bank_info.bank_name' => ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'bank_info.account_number' => ['nullable', 'string', 'max:50'],
            'bank_info.account_id' => ['nullable', 'string', 'max:50'],
            'bank_info.iban' => ['nullable', 'string', 'max:50'],
            'emergency_contact' => ['nullable', 'array'],
            'emergency_contact.name' => ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'emergency_contact.phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH],
        ];

        // Only add salary validation rules if user has permission to edit financial info
        if (Gate::allows('edit-employee-financial')) {
            $rules['base_salary'] = ['nullable', 'numeric', 'min:' . self::MIN_SALARY];
            $rules['hourly_rate'] = ['nullable', 'numeric', 'min:' . self::MIN_SALARY];
            $rules['salary_change_reason'] = ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH];
        }

        return $rules;
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
            'position_id.exists' => 'The selected position does not exist.',
            'base_salary.numeric' => 'The base salary must be a valid number.',
            'base_salary.min' => 'The base salary cannot be negative.',
            'status.required' => 'The employment status is required.',
            'status.in' => 'The employment status must be active, resigned, or terminated.',
            'termination_date.after_or_equal' => 'The end of service date must be on or after the start date.',
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
        // Handle checkbox - if not present, set to false
        if (!$this->has('billable_hours_applicable')) {
            $this->merge(['billable_hours_applicable' => false]);
        }

        // Clean up contact_info, removing empty values
        if ($this->has('contact_info')) {
            $contactInfo = array_filter($this->contact_info ?: [], function ($value) {
                return ! empty($value);
            });
            $this->merge(['contact_info' => empty($contactInfo) ? null : $contactInfo]);
        }

        // Clean up bank_info, removing empty values
        if ($this->has('bank_info')) {
            $bankInfo = array_filter($this->bank_info ?: [], function ($value) {
                return ! empty($value);
            });
            $this->merge(['bank_info' => empty($bankInfo) ? null : $bankInfo]);
        }

        // Clean up emergency_contact, removing empty values
        if ($this->has('emergency_contact')) {
            $emergencyContact = array_filter($this->emergency_contact ?: [], function ($value) {
                return ! empty($value);
            });
            $this->merge(['emergency_contact' => empty($emergencyContact) ? null : $emergencyContact]);
        }

        // Remove salary fields if user doesn't have permission
        if (!Gate::allows('edit-employee-financial')) {
            $this->request->remove('base_salary');
            $this->request->remove('hourly_rate');
            $this->request->remove('salary_change_reason');
        }
    }
}
