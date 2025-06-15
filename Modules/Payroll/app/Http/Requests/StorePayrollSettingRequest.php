<?php

namespace Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StorePayrollSettingRequest
 *
 * Validation request for payroll weight settings.
 * Ensures that attendance and billable hours weights sum to exactly 100%.
 *
 * @author Dev Agent
 */
class StorePayrollSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attendance_weight' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'billable_hours_weight' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attendance_weight.required' => 'Attendance weight is required.',
            'attendance_weight.numeric' => 'Attendance weight must be a number.',
            'attendance_weight.min' => 'Attendance weight must be at least 0.',
            'attendance_weight.max' => 'Attendance weight must not exceed 100.',
            'billable_hours_weight.required' => 'Billable hours weight is required.',
            'billable_hours_weight.numeric' => 'Billable hours weight must be a number.',
            'billable_hours_weight.min' => 'Billable hours weight must be at least 0.',
            'billable_hours_weight.max' => 'Billable hours weight must not exceed 100.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Only check sum if both values are numeric
            if (is_numeric($this->input('attendance_weight')) && is_numeric($this->input('billable_hours_weight'))) {
                $attendanceWeight = (float) $this->input('attendance_weight');
                $billableHoursWeight = (float) $this->input('billable_hours_weight');

                if (abs(($attendanceWeight + $billableHoursWeight) - 100) >= 0.01) {
                    $validator->errors()->add('weight_sum', 'The sum of attendance weight and billable hours weight must equal exactly 100%.');
                }
            }
        });
    }
}
