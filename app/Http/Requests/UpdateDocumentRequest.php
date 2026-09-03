<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    use FriendlyValidationErrors;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_id'   => 'nullable|integer|exists:customers,id',
            'document_type' => 'nullable|string|in:nic_copy,passport_copy,driving_license,salary_slip,bank_statement,billing_proof,salary_assignment_letter,employer_letter,photo,other',
            'is_mandatory'  => 'nullable|boolean',
            'document_name' => 'required|string|max:255',
            'file'          => 'required_without:file_path|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'file_path'     => 'required_without:file|string|max:1000',
            'remarks'       => 'nullable|string',
            'status'        => 'nullable|string|in:active,rejected,expired',
            'is_active'     => 'nullable|boolean',
        ];
    }

}
