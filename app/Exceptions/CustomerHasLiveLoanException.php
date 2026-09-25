<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Thrown from inside a create transaction when, after the customer rows are
 * locked, one of the borrowers turns out to already hold a live loan.
 *
 * The FormRequest checks catch almost every case with a friendly 422 before
 * any work starts. This is the second, race-proof check: two officers (or one
 * double-click) submitting for the same customer at the same moment both pass
 * the FormRequest, and only the lock decides which one wins.
 */
class CustomerHasLiveLoanException extends Exception
{
    public function __construct(string $message, public readonly string $field = 'customer_id')
    {
        parent::__construct($message);
    }

    /**
     * The same shape FormRequest::failedValidation() returns, so the frontend
     * shows it against the field whichever check caught it.
     */
    public function toResponse(): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $this->getMessage(),
            'errors'  => [
                ['field' => $this->field, 'messages' => [$this->getMessage()]],
            ],
        ], 422);
    }
}
