<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Application;
use App\Models\Branch;
use App\Models\LoanApplication;
use App\Models\LoanApplicationCustomer;
use App\Models\LoanApplicationStatusHistory;
use App\Models\LoanProduct;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use App\Http\Requests\CreateLoanApplicationRequest;
use App\Http\Requests\UpdateLoanApplicationRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\CustomerHasLiveLoanException;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Exceptions\InvestmentCollateralException;
use App\Services\CustomerLoanEligibilityService;
use App\Services\InvestmentCollateralService;
use App\Services\LoanApplicationWorkflowService;
use App\Services\LoanDocumentService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class LoanApplicationController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, ScopesToUserBranch;

    public function __construct(
        protected LoanApplicationWorkflowService $workflowService,
        protected NotificationService $notificationService,
        protected LoanDocumentService $loanDocumentService,
        protected InvestmentCollateralService $investmentCollateral,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Index',  only: ['index', 'show']),
            new Middleware('permission:Loan Application Create', only: ['store']),
            new Middleware('permission:Loan Application Update', only: ['update']),
            new Middleware('permission:Loan Application Toggle Status', only: ['toggleStatus', 'activate', 'deactivate']),
            new Middleware('permission:Loan Application Delete', only: ['destroy']),
            new Middleware('permission:Loan Application Review', only: ['review', 'reviewFail']),
            new Middleware('permission:Loan Application Resubmit', only: ['resubmit']),
            new Middleware('permission:Loan Application Verify', only: ['verify', 'verifyFail']),
            new Middleware('permission:Loan Application Reverify', only: ['reverify']),
            new Middleware('permission:Loan Application Approve', only: ['approve']),
            new Middleware('permission:Loan Application Reject', only: ['reject']),
            new Middleware('permission:Loan Application Reopen', only: ['reopen']),
            new Middleware('permission:Loan Application Offer Response', only: ['holdOffer', 'acceptOffer', 'declineOffer']),
            new Middleware('permission:Loan Application Disburse', only: ['disburse']),
            new Middleware('permission:Loan Application Cancel', only: ['cancel']),
        ];
    }

    /**
     * Refuse an individual-loan action on a Group Loan's application.
     *
     * A group loan is one loan application like any other, so its id resolves
     * here — but its money must be driven through GroupLoanWorkflowService.
     * Approving it here would run the interest formula against a null
     * interest_rate (group loans have none, only a service charge) and quietly
     * produce a zero charge, bypassing the group's own math entirely.
     *
     * Returns a 422 response when the action must be refused, or null when the
     * application is an ordinary Individual or Joint Loan.
     */
    private function groupLoanGuardResponse(LoanApplication $loanApplication)
    {
        if (!$loanApplication->isGroupLoan()) {
            return null;
        }

        $groupLoan = $loanApplication->groupLoan;
        $reference = $groupLoan?->group_loan_no ?? "ID {$loanApplication->group_loan_id}";

        return response()->json([
            'status'  => 'error',
            'message' => "This loan application belongs to group loan {$reference}. Manage it through the group loan endpoints (/group-loans) so the group's service charge and member list stay consistent.",
            'errors'  => [
                'group_loan_id' => $loanApplication->group_loan_id,
            ],
        ], 422);
    }

    /**
     * Refuse to flag a finished loan application active again.
     *
     * status and is_active are separate columns, and is_active is what the
     * listings filter on and what the frontend badge reads. Without this a
     * cancelled, rejected or closed loan could be flipped back to active and
     * would then display as "Active" despite its workflow being over — and
     * LoanApplicationStatus has no transition out of those states, so it could
     * never legitimately become active again.
     *
     * Returns a 422 response when the action must be refused, or null when the
     * application is still live.
     */
    private function terminalStatusResponse(LoanApplication $loanApplication)
    {
        if (!$loanApplication->status->isTerminal()) {
            return null;
        }

        return response()->json([
            'status'  => 'error',
            'message' => "This loan application is {$loanApplication->status->value} and cannot be reactivated.",
            'errors'  => [
                'status' => $loanApplication->status->value,
            ],
        ], 422);
    }

    /**
     * Display a listing of loan applications.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = LoanApplication::with([
                'application',
                'customer:'.Customer::SUMMARY_COLUMNS,
                'loanApplicationCustomers.customer' => fn ($q) => $q
                    ->select(explode(',', Customer::SUMMARY_COLUMNS))
                    ->with('customerDetail'),
                'loanProduct.loanType',
                'loanProduct.loanTerm',
                'branch',
                'appliedByUser:'.User::SUMMARY_COLUMNS,
                'reviewedByUser:'.User::SUMMARY_COLUMNS,
                'approvedByUser:'.User::SUMMARY_COLUMNS,
                'groupLoan.loanProduct',
                'groupLoan.branch',
                'groupLoan.items',
            ]);

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('interest_type')) {
                $query->where('interest_type', $request->interest_type);
            }

            if ($request->filled('status')) {
                $statuses = array_filter(array_map('trim', explode(',', (string) $request->status)));
                $query->whereIn('status', $statuses);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $this->scopeToUserBranch($query);

            $loanApplications = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanApplication', 'Loan applications index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'interest_type', 'is_active']),
                'count'   => $loanApplications->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan applications retrieved successfully',
                'data'    => $loanApplications,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan applications',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created loan application.
     */
    public function store(CreateLoanApplicationRequest $request)
    {
        try {
            $data = $request->validated();

            $data = Employee::mergeRecommenderSnapshot($data);

            // Investment-backed product: the pledged CDP Core policy is checked
            // against Core (ownership, approval, LTV, maturity) before anything
            // is written. Outside the transaction because it is an HTTP call;
            // the one-live-loan-per-policy rule is re-checked under the lock
            // below. No-op for a product that takes no collateral.
            $product = LoanProduct::findOrFail($data['loan_product_id']);
            $this->investmentCollateral->validate(
                $product,
                Customer::findOrFail($data['customer_id']),
                $data['collateral_policy_number'] ?? null,
                (float) $data['requested_amount'],
                (int) $data['term_months'],
                !empty($data['applied_at']) ? \Carbon\Carbon::parse($data['applied_at']) : now()
            );
            if (!$product->requires_investment_collateral) {
                $data['collateral_policy_number'] = null;
            }

            $loanApplication = DB::transaction(function () use (&$data) {
                $borrowers = ['customer_id' => $data['customer_id']];
                foreach ($data['joint_customer_ids'] ?? [] as $i => $jointId) {
                    $borrowers["joint_customer_ids.{$i}"] = $jointId;
                }
                CustomerLoanEligibilityService::assertEligible($borrowers);

                if (!empty($data['collateral_policy_number'])) {
                    // Two submissions for the same policy both passed the
                    // HTTP-time check above; only one may get through here.
                    $holder = LoanApplication::holdingPolicy($data['collateral_policy_number'])
                        ->lockForUpdate()
                        ->with('application')
                        ->first();
                    if ($holder) {
                        throw new InvestmentCollateralException(
                            "Policy {$data['collateral_policy_number']} was pledged against loan {$holder->reference()} a moment ago."
                        );
                    }
                }

                if (empty($data['application_id'])) {
                    $branchName = !empty($data['branch_id'])
                        ? Branch::find($data['branch_id'])?->name
                        : null;

                    $application = Application::create([
                        'application_type' => 'loan',
                        'branch' => $branchName,
                        'requested_amount' => $data['requested_amount'],
                        'repayment_period_months' => $data['term_months'] ?? null,
                        'monthly_repayment_date' => $data['monthly_repayment_date'] ?? null,
                    ]);

                    $data['application_id'] = $application->id;
                }

                if (empty($data['applied_by'])) {
                    $data['applied_by'] = Auth::id();
                }
                if (empty($data['applied_at'])) {
                    $data['applied_at'] = now();
                }
                $data['status'] = LoanApplicationStatus::Submitted;

                $loanApplication = LoanApplication::create($data);

                LoanApplicationStatusHistory::record(
                    $loanApplication,
                    LoanApplicationStatus::Submitted,
                    Auth::id(),
                    'Loan application submitted'
                );

                if (!empty($data['joint_customer_ids'])) {
                    $allCustomerIds = array_unique(array_merge([$loanApplication->customer_id], $data['joint_customer_ids']));

                    foreach ($allCustomerIds as $jointCustomerId) {
                        LoanApplicationCustomer::create([
                            'loan_application_id' => $loanApplication->id,
                            'customer_id'         => $jointCustomerId,
                        ]);
                    }
                }

                return $loanApplication;
            });

            $this->loanDocumentService->syncForApplication($loanApplication);

            $this->logActivity('CREATE', 'LoanApplication', "Created loan application ID: {$loanApplication->id}", $data);

            $staffRole = Role::where('name', config('notifications.staff_role'))->first();
            if ($staffRole) {
                foreach ($staffRole->users as $staffUser) {
                    $staffPhone = $staffUser->employee?->phone_primary;
                    if (!empty($staffPhone)) {
                        $this->notificationService->sendSms(
                            'application_submitted',
                            $staffPhone,
                            'CDP Capital: New loan application pending for review.',
                            ['loan_application_id' => $loanApplication->id, 'user_id' => $staffUser->id]
                        );
                    }
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application created successfully',
                'data'    => $loanApplication->load([
                    'application',
                    'customer:'.Customer::SUMMARY_COLUMNS,
                    'loanApplicationCustomers.customer' => fn ($q) => $q
                        ->select(explode(',', Customer::SUMMARY_COLUMNS))
                        ->with('customerDetail'),
                    'loanProduct',
                    'branch',
                    'appliedByUser:'.User::SUMMARY_COLUMNS,
                ]),
            ], 201);

        } catch (CustomerHasLiveLoanException $e) {
            return $e->toResponse();
        } catch (InvestmentCollateralException $e) {
            return $e->toResponse();
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan application.
     */
    public function show(string $id)
    {
        try {
            $loanApplication = LoanApplication::with([
                'application',
                'customer.bankDetails',
                'customer.fixedAssets',
                'customer.movingAssets',
                'customer.liabilities',
                'customer.guarantors',
                'customer.documents',
                'customer.customerDetail',
                'loanApplicationCustomers.customer.customerDetail',
                'loanApplicationCustomers.customer.bankDetails',
                'loanApplicationCustomers.customer.fixedAssets',
                'loanApplicationCustomers.customer.movingAssets',
                'loanApplicationCustomers.customer.liabilities',
                'loanApplicationCustomers.customer.documents',
                'loanProduct.loanType',
                'loanProduct.loanTerm',
                'branch',
                'appliedByUser:'.User::SUMMARY_COLUMNS,
                'reviewedByUser:'.User::SUMMARY_COLUMNS,
                'verifiedByUser:'.User::SUMMARY_COLUMNS,
                'approvedByUser:'.User::SUMMARY_COLUMNS,
                'offerRespondedByUser:'.User::SUMMARY_COLUMNS,
                'loanApplicationGuarantors.guarantor',
                'loanApplicationFixedAssets',
                'loanApplicationMovingAssets',
                'loanApplicationLiabilities',
                'loanApplicationBankDetails',
                'installments',
                'statusHistory.changedBy:'.User::SUMMARY_COLUMNS,
                'groupLoan.loanProduct',
                'groupLoan.branch',
                'groupLoan.items',
                'groupLoan.loanApplication.loanApplicationCustomers.customer.customerDetail',
                'groupLoan.loanApplication.loanApplicationCustomers.customer.bankDetails',
                'groupLoan.loanApplication.loanApplicationCustomers.customer.fixedAssets',
                'groupLoan.loanApplication.loanApplicationCustomers.customer.movingAssets',
                'groupLoan.loanApplication.loanApplicationCustomers.customer.liabilities',
                'groupLoan.loanApplication.loanApplicationCustomers.customer.documents',
            ])->find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application retrieved successfully',
                'data'    => $loanApplication,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified loan application.
     */
    public function update(UpdateLoanApplicationRequest $request, string $id)
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            if ($loanApplication->status->isTerminal()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "This loan application is {$loanApplication->status->value} and can no longer be edited."
                        . ($loanApplication->status === LoanApplicationStatus::Rejected
                            ? ' Reopen it first if it needs to be worked on again.'
                            : ''),
                    'errors'  => ['status' => $loanApplication->status->value],
                ], 422);
            }

            $data = $request->validated();

            $data = Employee::mergeRecommenderSnapshot($data);


            if (!empty($data['customer_id']) && (int) $data['customer_id'] !== (int) $loanApplication->customer_id) {
                if ($refusal = CustomerLoanEligibilityService::refusalForCustomer((int) $data['customer_id'], $loanApplication->id)) {
                    return (new CustomerHasLiveLoanException($refusal, 'customer_id'))->toResponse();
                }
            }

            $loanApplication->update($data);

            $this->logActivity('UPDATE', 'LoanApplication', "Updated loan application ID: {$loanApplication->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application updated successfully',
                'data'    => $loanApplication->fresh([
                    'application',
                    'customer:'.Customer::SUMMARY_COLUMNS,
                    'loanProduct',
                    'branch',
                    'appliedByUser:'.User::SUMMARY_COLUMNS,
                    'reviewedByUser:'.User::SUMMARY_COLUMNS,
                    'approvedByUser:'.User::SUMMARY_COLUMNS,
                ]),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Toggle the is_active status of a loan application.
     */
    public function toggleStatus(string $id)
    {
        try {
            $loanApplication = LoanApplication::query()->find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found'
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            if (!$loanApplication->is_active && ($terminal = $this->terminalStatusResponse($loanApplication))) {
                return $terminal;
            }

            $loanApplication->is_active = !$loanApplication->is_active;
            $loanApplication->save();

            Log::info('Loan application status toggled', [
                'user_id'             => Auth::id(),
                'loan_application_id' => $loanApplication->id,
                'new_status'          => $loanApplication->is_active
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application status updated successfully',
                'data'    => [
                    'id'        => $loanApplication->id,
                    'is_active' => $loanApplication->is_active
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to toggle loan application status',
                'error'   => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Activate a loan application.
     */
    public function activate(string $id)
    {
        try {
            $loanApplication = LoanApplication::query()->find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            if ($loanApplication->is_active) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Loan application is already active',
                    'data'    => $loanApplication
                ]);
            }

            if ($terminal = $this->terminalStatusResponse($loanApplication)) {
                return $terminal;
            }

            $loanApplication->update(['is_active' => true]);

            Log::info('Loan application activated', [
                'user_id'             => Auth::id(),
                'loan_application_id' => $loanApplication->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application activated successfully',
                'data'    => $loanApplication
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to activate loan application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate a loan application.
     */
    public function deactivate(string $id)
    {
        try {
            $loanApplication = LoanApplication::query()->find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            if (!$loanApplication->is_active) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Loan application is already inactive',
                    'data'    => $loanApplication
                ]);
            }

            $loanApplication->update(['is_active' => false]);

            Log::info('Loan application deactivated', [
                'user_id'             => Auth::id(),
                'loan_application_id' => $loanApplication->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application deactivated successfully',
                'data'    => $loanApplication
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to deactivate loan application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify a loan application after review and optionally assign a reviewer.
     */
    /**
     * Record which of the application's documents an officer ticked off.
     */
    private function markCheckedDocuments(LoanApplication $loanApplication, ?array $documentIds, string $byColumn, string $atColumn): void
    {
        $this->loanDocumentService->markChecked($loanApplication, $documentIds, $byColumn, $atColumn);
    }


    public function verify(Request $request, string $id)
    {
        return $this->runVerification($request, $id, false);
    }

    public function reverify(Request $request, string $id)
    {
        return $this->runVerification($request, $id, true);
    }


    private function runVerification(Request $request, string $id, bool $isReverification)
    {
        try {
            $validator = Validator::make($request->all(), [
                'remarks' => 'required|string|min:3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            $awaitingReverification = $loanApplication->status === LoanApplicationStatus::Reverify;

            if ($isReverification && !$awaitingReverification) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This loan application is not awaiting re-verification. Use the verify action instead.',
                ], 422);
            }

            if (!$isReverification && $awaitingReverification) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This loan application failed verification and is awaiting re-verification. Use the re-verify action instead.',
                ], 422);
            }

            $extra = [
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ];

            if ($awaitingReverification) {
                $extra['verify_failure_reason'] = null;
                $extra['verify_failed_at']      = null;
                $extra['reverify_count']        = (int) $loanApplication->reverify_count + 1;
            }

            if ($request->filled('remarks')) {
                $extra['verified_remarks'] = $request->input('remarks');
            }
            if ($request->filled('assigned_reviewer_id')) {
                $extra['assigned_reviewer_id'] = $request->input('assigned_reviewer_id');
            }

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::Verified,
                Auth::id(),
                $request->input('remarks'),
                $extra
            );

            $this->markCheckedDocuments(
                $loanApplication,
                $request->has('verified_document_ids') ? (array) $request->input('verified_document_ids', []) : null,
                'verified_by',
                'verified_at'
            );

            $verb = $isReverification ? 're-verified' : 'verified';

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} {$verb}", [
                'loan_application_id' => $loanApplication->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => $isReverification
                    ? 'Loan application re-verified successfully'
                    : 'Loan application verified successfully',
                'data'    => $loanApplication,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => $isReverification
                    ? 'Failed to re-verify loan application'
                    : 'Failed to verify loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    public function verifyFail(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|min:5|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            $reason = $request->input('reason');

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::Reverify,
                Auth::id(),
                $reason,
                [
                    'verify_failure_reason' => $reason,
                    'verify_failed_at'      => now(),
                ]
            );

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} failed verification", [
                'loan_application_id' => $loanApplication->id,
            ]);

            foreach ($loanApplication->notifiableCustomers() as $notifyCustomer) {
                $message = "Your loan application verification is failed.\nReason: {$reason}";

                if (!empty($notifyCustomer->phone_primary)) {
                    $this->notificationService->sendSms(
                        'application_verify_failed',
                        $notifyCustomer->phone_primary,
                        $message,
                        ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                    );
                }

                if ($loanApplication->isJointLoan() && !empty($notifyCustomer->email)) {
                    $this->notificationService->sendEmail(
                        'application_verify_failed',
                        $notifyCustomer->email,
                        'Loan Application Verification Failed',
                        $message,
                        ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                    );
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application verification failed. It is now awaiting re-verification and the customer has been notified.',
                'data'    => $loanApplication,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fail the verification of this loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    public function review(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'remarks' => 'required|string|min:3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            $extra = [
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ];
            if ($request->filled('remarks')) {
                $extra['reviewed_remarks'] = $request->input('remarks');
            }
            if ($request->filled('assigned_reviewer_id')) {
                $extra['assigned_reviewer_id'] = $request->input('assigned_reviewer_id');
            }

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::Reviewed,
                Auth::id(),
                $request->input('remarks'),
                $extra
            );

            $this->markCheckedDocuments(
                $loanApplication,
                $request->has('reviewed_document_ids') ? (array) $request->input('reviewed_document_ids', []) : null,
                'reviewed_by',
                'reviewed_at'
            );

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} reviewed", [
                'loan_application_id' => $loanApplication->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application reviewed successfully',
                'data'    => $loanApplication,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to review loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    public function reviewFail(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|min:5|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            $reason = $request->input('reason');

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::ReviewFailed,
                Auth::id(),
                $reason,
                [
                    'review_failure_reason' => $reason,
                    'review_failed_at'      => now(),
                ]
            );

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} failed review", [
                'loan_application_id' => $loanApplication->id,
            ]);

            foreach ($loanApplication->notifiableCustomers() as $notifyCustomer) {
                $message = "Your loan application has been reviewed but it failed.\nReason: {$reason}\nPlease resubmit your loan application documents again.";

                if (!empty($notifyCustomer->phone_primary)) {
                    $this->notificationService->sendSms(
                        'application_review_failed',
                        $notifyCustomer->phone_primary,
                        $message,
                        ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                    );
                }

                if ($loanApplication->isJointLoan() && !empty($notifyCustomer->email)) {
                    $this->notificationService->sendEmail(
                        'application_review_failed',
                        $notifyCustomer->email,
                        'Loan Application Review Failed',
                        $message,
                        ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                    );
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application review failed. The customer has been notified to resubmit documents.',
                'data'    => $loanApplication,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fail the review of this loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    public function resubmit(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'remarks' => 'required|string|min:3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            if (!Document::where('loan_application_id', $loanApplication->id)->exists()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Upload the requested documents before resubmitting this loan application.',
                ], 422);
            }

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::Submitted,
                Auth::id(),
                // No fallback wording any more: remarks are required, so the
                // trail records what the customer actually said rather than a
                // sentence the server made up on their behalf.
                $request->input('remarks'),
                [
                    'review_failure_reason' => null,
                    'review_failed_at'      => null,
                    'resubmitted_at'        => now(),
                    'resubmission_count'    => (int) $loanApplication->resubmission_count + 1,
                ]
            );

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} resubmitted for review", [
                'loan_application_id' => $loanApplication->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application resubmitted for review successfully',
                'data'    => $loanApplication,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to resubmit loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Approve a loan application under review.
     */
    public function approve(Request $request, string $id)
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }


            $validator = Validator::make($request->all(), [
                'approved_amount' => [
                    'nullable',
                    'numeric',
                    'min:0.01',
                    'max:' . (float) $loanApplication->requested_amount,
                ],

                'processing_fee'  => 'nullable|numeric|min:0',
                'remarks'         => 'required|string|min:3',
            ], [
                'approved_amount.max' => 'The approved amount cannot be more than the requested amount of '
                    . number_format((float) $loanApplication->requested_amount, 2)
                    . '. Approve the same amount or less.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors(),
                ], 422);
            }


            $approvedAmount = $request->filled('approved_amount')
                ? $request->input('approved_amount')
                : $loanApplication->requested_amount;

            $extra = [
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approved_amount' => $approvedAmount,
            ];
            $extra['approval_remarks'] = $request->input('remarks');

            $interest = round($approvedAmount * $loanApplication->interest_rate / 100, 2);
            $totalRepayment = round($approvedAmount + $interest, 2);
            $extra['monthly_installment'] = round($totalRepayment / $loanApplication->term_months, 2);


            $processingFee = $request->filled('processing_fee')
                ? round((float) $request->input('processing_fee'), 2)
                : $this->calculateProcessingFee($loanApplication->loanProduct, $approvedAmount);
            $extra['processing_fee'] = $processingFee;
            $extra['net_disbursement_amount'] = max(0, round($approvedAmount - $processingFee, 2));

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::Approved,
                Auth::id(),
                $request->input('remarks'),
                $extra
            );

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} approved", [
                'loan_application_id' => $loanApplication->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application approved successfully',
                'data'    => $loanApplication,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to approve loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject a loan application under review.
     */
    public function reject(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rejection_reason' => 'required|string|min:3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::Rejected,
                Auth::id(),
                $request->input('rejection_reason'),
                ['rejection_reason' => $request->input('rejection_reason')]
            );

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} rejected", [
                'loan_application_id' => $loanApplication->id,
            ]);

            foreach ($loanApplication->notifiableCustomers() as $notifyCustomer) {
                $message = "Your loan application has been rejected.\nReason: {$loanApplication->rejection_reason}";

                if (!empty($notifyCustomer->phone_primary)) {
                    $this->notificationService->sendSms(
                        'application_rejected',
                        $notifyCustomer->phone_primary,
                        $message,
                        ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                    );
                }

                if ($loanApplication->isJointLoan() && !empty($notifyCustomer->email)) {
                    $this->notificationService->sendEmail(
                        'application_rejected',
                        $notifyCustomer->email,
                        'Loan Application Rejected',
                        $message,
                        ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                    );
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application rejected successfully',
                'data'    => $loanApplication,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to reject loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Disburse an approved loan application.
     */
    /**
     * Record the borrower's answer to an approved offer.
     *
     * Approving 400,000 against a request for 500,000 is an offer, not a
     * conclusion. Until the borrower answers, the file waits: On Hold while
     * they think, Accepted when they agree -- and only then may it be
     * disbursed -- or Declined, which carries the reason and takes the
     * application straight on to Cancelled.
     *
     * One method behind three routes because the three differ only in the
     * status they land on and whether a reason is required; splitting them
     * would have meant three copies of the same guards.
     */
    private function respondToOffer(Request $request, string $id, LoanApplicationStatus $to)
    {
        try {

            $rules = ['remarks' => 'required|string|min:3|max:1000'];

            if ($to === LoanApplicationStatus::Declined) {
                $rules['decline_reason'] = 'required|string|in:' . implode(',', array_keys(LoanApplication::OFFER_DECLINE_REASONS));
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            $extra = [
                'offer_responded_by'   => Auth::id(),
                'offer_responded_at'   => now(),
                'offer_remarks'        => $request->input('remarks'),
                'offer_decline_reason' => $to === LoanApplicationStatus::Declined
                    ? $request->input('decline_reason')
                    : null,
            ];

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                $to,
                Auth::id(),
                $request->input('remarks'),
                $extra
            );

            if ($to === LoanApplicationStatus::Declined) {
                $loanApplication = $this->workflowService->transition(
                    $loanApplication,
                    LoanApplicationStatus::Cancelled,
                    Auth::id(),
                    'Cancelled: customer declined the approved offer'
                );
            }

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} offer {$to->value}", [
                'loan_application_id' => $loanApplication->id,
                'decline_reason'      => $extra['offer_decline_reason'],
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => match ($to) {
                    LoanApplicationStatus::OnHold   => 'Offer put on hold for the customer',
                    LoanApplicationStatus::Accepted => 'Customer accepted the approved offer',
                    default                         => 'Customer declined the offer — the application has been cancelled',
                },
                'data'    => $loanApplication,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to record the offer response',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function holdOffer(Request $request, string $id)
    {
        return $this->respondToOffer($request, $id, LoanApplicationStatus::OnHold);
    }

    public function acceptOffer(Request $request, string $id)
    {
        return $this->respondToOffer($request, $id, LoanApplicationStatus::Accepted);
    }

    public function declineOffer(Request $request, string $id)
    {
        return $this->respondToOffer($request, $id, LoanApplicationStatus::Declined);
    }

    public function disburse(string $id)
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::Disbursed,
                Auth::id(),
                'Loan disbursed to the customer',
                ['disbursed_at' => now()]
            );

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::Active,
                Auth::id(),
                'Loan activated on disbursement'
            );

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} disbursed", [
                'loan_application_id' => $loanApplication->id,
            ]);

            $disbursedMessage = 'Congratulations! Your loan has been approved and successfully disbursed. Your repayment schedule is now available.';

            foreach ($loanApplication->notifiableCustomers() as $notifyCustomer) {
                if (!empty($notifyCustomer->phone_primary)) {
                    $this->notificationService->sendSms(
                        'loan_disbursed',
                        $notifyCustomer->phone_primary,
                        $disbursedMessage,
                        ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                    );
                }

                if ($loanApplication->isJointLoan() && !empty($notifyCustomer->email)) {
                    $this->notificationService->sendEmail(
                        'loan_disbursed',
                        $notifyCustomer->email,
                        'Loan Disbursed',
                        $disbursedMessage,
                        ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                    );
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application disbursed successfully',
                'data'    => $loanApplication,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to disburse loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Put a rejected loan application back into play.
     */
    public function reopen(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'remarks' => 'required|string|min:3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            $rejection = LoanApplicationStatusHistory::where('loan_application_id', $loanApplication->id)
                ->where('loan_application_status', LoanApplicationStatus::Rejected)
                ->latest('id')
                ->first();

            $borrowers = ['customer_id' => $loanApplication->customer_id];
            foreach ($loanApplication->loanApplicationCustomers()->pluck('customer_id') as $i => $memberId) {
                if ((int) $memberId !== (int) $loanApplication->customer_id) {
                    $borrowers["loan_application_customers.{$i}"] = $memberId;
                }
            }

            $loanApplication = DB::transaction(function () use ($loanApplication, $borrowers, $request, $rejection) {
                CustomerLoanEligibilityService::assertEligible($borrowers, $loanApplication->id);

                // A rejected loan freed its policy; another loan may have taken
                // it since. It cannot be reopened while that loan is live.
                if ($loanApplication->collateral_policy_number) {
                    $holder = LoanApplication::holdingPolicy($loanApplication->collateral_policy_number)
                        ->where('id', '!=', $loanApplication->id)
                        ->lockForUpdate()
                        ->with('application')
                        ->first();
                    if ($holder) {
                        throw new InvestmentCollateralException(
                            "This loan cannot be reopened: its policy {$loanApplication->collateral_policy_number} now secures loan {$holder->reference()}. Create a new application with fresh collateral instead."
                        );
                    }
                }

                return $this->workflowService->transition(
                    $loanApplication,
                    LoanApplicationStatus::Reopened,
                    Auth::id(),
                    $request->input('remarks'),
                    ['is_active' => true],
                    [
                        'reopened_from'             => LoanApplicationStatus::Rejected->value,
                        'reopened_by'               => Auth::id(),
                        'reopened_at'               => now()->toDateTimeString(),
                        'rejection_history_id'      => $rejection?->id,
                        'previous_rejection_reason' => $loanApplication->rejection_reason ?? $rejection?->remarks,
                        'previously_rejected_at'    => $rejection?->changed_at?->toDateTimeString(),
                        'previously_rejected_by'    => $rejection?->changed_by,
                    ]
                );
            });

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} reopened after rejection", [
                'loan_application_id' => $loanApplication->id,
                'reopened_by'         => Auth::id(),
                'remarks'             => $request->input('remarks'),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application reopened successfully. It can be edited and sent for review again.',
                'data'    => $loanApplication,
            ], 200);

        } catch (CustomerHasLiveLoanException $e) {
            return $e->toResponse();
        } catch (InvestmentCollateralException $e) {
            return $e->toResponse();
        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to reopen loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Cancel a loan application that has not yet been approved.
     */
    public function cancel(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'remarks' => 'required|string|min:3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::Cancelled,
                Auth::id(),
                $request->input('remarks')
            );

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} cancelled", [
                'loan_application_id' => $loanApplication->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application cancelled successfully',
                'data'    => $loanApplication,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to cancel loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Soft-delete the specified loan application.
     */
    public function destroy(string $id)
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($guard = $this->groupLoanGuardResponse($loanApplication)) {
                return $guard;
            }

            $loanApplication->delete();

            $this->logActivity('DELETE', 'LoanApplication', "Deleted loan application ID: {$loanApplication->id}", [
                'loan_application_id' => $id,
                'deleted_by'          => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete loan application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Derive the processing fee from the loan product's configured rule
     * (fixed amount, or a percentage of the approved amount).
     */
    private function calculateProcessingFee(?LoanProduct $product, float $approvedAmount): float
    {
        if (!$product || !$product->processing_fee_value) {
            return 0.0;
        }

        return $product->processing_fee_type === 'percentage'
            ? round($approvedAmount * $product->processing_fee_value / 100, 2)
            : round((float) $product->processing_fee_value, 2);
    }
}
