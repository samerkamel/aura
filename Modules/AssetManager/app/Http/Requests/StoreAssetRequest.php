<?php

namespace Modules\AssetManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Asset Request
 *
 * Handles validation for creating a new asset in the asset management system.
 * Validates required fields, uniqueness constraints, and data types.
 *
 * @author Dev Agent
 */
class StoreAssetRequest extends FormRequest
{
    /**
     * Validation constants
     */
    private const MAX_STRING_LENGTH = 255;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'type' => ['required', 'string', 'max:' . self::MAX_STRING_LENGTH],
            'serial_number' => ['nullable', 'string', 'max:' . self::MAX_STRING_LENGTH, 'unique:assets,serial_number'],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:available,assigned,maintenance,retired'],
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
            'name.required' => 'The asset name is required.',
            'name.max' => 'The asset name cannot exceed ' . self::MAX_STRING_LENGTH . ' characters.',
            'type.required' => 'The asset type is required.',
            'type.max' => 'The asset type cannot exceed ' . self::MAX_STRING_LENGTH . ' characters.',
            'serial_number.unique' => 'This serial number is already registered for another asset.',
            'serial_number.max' => 'The serial number cannot exceed ' . self::MAX_STRING_LENGTH . ' characters.',
            'purchase_date.date' => 'Please enter a valid purchase date.',
            'purchase_date.before_or_equal' => 'The purchase date cannot be in the future.',
            'purchase_price.numeric' => 'The purchase price must be a valid number.',
            'purchase_price.min' => 'The purchase price cannot be negative.',
            'description.max' => 'The description cannot exceed 1000 characters.',
            'status.required' => 'The asset status is required.',
            'status.in' => 'Please select a valid status: Available, Assigned, Maintenance, or Retired.',
        ];
    }
}
