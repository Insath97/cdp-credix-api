<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffSendLoanConfirmationOtpRequest;
use App\Http\Requests\StaffVerifyLoanConfirmationOtpRequest;
use App\Models\LoanApplication;
use App\Services\LoanCustomerConfirmationService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Staff side of the customer's loan confirmation (OTP 2): send the review
 * link, and see where each customer stands. The customer side lives in
 * Public\LoanReviewController.
 */
class LoanCustomerConfirmationController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(
        protected LoanCustomerConfirmationService $confirmationService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Create|Loan Application Update', only: ['sendLink']),
            new Middleware('permission:Loan Application Index', only: ['status', 'preview']),
            new Middleware('permission:Loan Customer Confirmation Send OTP', only: ['sendOtp']),
            new Middleware('permission:Loan Customer Confirmation Verify OTP', only: ['verifyOtp']),
        ];
    }

    /**
     * Staff preview: what each customer sees on their link, where they stand,
     * their correction requests, and what this user may do now.
     */
    public function preview(string $id): JsonResponse
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $this->confirmationService->preview($loanApplication, Auth::user()),
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Staff-assisted: send OTP 2 to the customer's registered phone, with the
     * reason staff have to enter it.
     */
    public function sendOtp(StaffSendLoanConfirmationOtpRequest $request, string $id, string $customerId): JsonResponse
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            $result = $this->confirmationService->staffSendOtp(
                $loanApplication,
                (int) $customerId,
                $request->input('reason'),
                Auth::user()
            );

            $this->logActivity('UPDATE', 'LoanCustomerConfirmation', "Staff sent confirmation OTP for Loan Application #{$loanApplication->id}", [
                'loan_application_id' => $loanApplication->id,
                'customer_id'         => (int) $customerId,
                'reason'              => $result['staff_assist_reason'],
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'OTP sent to the customer\'s registered mobile.',
                'data'    => $result,
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Staff-assisted: enter the OTP the customer read out.
     */
    public function verifyOtp(StaffVerifyLoanConfirmationOtpRequest $request, string $id, string $customerId): JsonResponse
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            $result = $this->confirmationService->staffVerifyOtp(
                $loanApplication,
                (int) $customerId,
                $request->input('otp'),
                true,
                Auth::user(),
                $request->ip(),
                $request->userAgent()
            );

            $this->logActivity('UPDATE', 'LoanCustomerConfirmation', "Staff verified customer confirmation OTP for Loan Application #{$loanApplication->id}", [
                'loan_application_id' => $loanApplication->id,
                'customer_id'         => (int) $customerId,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'The customer\'s confirmation has been verified.',
                'data'    => $result,
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * 403/404/410 pass through; other messages meant for staff are 422.
     * Anything unexpected is logged and reported as a 500.
     */
    private function errorResponse(\Throwable $th): JsonResponse
    {
        if (get_class($th) !== \Exception::class) {
            Log::error('Loan customer confirmation failed: ' . $th->getMessage(), ['exception' => $th]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }

        return response()->json([
            'status'  => 'error',
            'message' => $th->getMessage(),
        ], in_array($th->getCode(), [403, 404, 410], true) ? $th->getCode() : 422);
    }

    /**
     * Send the review link by SMS to every customer who still has to confirm.
     */
    public function sendLink(string $id): JsonResponse
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            $result = $this->confirmationService->sendLinks($loanApplication, Auth::user());

            $this->logActivity('CREATE', 'LoanCustomerConfirmation', "Customer review link sent for Loan Application #{$loanApplication->id}", [
                'loan_application_id' => $loanApplication->id,
                'customers'           => collect($result['customers'])
                    ->map(fn ($c) => ['customer_id' => $c['customer_id'], 'link_sent' => $c['link_sent']])
                    ->all(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Review link sent to the customer by SMS.',
                'data'    => $result,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Each required customer's confirmation state for this loan application.
     */
    public function status(string $id): JsonResponse
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $this->confirmationService->status($loanApplication),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
