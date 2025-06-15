<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Document Request
 *
 * Handles validation for uploading employee documents.
 * Validates file types, file size, and required fields.
 *
 * @author Dev Agent
 */
class StoreDocumentRequest extends FormRequest
{
    /**
     * File size limit in bytes (5MB)
     */
    private const MAX_FILE_SIZE = 5242880; // 5MB in bytes

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'max:255'],
            'document_file' => [
                'required',
                'file',
                'max:'.(self::MAX_FILE_SIZE / 1024), // Laravel expects KB
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,bmp,svg,txt,rtf,xls,xlsx,ppt,pptx',
            ],
            'issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:issue_date'],
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
            'document_type.required' => 'The document type is required.',
            'document_type.max' => 'The document type cannot exceed 255 characters.',
            'document_file.required' => 'Please select a file to upload.',
            'document_file.file' => 'The uploaded file is not valid.',
            'document_file.max' => 'The file size cannot exceed 5MB.',
            'document_file.mimes' => 'The file must be one of the following types: PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, BMP, SVG, TXT, RTF, XLS, XLSX, PPT, PPTX.',
            'issue_date.date' => 'Please enter a valid issue date.',
            'issue_date.before_or_equal' => 'The issue date cannot be in the future.',
            'expiry_date.date' => 'Please enter a valid expiry date.',
            'expiry_date.after' => 'The expiry date must be after the issue date.',
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
        // Clean up the document type
        if ($this->has('document_type')) {
            $this->merge([
                'document_type' => trim($this->document_type),
            ]);
        }
    }
}
