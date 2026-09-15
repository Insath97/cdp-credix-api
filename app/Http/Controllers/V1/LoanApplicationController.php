<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Application;
use App\Models\Branch;
use App\Models\LoanApplication;
use App\Models\LoanApplicationCustomer;
use App\Models\LoanProduct;
use App\Models\Customer;
use App\Models\Document;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use App\Http\Requests\CreateLoanApplicationRequest;
use App\Http\Requests\UpdateLoanApplicationRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Services\LoanApplicationWorkflowService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class LoanApplicationController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, ScopesToUserBranch;

    public function __construct(
        protected LoanApplicationWorkflowService $workflowService,
        protected NotificationService $notificationService,
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
            new Middleware('permission:Loan Application Review', only: ['review']),
            new Middleware('permission:Loan Application Verify', only: ['verify']),
            new Middleware('permission:Loan Application Approve', only: ['approve']),
            new Middleware('permission:Loan Application Reject', only: ['reject']),
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
            // A loan list only names the borrower and any co-borrowers, so both are
            // loaded on summary columns — NIC, date of birth, address, income and
            // employer/business details are never queried. show() is the credit
            // file and does select them.
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

            // Accepts one status or a comma-separated list. The document upload
            // screen uses it to offer only applications still at Submitted --
            // documents are collected before review, and listing a disbursed or
            // closed loan there only invites uploading against the wrong one.
            if ($request->filled('status')) {
                $statuses = array_filter(array_map('trim', explode(',', (string) $request->status)));
                $query->whereIn('status', $statuses);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            // A branch officer sees their own branch's rows only.
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

            // monthly_installment is intentionally NOT calculated here — the customer's
            // requested_amount is not necessarily what gets disbursed. Calculation happens
            // at approve() using approved_amount, once that figure is actually known.

            $loanApplication = LoanApplication::create($data);

            if (!empty($data['joint_customer_ids'])) {
                $allCustomerIds = array_unique(array_merge([$loanApplication->customer_id], $data['joint_customer_ids']));

                foreach ($allCustomerIds as $jointCustomerId) {
                    LoanApplicationCustomer::create([
                        'loan_application_id' => $loanApplication->id,
                        'customer_id'         => $jointCustomerId,
                    ]);
                }
            }

            $this->logActivity('CREATE', 'LoanApplication', "Created loan application ID: {$loanApplication->id}", $data);

            $staffRole = Role::where('name', config('notifications.staff_role'))->first();
            if ($staffRole) {
                foreach ($staffRole->users as $staffUser) {
                    $staffPhone = $staffUser->employee?->phone_primary;
                    if (!empty($staffPhone)) {
                        $this->notificationService->sendSms(
                            'application_submitted',
                            $staffPhone,
                            'CDP Credix: New loan application pending for review.',
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

            $data = $request->validated();

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

            // Toggling a finished loan back on would show it as "Active".
            // Only block turning it ON — turning it off is always fine.
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
     *
     * The list replaces whatever was marked before rather than adding to it,
     * so unticking a document at a second attempt actually clears it. Ids that
     * do not belong to this application are ignored — the checklist is built
     * from the application's own documents, so anything else is a stale or
     * forged id, not a document this officer looked at.
     *
     * Marking is deliberately outside the status transition: a failure to
     * stamp a checklist must not roll back a workflow step that already
     * happened, so it is logged and swallowed.
     */
    private function markCheckedDocuments(LoanApplication $loanApplication, ?array $documentIds, string $byColumn, string $atColumn): void
    {
        if ($documentIds === null) {
            return;
        }

        try {
            $ids = array_values(array_unique(array_filter(array_map('intval', $documentIds))));

            $scope = Document::where('loan_application_id', $loanApplication->id);

            (clone $scope)->whereNotIn('id', $ids ?: [0])
                ->update([$byColumn => null, $atColumn => null]);

            if ($ids) {
                (clone $scope)->whereIn('id', $ids)
                    ->update([$byColumn => Auth::id(), $atColumn => now()]);
            }
        } catch (\Throwable $th) {
            Log::warning('Failed to mark checked documents', [
                'loan_application_id' => $loanApplication->id,
                'column' => $byColumn,
                'error' => $th->getMessage(),
            ]);
        }
    }

    public function verify(Request $request, string $id)
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
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ];
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

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} verified", [
                'loan_application_id' => $loanApplication->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application verified successfully',
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
                'message' => 'Failed to verify loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Review a submitted loan application — the first of the three hands the
     * file passes through. Records who reviewed it, then hands it on for
     * verification by someone else.
     */
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

            // You can approve less than was requested, never more: the
            // requested amount is the ceiling. Validated here rather than in a
            // FormRequest because the ceiling comes from the loan itself, and
            // the method previously validated nothing at all — which is how a
            // loan ended up approved for double what was requested.
            $validator = Validator::make($request->all(), [
                'approved_amount' => [
                    'nullable',
                    'numeric',
                    'min:0.01',
                    'max:' . (float) $loanApplication->requested_amount,
                ],
                // A negative fee would make net_disbursement_amount larger
                // than the approved amount — cash out the door exceeding what
                // was actually approved.
                'processing_fee'  => 'nullable|numeric|min:0',
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

            // Approved amount defaults to what the customer requested unless the
            // approver explicitly overrides it (e.g. approving a lower amount).
            $approvedAmount = $request->filled('approved_amount')
                ? $request->input('approved_amount')
                : $loanApplication->requested_amount;

            $extra = [
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approved_amount' => $approvedAmount,
            ];
            if ($request->filled('remarks')) {
                $extra['approval_remarks'] = $request->input('remarks');
            }

            // All financial figures are backend-calculated from approved_amount —
            // never requested_amount — since that's the figure actually being lent.
            $interest = round($approvedAmount * $loanApplication->interest_rate / 100, 2);
            $totalRepayment = round($approvedAmount + $interest, 2);
            $extra['monthly_installment'] = round($totalRepayment / $loanApplication->term_months, 2);

            // Processing fee defaults to the product's configured rule unless the
            // approver explicitly overrides it. It is deducted from the cash the
            // customer actually receives at disbursement (net_disbursement_amount) —
            // it never changes what they owe: interest, installments and
            // outstanding_balance all stay based on the full approved_amount.
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
            // Remarks are required on every answer: the status alone says what
            // the borrower decided, never what they actually said, and that
            // sentence is what the next officer to open the file reads.
            $rules = ['remarks' => 'required|string|min:3|max:1000'];

            // A decline has to say why: "customers walked away from 12 offers
            // last quarter" is only actionable with the reason beside it.
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

            // A declined offer is the end of this application. Cancelling it
            // here rather than leaving it for someone to notice keeps the
            // pipeline honest -- and both steps land in the status history, so
            // the reason stays readable after the file is closed.
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
                null,
                ['disbursed_at' => now()]
            );

            $loanApplication = $this->workflowService->transition(
                $loanApplication,
                LoanApplicationStatus::Active,
                Auth::id()
            );

            $this->logActivity('UPDATE', 'LoanApplication', "Loan application ID: {$loanApplication->id} disbursed", [
                'loan_application_id' => $loanApplication->id,
            ]);

            // Deliberately no amounts here. Charges deducted at disbursement --
            // the processing fee, and a group loan's service charge -- are not
            // quoted to the customer in a notification; they belong on the
            // disbursement voucher and the repayment schedule, where the customer
            // sees them in context. Mirrors the group loan disbursed message,
            // which has never carried them.
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
     * Cancel a loan application that has not yet been approved.
     */
    public function cancel(Request $request, string $id)
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
