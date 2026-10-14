<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\CustomerLoanEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;

/**
 * GET /api/v1/customers/loan-eligibility?customer_id=5
 * GET /api/v1/customers/loan-eligibility?nic=853400937V
 *   optional: &exclude_loan_application_id=12 (adding a member to an existing loan)
 *
 * Asked by the loan wizard when the officer clicks "Add Selected Customer",
 * so a customer who already holds a live loan is refused at that step. The
 * create endpoints still enforce the same rule on submit; this is only the
 * early warning.
 */
class CustomerLoanEligibilityController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Create|Group Loan Create|Loan Application Update|Group Loan Update', only: ['show']),
        ];
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'                 => 'required_without:nic|nullable|integer|exists:customers,id',
            'nic'                         => 'required_without:customer_id|nullable|string|max:100',
            'exclude_loan_application_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = CustomerLoanEligibilityService::check(
                $request->filled('customer_id') ? (int) $request->input('customer_id') : null,
                $request->input('nic'),
                $request->filled('exclude_loan_application_id') ? (int) $request->input('exclude_loan_application_id') : null
            );

            // 200 either way: "not eligible" is an answer, not a failure. The
            // wizard reads data.eligible and shows data.message when false.
            return response()->json([
                'status'  => 'success',
                'message' => $result['message'] ?? 'Customer can be added to a new loan.',
                'data'    => $result,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to check customer loan eligibility',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
