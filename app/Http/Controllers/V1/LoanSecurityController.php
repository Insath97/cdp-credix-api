<?php

namespace App\Http\Controllers\V1;

use App\Enums\LoanSecurityType;
use App\Exceptions\InvestmentCollateralException;
use App\Exceptions\SecurityPlanException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Document;
use App\Models\LoanApplicationSecurity;
use App\Services\InvestmentCollateralService;
use App\Services\LoanSecurityLtvService;
use App\Services\LoanSecurityPlanService;
use App\Traits\ActivityLogTrait;
use App\Traits\ValidatesLoanSecurity;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The Loan Security step of the loan wizard, for secured loans -- Standard
 * Borrowing under the General term (LoanProduct::requiresSecurity()).
 *
 *   GET  /loan-securities/options             the security types, the plans this
 *                                             officer may use on each, and the
 *                                             percentage each plan lends at
 *   POST /loan-securities/coverage            the securities typed so far -> each
 *                                             one's ceiling, and the running total
 *   POST /loan-securities/investment-lookup   NIC + policy number -> the investment, live from CDP Core
 *
 * Nothing here is stored. The securities themselves are saved with the
 * application (the `securities` array on POST / PUT /loan-applications); their
 * papers go up through POST /documents under the document type each security
 * uses.
 *
 * coverage is what the step reads on every change: it is the same arithmetic
 * the submission is refused on (LoanSecurityLtvService), so the ceiling the
 * officer was shown and the ceiling they are held to cannot disagree.
 */
class LoanSecurityController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(
        protected InvestmentCollateralService $investmentCollateral,
        protected LoanSecurityLtvService $loanSecurityLtv,
        protected LoanSecurityPlanService $loanSecurityPlans
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Index|Loan Application Create|Loan Application Update', only: ['options']),
            new Middleware('permission:Loan Application Index|Loan Application Create|Loan Application Update', only: ['coverage']),
            new Middleware('permission:Loan Application Create|Loan Application Update|CDP Customer Verification', only: ['investmentLookup']),
        ];
    }

    /**
     * The dropdowns of the Loan Security step, so the frontend never keeps its
     * own copy of the lists -- including what each lending plan is worth up to,
     * which is configured and changes without a deploy.
     *
     * Each type carries `plans`, and only the plans this officer holds the
     * permission for. The plan list is not filtered at the point of use as a
     * convenience: a plan the officer may not use is not offered, and sending
     * one anyway is refused at submit rather than quietly accepted.
     */
    public function options()
    {
        $types = array_map(function (LoanSecurityType $type) {
            $plans = array_values(array_filter(
                $this->loanSecurityPlans->plansFor($type),
                fn (array $plan) => $plan['allowed']
            ));

            return [
                'value'         => $type->value,
                'label'         => $type->label(),
                // Where this type's value is entered, and what it is called
                // there. null for a CDP Investment: its value is CDP Core's
                // to report, and a field for it would only invite the
                // frontend to invent the collateral.
                'value_field'   => $type->valueField(),
                'value_label'   => $type->valueLabel(),
                'plans'         => array_map(fn (array $plan) => [
                    'value'      => $plan['code'],
                    'label'      => $plan['label'],
                    'percentage' => LoanSecurityLtvService::formatPercentage($plan['percentage']),
                ], $plans),
                // The main paper, as before; `documents` lists them all.
                'document_type' => $type->documentType(),
                'document_label' => Document::TYPES[$type->documentType()],
                'document_required' => $type->documents()[$type->documentType()],
                'documents'     => collect($type->documents())
                    ->map(fn (bool $required, string $documentType) => [
                        'document_type'  => $documentType,
                        'document_label' => Document::TYPES[$documentType],
                        'required'       => $required,
                    ])
                    ->values(),
            ];
        }, LoanSecurityType::cases());

        return response()->json([
            'status'  => 'success',
            'message' => 'Loan security options retrieved successfully',
            'data'    => [
                'security_types' => $types,
                'property_types' => collect(LoanApplicationSecurity::PROPERTY_TYPES)
                    ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                    ->values(),
                'max_securities_per_application' => LoanApplicationSecurity::MAX_SECURITIES_PER_APPLICATION,
            ],
        ], 200);
    }

    /**
     * What the securities entered into the step are worth, and how much of the
     * loan they cover. Read on every change to a security, so the helper text
     * under each value box and the coverage summary at the foot of the step are
     * both this system's numbers rather than the frontend's arithmetic.
     *
     * Nothing is stored and nothing is verified -- a CDP Investment's value is
     * not looked up here. A policy typed into the step that is not real, or not
     * this borrower's, still has no value yet, so it contributes nothing until
     * investment-lookup has been called for it; the server-side check at submit
     * is the one that decides.
     *
     * requested_amount comes from the form because the coverage depends on it.
     * It is not trusted for anything: it only decides how much the securities
     * are measured against, and the submission is judged on the amount the
     * request itself carries.
     *
     * The plan is required on every security here as it is at submit. Without
     * one there is no percentage to apply, and returning a ceiling for a
     * security whose terms nobody chose would be the number the officer
     * designs the loan around.
     */
    public function coverage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'requested_amount'  => ['required', 'numeric', 'min:0'],
            'securities'        => ['required', 'array', 'max:' . LoanApplicationSecurity::MAX_SECURITIES_PER_APPLICATION],
            'securities.*'      => ['array'],
            'securities.*.security_type' => ['required', Rule::enum(LoanSecurityType::class)],
            'securities.*.security_plan' => ['required', 'string', 'max:50'],
            // Only the value columns are read here, and only for the type they
            // belong to; the full per-type field rules belong to the create and
            // update requests, where they are enforced before anything is saved.
            'securities.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'securities.*.vehicle_value'   => ['nullable', 'numeric', 'min:0'],
            'securities.*.investment_details' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $securities = $request->input('securities', []);

        try {
            // Each security is checked against this officer, not merely
            // resolved. A plan that exists but is not permitted is refused
            // here too, so the step cannot be used to price a loan on terms
            // this officer would not be allowed to submit.
            $securities = $this->loanSecurityPlans->assertAllUsable($securities);
        } catch (SecurityPlanException $e) {
            return $e->toResponse();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Security coverage calculated successfully',
            'data'    => [
                'securities' => $this->loanSecurityLtv->breakdown($securities),
                'coverage'   => $this->loanSecurityLtv->coverage(
                    (float) $request->input('requested_amount'),
                    $securities
                ),
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
     * holding the policy. max_loan comes from the type's default plan, the one
     * that applies before an officer has chosen -- this is a look-up, not a
     * pledge, and the percentage it reports is not the one the loan will be
     * written under.
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
