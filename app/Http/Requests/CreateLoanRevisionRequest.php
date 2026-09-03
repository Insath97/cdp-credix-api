<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\LoanRevisionType;

class CreateLoanRevisionRequest extends FormRequest
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
            'loan_application_id' => 'required|integer|exists:loan_applications,id',
            'revision_type'       => ['required', Rule::enum(LoanRevisionType::class)],
            'reason'              => 'required|string|max:2000',
            'revised_term'        => 'required_unless:revision_type,principal_only|nullable|integer|min:1',
            'document'            => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ];
    }

}
