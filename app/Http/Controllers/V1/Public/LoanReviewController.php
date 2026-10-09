<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmLoanReviewRequest;
use App\Services\LoanCustomerConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * The customer's review page, reached from the SMS link. No login: the link
 * token is the credential, and every endpoint is throttled. The staff side
 * lives in LoanCustomerConfirmationController.
 */
class LoanReviewController extends Controller
{
    public function __construct(
        protected LoanCustomerConfirmationService $confirmationService
    ) {
    }

    /**
     * The KYC and loan details the customer is asked to confirm.
     */
    public function show(string $token): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data'   => $this->confirmationService->showByToken($token),
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Send OTP 2 to the customer's mobile.
     */
    public function requestOtp(string $token): JsonResponse
    {
        try {
            return response()->json([
                'status'  => 'success',
                'message' => 'OTP sent to your mobile.',
                'data'    => $this->confirmationService->requestOtp($token),
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Verify OTP 2 and record the customer's confirmation.
     */
    public function confirm(ConfirmLoanReviewRequest $request, string $token): JsonResponse
    {
        try {
            $result = $this->confirmationService->confirm(
                $token,
                $request->input('otp'),
                true,
                $request->ip(),
                $request->userAgent()
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Thank you. Your loan application details have been confirmed.',
                'data'    => $result,
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * 404/410 for a bad or dead link, 422 for anything the customer can fix.
     * The service raises plain Exceptions for messages meant for the customer;
     * anything else (a query error, say) is logged and never shown publicly.
     */
    private function errorResponse(\Throwable $th): JsonResponse
    {
        if (get_class($th) !== \Exception::class) {
            Log::error('Public loan review failed: ' . $th->getMessage(), ['exception' => $th]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong. Please try again or contact your branch.',
            ], 500);
        }

        $code = in_array($th->getCode(), [404, 410], true) ? $th->getCode() : 422;

        return response()->json([
            'status'  => 'error',
            'message' => $th->getMessage(),
        ], $code);
    }
}
