<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveCorrectionRequestRequest;
use App\Http\Requests\RejectCorrectionRequestRequest;
use App\Models\CustomerDetailCorrectionRequest;
use App\Models\User;
use App\Services\CustomerCorrectionService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Staff side of the customer's correction requests: view, take into review,
 * reject, or approve and apply. Applying also needs the edit permission of
 * the record being changed, which the service checks. The audit fields are
 * shown only to holders of Loan Customer Correction Audit.
 */
class CustomerCorrectionRequestController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    protected const AUDIT_FIELDS = ['old_value', 'applied_value', 'applied_changes'];

    public function __construct(
        protected CustomerCorrectionService $correctionService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Customer Correction Index', only: ['index', 'show']),
            new Middleware('permission:Loan Customer Correction Audit', only: ['history']),
            new Middleware('permission:Loan Customer Correction Review', only: ['inReview', 'reject', 'approve']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $requests = $this->correctionService->list(
                $request->only(['status', 'loan_application_id', 'customer_id', 'review_stage', 'per_page'])
            );

            if (!$this->canSeeAudit()) {
                $requests->getCollection()->each->makeHidden(self::AUDIT_FIELDS);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $requests,
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Applied corrections with their old and new values: the audit history.
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['loan_application_id', 'customer_id', 'review_stage', 'per_page']);
            $filters['status'] = CustomerDetailCorrectionRequest::STATUS_COMPLETED;

            return response()->json([
                'status' => 'success',
                'data'   => $this->correctionService->list($filters),
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $correction = CustomerDetailCorrectionRequest::find($id);

            if (!$correction) {
                return $this->notFound();
            }

            $data = $this->correctionService->show($correction, $this->staff());

            if (!$this->canSeeAudit()) {
                $data['correction_request']->makeHidden(self::AUDIT_FIELDS);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $data,
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    public function inReview(string $id): JsonResponse
    {
        try {
            $correction = CustomerDetailCorrectionRequest::find($id);

            if (!$correction) {
                return $this->notFound();
            }

            $correction = $this->correctionService->markInReview($correction, $this->staff());

            $this->logActivity('UPDATE', 'CustomerDetailCorrectionRequest', "Correction request #{$correction->id} taken into review", [
                'correction_request_id' => $correction->id,
                'loan_application_id'   => $correction->loan_application_id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Correction request taken into review.',
                'data'    => $correction->makeHidden(self::AUDIT_FIELDS),
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    public function reject(RejectCorrectionRequestRequest $request, string $id): JsonResponse
    {
        try {
            $correction = CustomerDetailCorrectionRequest::find($id);

            if (!$correction) {
                return $this->notFound();
            }

            $correction = $this->correctionService->reject($correction, $request->input('remarks'), $this->staff());

            $this->logActivity('UPDATE', 'CustomerDetailCorrectionRequest', "Correction request #{$correction->id} rejected", [
                'correction_request_id' => $correction->id,
                'loan_application_id'   => $correction->loan_application_id,
                'remarks'               => $correction->review_remarks,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Correction request rejected.',
                'data'    => $correction->makeHidden(self::AUDIT_FIELDS),
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Approve and apply the correction to its target record.
     */
    public function approve(ApproveCorrectionRequestRequest $request, string $id): JsonResponse
    {
        try {
            $correction = CustomerDetailCorrectionRequest::find($id);

            if (!$correction) {
                return $this->notFound();
            }

            $result = $this->correctionService->approve(
                $correction,
                $request->input('target'),
                $request->input('value'),
                $request->input('remarks'),
                $this->staff()
            );

            $applied = $result['correction_request'];

            $this->logActivity('UPDATE', 'CustomerDetailCorrectionRequest', "Correction request #{$applied->id} approved and applied to {$applied->applied_target}", [
                'correction_request_id' => $applied->id,
                'loan_application_id'   => $applied->loan_application_id,
                'target'                => $applied->applied_target,
                'kyc_reset'             => $result['kyc_reset'],
                'review_link_sent'      => $result['review_link']['sent'],
            ]);

            if (!$this->canSeeAudit()) {
                $applied->makeHidden(self::AUDIT_FIELDS);
            }

            $message = 'Correction applied. ' . $result['review_link']['message'];
            if ($result['kyc_reset']) {
                $message .= ' The customer\'s KYC was reset because their phone or NIC changed: verify KYC (OTP 1) again, then send the review link.';
            }

            return response()->json([
                'status'  => 'success',
                'message' => $message,
                'data'    => $result,
            ], 200);

        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * The signed-in staff member. Every route here sits behind auth:api.
     */
    protected function staff(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    protected function canSeeAudit(): bool
    {
        return $this->staff()->can('Loan Customer Correction Audit');
    }

    protected function notFound(): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'Correction request not found',
        ], 404);
    }

    /**
     * 403/404 pass through; other messages meant for staff are 422.
     * Anything unexpected is logged and reported as a 500.
     */
    private function errorResponse(\Throwable $th): JsonResponse
    {
        if (get_class($th) !== \Exception::class) {
            Log::error('Customer correction request failed: ' . $th->getMessage(), ['exception' => $th]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }

        return response()->json([
            'status'  => 'error',
            'message' => $th->getMessage(),
        ], in_array($th->getCode(), [403, 404], true) ? $th->getCode() : 422);
    }
}
