<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * The loan asked for is more than the securities pledged against it are worth
 * at their configured percentages -- see LoanSecurityLtvService. Refused on
 * submit and re-checked before review and verification, so the requested
 * amount, the valuations and the percentages cannot drift apart behind each
 * other.
 *
 * Rendered in the FormRequest error shape so the wizard shows it against the
 * field that caused it, with the per-security breakdown so the frontend can
 * point at the card that has to change.
 */
class SecurityLimitExceededException extends Exception
{
    /**
     * @param array<int, array<string, mixed>> $breakdown what each security contributes
     * @param array<string, mixed>              $totals    the sums: total_value, total_max_loan_amount
     */
    public function __construct(
        string $message,
        public readonly array $breakdown = [],
        public readonly array $totals = [],
        public readonly string $field = 'requested_amount',
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
            'security_limit' => [
                'breakdown' => $this->breakdown,
                'totals'    => $this->totals,
            ],
        ], $this->status);
    }
}
