<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * The security cannot be pledged under the plan that was asked for: no plan was
 * chosen, the plan is not one this security type offers, or the person making
 * the application does not hold the plan's permission. See
 * LoanSecurityPlanService.
 *
 * Rendered in the FormRequest error shape so the wizard shows it against the
 * security card that has to change, with the plans that were actually on offer.
 */
class SecurityPlanException extends Exception
{
    /**
     * @param string $message    what to tell the officer
     * @param string $field      the security_plan field to point at
     * @param int    $status     403 for a permission refusal, 422 for a bad plan
     * @param array<int, array<string, mixed>> $plans what the type offers, each
     *                                                 with an `allowed` flag
     */
    public function __construct(
        string $message,
        public readonly string $field = 'security_plan',
        public readonly int $status = 422,
        public readonly array $plans = []
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
            'security_plans' => $this->plans,
        ], $this->status);
    }
}