<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Services\LoanCustomerConfirmationService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

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
            new Middleware('permission:Loan Application Index', only: ['status']),
        ];
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
