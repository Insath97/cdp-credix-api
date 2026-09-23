<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CdpCustomerVerificationRequest;
use App\Services\CdpConnectService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;


class CdpCustomerVerificationController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(
        protected CdpConnectService $cdpService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:CDP Customer Verification', only: ['checkCustomer']),
        ];
    }

    /**
     * Check customer identity and investments in CDP Connect.
     */
    public function checkCustomer(CdpCustomerVerificationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $result = $this->cdpService->getCustomerInvestments(
                $data['id_number'],
                $data['id_type'] ?? null,
                $data['status'] ?? null
            );

            $this->logActivity('Index', 'CdpCustomerVerification', 'CDP Connect customer verification performed', [
                'user_id'     => Auth::id(),
                'id_number'   => $data['id_number'],
                'id_type'     => $data['id_type'] ?? null,
                'success'     => $result['success'],
                'status_code' => $result['status_code'],
            ]);

            if (!$result['success']) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $result['message'],
                ], $result['status_code']);
            }

           $payload = $result['data'] ?? [];

            return response()->json([
                'status'  => 'success',
                'message' => 'CDP customer verified successfully',
                'data'    => [
                    'customer'    => $payload['customer'] ?? null,
                    'summary'     => $payload['summary'] ?? null,
                    'investments' => $payload['investments'] ?? [],
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to verify the customer with CDP Connect',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
