<?php

namespace App\Http\Requests;

use App\Models\LoanProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLoanProductRequest extends FormRequest
{
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
        $id = $this->route('loan_product');

        return [
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50|unique:loan_products,code,' . $id,
            'loan_type_id' => 'nullable|integer|exists:loan_types,id',
            'loan_term_id' => 'nullable|integer|exists:loan_terms,id',
            'description' => 'nullable|string',
            'interest_rate' => 'nullable|numeric|min:0|max:999.999',
            'interest_type' => 'nullable|string|in:flat,reducing',
            // The band is checked in withValidator() below rather than with
            // gte:min_amount here. Every field in this request is nullable, so
            // this is a partial update, and gte resolves its other operand out
            // of the payload alone: on a body carrying only max_amount there is
            // no min_amount to compare against, and the rule then fails for
            // every value, however large. Raising a product's ceiling is the
            // commonest edit there is, and it was impossible.
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'min_term_months' => 'nullable|integer|min:1',
            'max_term_months' => 'nullable|integer|min:1',
            'processing_fee_type' => 'nullable|string|in:fixed,percentage',
            'processing_fee_value' => 'nullable|numeric|min:0',
            'penalty_value' => 'nullable|numeric|min:0',
            'grace_period_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_islamic' => 'nullable|boolean',
            'is_group_loan' => 'nullable|boolean',
        ];
    }


    /**
     * Check the amount and term bands against the product as it will stand
     * after this update, not against the payload alone.
     *
     * A partial update carries one half of a pair; the other half is whatever
     * is already stored. Comparing the two halves of the *merged* state is the
     * only way to both allow a lone `max_amount` edit and still refuse one that
     * would drop the ceiling below the existing floor.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $product = LoanProduct::find($this->route('loan_product'));

            if (! $product) {
                return;
            }

            $bands = [
                'max_amount'      => ['min' => 'min_amount', 'label' => 'max amount', 'floor' => 'min amount'],
                'max_term_months' => ['min' => 'min_term_months', 'label' => 'max term months', 'floor' => 'min term months'],
            ];

            foreach ($bands as $maxField => $band) {
                $max = $this->input($maxField, $product->{$maxField});
                $min = $this->input($band['min'], $product->{$band['min']});

                if ($max === null || $min === null) {
                    continue;
                }

                if ((float) $max < (float) $min) {
                    $validator->errors()->add(
                        $maxField,
                        "The {$band['label']} must be greater than or equal to {$band['floor']}.",
                    );
                }
            }
        });
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
