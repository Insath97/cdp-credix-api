<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Thrown when a loan application is attempted for a customer whose KYC details
 * have not yet been confirmed and verified with OTP.
 */
class CustomerKycNotVerifiedException extends Exception
{
    public function __construct(string $message, public readonly string $field = 'customer_id')
    {
        parent::__construct($message);
    }

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
