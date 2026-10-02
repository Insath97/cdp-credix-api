<?php

namespace App\Http\Controllers\V1;

use App\Enums\LoanSecurityType;
use App\Exceptions\InvestmentCollateralException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Document;
use App\Models\LoanApplicationSecurity;
use App\Services\InvestmentCollateralService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * The Loan Security step of the loan wizard, for secured loans -- Standard
 * Borrowing under the General term (LoanProduct::requiresSecurity()).
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
                    // The main paper, as before; `documents` lists them all.
                    'document_type'     => $type->documentType(),
                    'document_label'    => Document::TYPES[$type->documentType()],
                    'document_required' => $type->documents()[$type->documentType()],
                    'documents'         => collect($type->documents())
                        ->map(fn (bool $required, string $documentType) => [
                            'document_type'  => $documentType,
                            'document_label' => Document::TYPES[$documentType],
                            'required'       => $required,
                        ])
                        ->values(),
                ], LoanSecurityType::cases()),
                'property_types' => collect(LoanApplicationSecurity::PROPERTY_TYPES)
                    ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                    ->values(),
                // System Setting: the most a CDP Investment can secure, as a % of its value.
                'cdp_investment_max_loan_percentage' => InvestmentCollateralService::maxLoanPercentage(),
            ],
        ], 200);
    }

    /**
     * "Retrieve and display the relevant investment details" for the NIC and
     * policy number typed into the step. Nothing is stored; the same checks
     * run again, against Core, when the application is submitted.
     *
     * customer_id / customer_ids (the borrower, or every borrower on a joint
     * loan) check up front that the NIC belongs to one of them, and
     * loan_application_id stops an edit seeing its own loan as the one
     * holding the policy. max_loan comes from the System Setting.
     */
    public function investmentLookup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nic'                 => 'required|string|max:20',
            'policy_number'       => 'required|string|max:100',
            'customer_id'         => 'nullable|integer|exists:customers,id',
            'customer_ids'        => 'nullable|array',
            'customer_ids.*'      => 'integer|distinct|exists:customers,id',
            'loan_application_id' => 'nullable|integer|exists:loan_applications,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $borrowerIds = collect([$request->input('customer_id')])
            ->merge((array) $request->input('customer_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        try {
            $result = $this->investmentCollateral->lookup(
                (string) $request->input('nic'),
                (string) $request->input('policy_number'),
                $borrowerIds->isEmpty() ? [] : Customer::whereIn('id', $borrowerIds)->get(),
                $request->filled('loan_application_id') ? $request->integer('loan_application_id') : null
            );

            $this->logActivity('Index', 'LoanSecurity', 'CDP investment looked up for a loan security', [
                'user_id'       => Auth::id(),
                'policy_number' => $result['investment']['policy_number'],
                'customer_ids'  => $borrowerIds->all(),
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
