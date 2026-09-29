<?php

namespace App\Http\Controllers\V1;

use App\Enums\LoanSecurityType;
use App\Exceptions\InvestmentCollateralException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Document;
use App\Models\LoanApplicationSecurity;
use App\Models\LoanProduct;
use App\Services\InvestmentCollateralService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * The Loan Security step of the loan wizard, for secured products
 * (loan_products.requires_security).
 *
 *   GET  /loan-securities/options             the security and property types
 *   POST /loan-securities/investment-lookup   NIC + policy number -> the investment, live from CDP Core
 *
 * The security itself is saved with the application (the `security` object
 * on POST / PUT /loan-applications); its papers go up through POST /documents
 * under the document type each security uses.
 */
class LoanSecurityController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(protected InvestmentCollateralService $investmentCollateral)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Index|Loan Application Create|Loan Application Update', only: ['options']),
            new Middleware('permission:Loan Application Create|Loan Application Update|CDP Customer Verification', only: ['investmentLookup']),
        ];
    }

    /**
     * The dropdowns of the Loan Security step, so the frontend never keeps
     * its own copy of the lists.
     */
    public function options()
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Loan security options retrieved successfully',
            'data'    => [
                'security_types' => array_map(fn (LoanSecurityType $type) => [
                    'value'             => $type->value,
                    'label'             => $type->label(),
                    'document_type'     => $type->documentType(),
                    'document_label'    => Document::TYPES[$type->documentType()],
                    'document_required' => $type->documentRequired(),
                ], LoanSecurityType::cases()),
                'property_types' => collect(LoanApplicationSecurity::PROPERTY_TYPES)
                    ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                    ->values(),
            ],
        ], 200);
    }

    /**
     * "Retrieve and display the relevant investment details" for the NIC and
     * policy number typed into the step. Nothing is stored; the same checks
     * run again, against Core, when the application is submitted.
     *
     * customer_id checks the NIC is the borrower's up front, loan_product_id
     * adds the product's max_loan, and loan_application_id stops an edit
     * seeing its own loan as the one holding the policy.
     */
    public function investmentLookup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nic'                 => 'required|string|max:20',
            'policy_number'       => 'required|string|max:100',
            'customer_id'         => 'nullable|integer|exists:customers,id',
            'loan_product_id'     => 'nullable|integer|exists:loan_products,id',
            'loan_application_id' => 'nullable|integer|exists:loan_applications,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->investmentCollateral->lookup(
                (string) $request->input('nic'),
                (string) $request->input('policy_number'),
                $request->filled('loan_product_id') ? LoanProduct::find($request->integer('loan_product_id')) : null,
                $request->filled('customer_id') ? Customer::find($request->integer('customer_id')) : null,
                $request->filled('loan_application_id') ? $request->integer('loan_application_id') : null
            );

            $this->logActivity('Index', 'LoanSecurity', 'CDP investment looked up for a loan security', [
                'user_id'       => Auth::id(),
                'policy_number' => $result['investment']['policy_number'],
                'customer_id'   => $request->input('customer_id'),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Investment retrieved successfully',
                'data'    => $result,
            ], 200);
        } catch (InvestmentCollateralException $e) {
            return $e->toResponse();
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve the investment',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
