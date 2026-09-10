<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateCustomerRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'customer_id'=>'nullable|string|max:255',
        'full_name'=>'required|string|max:500',
        'name_with_initials'=>'required|string|max:255',
        'customer_code' => 'nullable|string|max:255|unique:customers,customer_code',
        'id_type'=>'required',
        'id_number'=>'required|unique:customers,id_number',
        'address_line_1'=>'required',
        'address_line_2'=>'nullable',
        'landmark'=>'nullable|string|max:255',
        'city'=>'nullable|string|max:255',
        'state'=>'nullable|string|max:255',
        'country'=>'required|string|max:255',
        'postal_code'=>'nullable|string|max:255',
        'gn_division'=>'nullable|string|max:255',
        'ds_division'=>'nullable|string|max:255',
        'district'=>'nullable|string|max:255',
        'province'=>'nullable|string|max:255',
        'date_of_birth'=>'required|date',
        'phone_primary'=>'required|string|max:20',
        'phone_secondary'=>'nullable|string|max:20',
        'email'=>'nullable|email|max:255|unique:users,email',
        'have_whatsapp'=>'required|boolean',
        'whatsapp_number'=>'nullable|string|max:20',
        'preferred_language'=>'required|string|max:50',
        'employment_status'=>'nullable|string|max:50',
        'occupation'=>'nullable|string|max:255',
        'employer_name'=>'nullable|string|max:255',
        'employer_address_line1'=>'nullable',
        'employer_address_line2'=>'nullable',
        'employer_city'=>'nullable|string|max:255',
        'employer_state'=>'nullable|string|max:255',
        'employer_country'=>'nullable|string|max:255',
        'employer_postal_code'=>'nullable|string|max:255',
        'employer_phone'=>'nullable|string|max:20',
        'employer_email'=>'nullable|string|max:255',
        'business_name'=>'nullable|string|max:255',
        'business_registration_number'=>'nullable|string|max:255',
        'business_address_line1'=>'nullable|string|max:255',
        'business_address_line2'=>'nullable|string|max:255',
        'business_city'=>'nullable|string|max:255',
        'business_state'=>'nullable|string|max:255',
        'business_country'=>'nullable|string|max:255',
        'business_postal_code'=>'nullable|string|max:255',
        'business_phone'=>'nullable|string|max:20',
        'business_email'=>'nullable|string|max:255',
        'branch_id'=>'required|exists:branches,id',

        // The CDP employee who introduced this customer. The link is optional
        // so a recommender who is not on the employee register yet can still be
        // recorded; what the business needs on the file is the four details.
        'recommended_by_employee_id' => 'nullable|integer|exists:employees,id',
        'recommender_name'           => 'nullable|string|max:255',
        'recommender_employee_code'  => 'nullable|string|max:255',
        'recommender_nic'            => 'nullable|string|max:255',
        'recommender_phone'          => 'nullable|string|max:255',
        'is_active'=>'boolean',

        // Bank Details Validation
        'bank_details' => 'nullable|array',
        'bank_details.*.bank_name' => 'required_with:bank_details|string|max:255',
        'bank_details.*.branch_name' => 'nullable|string|max:255',
        'bank_details.*.account_number' => 'required_with:bank_details|string|max:255',
        'bank_details.*.payment_method' => 'nullable|string|max:255',
        'bank_details.*.is_active' => 'nullable|boolean',

        // Fixed Assets Validation
        'fixed_assets' => 'nullable|array',
        'fixed_assets.*.owner_name' => 'required_with:fixed_assets|string|max:255',
        'fixed_assets.*.property_location' => 'nullable|string|max:255',
        'fixed_assets.*.extent' => 'nullable|string|max:255',
        'fixed_assets.*.market_value' => 'nullable|numeric|min:0',
        'fixed_assets.*.is_mortaged' => 'nullable|boolean',
        'fixed_assets.*.is_active' => 'nullable|boolean',

        // Moving Assets Validation
        'moving_assets' => 'nullable|array',
        'moving_assets.*.assest_category' => 'required_with:moving_assets|string|max:255',
        'moving_assets.*.owner_name' => 'required_with:moving_assets|string|max:255',
        'moving_assets.*.no_of_shares' => 'nullable|integer|min:0',
        'moving_assets.*.par_value' => 'nullable|numeric|min:0',
        'moving_assets.*.registation_no' => 'nullable|string|max:255',
        'moving_assets.*.market_value' => 'nullable|numeric|min:0',
        'moving_assets.*.mortgage_lease_hire_status' => 'nullable|string|max:255',
        'moving_assets.*.is_active' => 'nullable|boolean',

        // Liabilities Validation
        'liabilities' => 'nullable|array',
        'liabilities.*.liability_type' => 'required_with:liabilities|string|in:bank_loan,leasing,credit_card,hire_purchase,other',
        'liabilities.*.institution_name' => 'required_with:liabilities|string|max:255',
        'liabilities.*.account_reference_no' => 'nullable|string|max:255',
        'liabilities.*.original_amount' => 'nullable|numeric|min:0',
        'liabilities.*.outstanding_balance' => 'required_with:liabilities|numeric|min:0',
        'liabilities.*.monthly_installment' => 'nullable|numeric|min:0',
        'liabilities.*.start_date' => 'nullable|date',
        'liabilities.*.end_date' => 'nullable|date',
        'liabilities.*.is_active' => 'nullable|boolean',

        // Guarantors Validation
        'guarantors' => 'nullable|array',
        'guarantors.*.full_name' => 'required_with:guarantors|string|max:255',
        'guarantors.*.type' => 'required_with:guarantors|string|in:guarantor_1,guarantor_2',
        'guarantors.*.id_type' => 'required_with:guarantors|string|max:100',
        'guarantors.*.id_number' => 'required_with:guarantors|string|max:100',
        'guarantors.*.date_of_birth' => 'nullable|date',
        'guarantors.*.phone_primary' => 'nullable|string|max:20',
        'guarantors.*.occupation' => 'nullable|string|max:255',
        'guarantors.*.employer_name' => 'nullable|string|max:255',
        'guarantors.*.date_joined' => 'nullable|date',
        'guarantors.*.salary' => 'nullable|numeric|min:0',
        'guarantors.*.allowance' => 'nullable|numeric|min:0',
        'guarantors.*.other_income' => 'nullable|numeric|min:0',
        'guarantors.*.liabilities' => 'nullable|numeric|min:0',
        'guarantors.*.bank_name_of_guarantor' => 'nullable|string|max:255',
        'guarantors.*.bank_account_no_of_guarantor' => 'nullable|string|max:255',
        'guarantors.*.bank_branch_of_guarantor' => 'nullable|string|max:255',

        // Documents collected for a guarantor. Same shape as the customer's own
        // documents block below, but they are written with the guarantor_id so
        // a guarantor's papers stay distinguishable from the borrower's.
        'guarantors.*.documents' => 'nullable|array',
        'guarantors.*.documents.*.document_name' => 'nullable|string|max:255',
        'guarantors.*.documents.*.document_type' => 'nullable|string|max:60',
        'guarantors.*.documents.*.remarks' => 'nullable|string|max:1000',
        'guarantors.*.documents.*.is_active' => 'nullable|boolean',
        'guarantors.*.documents.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        'guarantors.*.documents.*.file_path' => 'nullable|string|max:1000',

        // Documents Validation
        'documents' => 'nullable|array',
        'documents.*.document_name' => 'nullable|string|max:255',
        'documents.*.document_type' => 'required_with:documents|string|in:nic_copy,passport_copy,driving_license,salary_slip,bank_statement,billing_proof,salary_assignment_letter,employer_letter,photo,other',
        'documents.*.remarks' => 'nullable|string|max:1000',
        'documents.*.is_active' => 'nullable|boolean',
        'documents.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',

        // User Account Validation
        'create_user_account' => 'nullable|boolean',
        'user_username' => 'nullable|string|max:255|unique:users,username',
        'user_password' => 'nullable|string|min:6|max:255',

        // Loan Application Validation
        'loan_product_id' => 'nullable|integer|exists:loan_products,id',
        'requested_amount' => 'nullable|numeric|min:0',
        'interest_rate' => 'nullable|numeric|min:0',
        'interest_type' => 'nullable|string|in:flat,reducing',
        'term_months' => 'nullable|integer|min:1',
        'monthly_repayment_date' => 'nullable|integer|min:1|max:31',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errorMessages = $validator->errors();
        $fieldErrors = collect($errorMessages->getMessages())->map(function ($messages, $field) {
            return [
                'field' => $field,
                'messages' => $messages,
            ];
        })->values();

        $message = $fieldErrors->count() > 1
            ? 'There are multiple validation errors. Please review the form and correct the issues.'
            : 'There is an issue with the input for ' . $fieldErrors->first()['field'] . '.';

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => $fieldErrors,
        ], 422));
    }
}


