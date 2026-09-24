<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * A CDP Core policy cannot secure this loan (not the customer's, not
 * approved, already backing another live loan, too small, matures too soon,
 * or Core could not be reached). Rendered in the FormRequest error shape so
 * the wizard shows it against the right field.
 */
class InvestmentCollateralException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $field = 'collateral_policy_number',
        public readonly int $status = 422
    ) {
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
        ], $this->status);
    }
}
