<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\InvestmentCollateralException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoanProduct;
use App\Services\InvestmentCollateralService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * The loan wizard's investment picker.
 *
 *   GET /customers/{id}/investments?loan_product_id=7
 *
 * Lists the customer's approved CDP Core policies, live from Core, with the
 * most the product would lend against each and whether another live Credix
 * loan already holds it. Nothing is stored; the officer picks one and the
 * policy number goes up with the application as collateral_policy_number.
 */
class CustomerInvestmentController extends Controller implements HasMiddleware
{
    public function __construct(protected InvestmentCollateralService $collateral)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Create|CDP Customer Verification', only: ['index']),
        ];
    }

    public function index(Request $request, string $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['status' => 'error', 'message' => 'Customer not found'], 404);
        }

        if (trim((string) $customer->id_number) === '') {
            return response()->json([
                'status'  => 'error',
                'message' => 'This customer has no ID number on record, so their CDP Core investments cannot be looked up.',
            ], 422);
        }

        $product = $request->filled('loan_product_id')
            ? LoanProduct::find($request->integer('loan_product_id'))
            : null;

        try {
            $data = $this->collateral->investmentsFor($customer, $product);

            return response()->json([
                'status'  => 'success',
                'message' => 'Investments retrieved successfully',
                'data'    => $data + [
                    'requires_investment_collateral' => (bool) $product?->requires_investment_collateral,
                    'max_loan_percentage'            => $product?->max_loan_percentage,
                ],
            ], 200);
        } catch (InvestmentCollateralException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->status);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve the customer investments',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
