<?php

namespace App\Http\Controllers\V1\External;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Services\CustomerLoanEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * What CDP Core asks Credix before it pays an investment policy out.
 *
 *   GET /api/v1/external/core/policy-hold?policy_number=CDP-JFNM-00000138
 *   GET /api/v1/external/core/policy-hold?id_number=199231303062
 *   Header: X-Core-Key
 *
 * `held` is true when a LIVE Credix loan (anything but closed, cancelled,
 * rejected or declined) is secured by the policy. Core should block a
 * withdrawal, maturity payout, surrender or transfer while `held` is true.
 * Nothing is kept on Credix's side beyond the loan itself, so the answer is
 * always derived from the loan's current status.
 */
class CorePolicyHoldController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'policy_number' => ['nullable', 'string', 'max:100', 'required_without:id_number'],
            'id_number'     => ['nullable', 'string', 'max:50', 'required_without:policy_number'],
        ]);

        $query = LoanApplication::query()
            ->live()
            ->whereNotNull('collateral_policy_number')
            ->with(['application', 'customer:id,full_name,id_number']);

        if (!empty($data['policy_number'])) {
            $query->whereRaw(
                'UPPER(TRIM(collateral_policy_number)) = ?',
                [strtoupper(trim($data['policy_number']))]
            );
        }

        if (!empty($data['id_number'])) {
            // Same person under old (V/X) or new (12-digit) NIC.
            $variants = CustomerLoanEligibilityService::nicVariants($data['id_number']);
            $query->whereHas('customer', function ($c) use ($variants) {
                $c->whereIn(DB::raw("UPPER(REPLACE(REPLACE(TRIM(id_number), ' ', ''), '-', ''))"), $variants);
            });
        }

        $loans = $query->orderByDesc('id')->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'held'  => $loans->isNotEmpty(),
                'loans' => $loans->map(fn (LoanApplication $loan) => [
                    'policy_number' => $loan->collateral_policy_number,
                    'reference'     => $loan->reference(),
                    'status'        => $loan->status->value,
                    'amount'        => (float) ($loan->approved_amount ?? $loan->requested_amount),
                    'customer'      => [
                        'name'      => $loan->customer?->full_name,
                        'id_number' => $loan->customer?->id_number,
                    ],
                    'applied_at'    => $loan->applied_at?->toDateString(),
                ])->values(),
            ],
        ]);
    }
}
