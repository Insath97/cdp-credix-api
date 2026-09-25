<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Application;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\GroupLoan;
use App\Models\Guarantor;
use App\Models\LoanApplication;
use App\Models\LoanApplicationStatusHistory;
use App\Models\Setting;
use App\Models\Customer;
use App\Models\Document;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use App\Http\Requests\CreateGroupLoanRequest;
use App\Http\Requests\UpdateGroupLoanRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Enums\GroupLoanStatus;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\CustomerHasLiveLoanException;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Services\CustomerLoanEligibilityService;
use App\Services\GroupLoanWorkflowService;
use App\Services\LoanDocumentService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class GroupLoanController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, ScopesToUserBranch;

    public function __construct(
        protected GroupLoanWorkflowService $workflowService,
        protected NotificationService $notificationService,
        protected LoanDocumentService $loanDocumentService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Group Loan Index', only: ['index', 'show', 'byStatus', 'statusCounts']),
            new Middleware('permission:Group Loan Create', only: ['store']),
            new Middleware('permission:Group Loan Update', only: ['update']),
            new Middleware('permission:Group Loan Toggle Status', only: ['toggleStatus', 'activate', 'deactivate']),
            new Middleware('permission:Group Loan Delete', only: ['destroy']),
            new Middleware('permission:Group Loan Review', only: ['review', 'reviewFail']),
            new Middleware('permission:Group Loan Resubmit', only: ['resubmit']),
            new Middleware('permission:Group Loan Verify', only: ['verify', 'verifyFail']),
            new Middleware('permission:Group Loan Reverify', only: ['reverify']),
            new Middleware('permission:Group Loan Approve', only: ['approve']),
            new Middleware('permission:Group Loan Reject', only: ['reject']),
            new Middleware('permission:Group Loan Reopen', only: ['reopen']),
            new Middleware('permission:Group Loan Offer Response', only: ['holdOffer', 'acceptOffer', 'declineOffer']),
            new Middleware('permission:Group Loan Disburse', only: ['disburse']),
            new Middleware('permission:Group Loan Cancel', only: ['cancel']),
        ];
    }

    /**
     * Display a listing of group loans.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = GroupLoan::with([
                'loanProduct',
                'branch',
                'appliedByUser:'.User::SUMMARY_COLUMNS,
                'reviewedByUser:'.User::SUMMARY_COLUMNS,
                'approvedByUser:'.User::SUMMARY_COLUMNS,
            ]);

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->filled('status')) {
                $status = GroupLoanStatus::tryFrom($request->input('status'));

                if (!$status) {
                    return $this->invalidStatusResponse($request->input('status'));
                }

                $query->status($status);
            }

            // A branch officer sees their own branch's rows only.
            $this->scopeToUserBranch($query);

            $groupLoans = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'GroupLoan', 'Group loans index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'is_active', 'status']),
                'count'   => $groupLoans->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loans retrieved successfully',
                'data'    => $groupLoans,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve group loans',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Dedicated per-status listing backing the frontend's lifecycle tabs:
     * GET /group-loans/status/{available|locked|disbursed|closed}. Same shape
     * and same filters as index() — it just pins the status from the path
     * instead of the query string.
     */
    public function byStatus(Request $request, string $status)
    {
        if (!GroupLoanStatus::tryFrom($status)) {
            return $this->invalidStatusResponse($status);
        }

        return $this->index($request->merge(['status' => $status]));
    }

    /**
     * Row counts per lifecycle status, for the tab badges. Returns every
     * filterable status, zero included, so the frontend never has to
     * back-fill missing keys.
     */
    public function statusCounts()
    {
        try {

            $counts = $this->scopeToUserBranch(GroupLoan::query())
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $data = [];
            foreach (GroupLoanStatus::filterable() as $status) {
                $data[$status->value] = (int) ($counts[$status->value] ?? 0);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan status counts retrieved successfully',
                'data'    => $data,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve group loan status counts',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Refuse to flag a finished group loan active again.
     *
     * status and is_active are separate columns, and is_active is what the
     * listings filter on and what the frontend badge reads. Without this a
     * cancelled, rejected or closed group loan could be flipped back to active
     * and would then display as "Active" despite its workflow being over —
     * and GroupLoanStatus has no transition out of those states.
     *
     * Returns a 422 response when the action must be refused, or null when the
     * group loan is still live.
     */
    protected function terminalStatusResponse(GroupLoan $groupLoan)
    {
        if (!$groupLoan->status->isTerminal()) {
            return null;
        }

        return response()->json([
            'status'  => 'error',
            'message' => "This group loan is {$groupLoan->status->value} and cannot be reactivated.",
            'errors'  => [
                'status' => $groupLoan->status->value,
            ],
        ], 422);
    }

    /**
     * Shared 422 for an unrecognised status, listing what is accepted.
     */
    protected function invalidStatusResponse(string $status)
    {
        return response()->json([
            'status'  => 'error',
            'message' => "Invalid group loan status '{$status}'.",
            'errors'  => [
                'status' => array_column(GroupLoanStatus::cases(), 'value'),
            ],
        ], 422);
    }

    /**
     * Store a newly created group loan, along with its product/material line
     * items and the single loan application that carries the whole group's
     * money — all created together in a single request, since neither has a
     * separate reusable catalog/picker.
     *
     * The group borrows as one loan: one `applications` row, one
     * `loan_applications` row, and one `loan_application_customers` row per
     * member — the same shape Joint Loan uses. Members are always existing
     * Customer records; nothing here creates or duplicates a customer.
     */
    public function store(CreateGroupLoanRequest $request)
    {
        try {
            $data = $request->validated();

            $data = Employee::mergeRecommenderSnapshot($data);

            $groupLoan = DB::transaction(function () use ($data) {
                $items = collect($data['items'])->map(function ($item) {
                    $item['line_total'] = round($item['quantity'] * $item['unit_price'], 2);
                    return $item;
                });


                $requestedAmount = round($items->sum('line_total'), 2);


                $serviceChargePercentage = (float) Setting::get('group_loan_service_charge_percentage', 0);


                $resolvedMembers = collect($data['members'])->map(function ($member) use ($data) {
                    if (!empty($member['customer_id'])) {
                        $member['customer_id'] = (int) $member['customer_id'];
                        return $member;
                    }

                    $customer = Customer::create([
                        'full_name'      => $member['member_name'] ?? null,
                        'id_type'        => 'NIC',
                        'id_number'      => $member['nic'] ?? null,
                        'address_line_1' => $member['address'] ?? null,
                        'phone_primary'  => $member['phone_number'] ?? null,
                        'branch_id'      => $data['branch_id'] ?? null,
                    ]);

                    $customer->customerDetail()->create([
                        'gn_division' => $member['gn_division'] ?? null,
                        'ds_division' => $member['ds_division'] ?? null,
                    ]);

                    $member['customer_id'] = $customer->id;
                    return $member;
                });

                $memberCustomerIds = $resolvedMembers
                    ->pluck('customer_id')
                    ->values();

                // One live loan per customer, re-checked under a row lock so
                // two submissions racing for the same person cannot both pass.
                // A typed-in member's new customer row is matched to any older
                // row by NIC inside the service.
                CustomerLoanEligibilityService::assertEligible(
                    $resolvedMembers->mapWithKeys(fn ($m, $i) => [
                        "members.{$i}." . (empty($data['members'][$i]['customer_id']) ? 'nic' : 'customer_id') => $m['customer_id'],
                    ])->all()
                );

                $groupLoan = GroupLoan::create([
                    'loan_product_id'           => $data['loan_product_id'],
                    'branch_id'                 => $data['branch_id'] ?? null,
                    'group_name'                => $data['group_name'] ?? null,
                    'number_of_members'         => $memberCustomerIds->count(),
                    'competency'                => $data['competency'],
                    'requested_amount'          => $requestedAmount,
                    'service_charge_percentage' => $serviceChargePercentage,
                    'term_months'               => $data['term_months'],
                    'applied_by'                => Auth::id(),
                    'applied_at'                => now(),
                    'status'                    => GroupLoanStatus::Available,
                ]);

                foreach ($items as $item) {
                    $groupLoan->items()->create($item);
                }

                $branchName = !empty($data['branch_id'])
                    ? Branch::find($data['branch_id'])?->name
                    : null;

                $application = Application::create([
                    'application_type'        => 'group_loan',
                    'branch'                  => $branchName,
                    'requested_amount'        => $requestedAmount,
                    'repayment_period_months' => $data['term_months'],
                ]);

                $loanApplication = $groupLoan->loanApplication()->create([
                    'application_id'   => $application->id,
                    // The first member is the primary applicant on the row; the
                    // pivot below holds the complete member set, primary included.
                    'customer_id'      => $memberCustomerIds->first(),
                    'loan_product_id'  => $data['loan_product_id'],
                    'branch_id'        => $data['branch_id'] ?? null,
                    'requested_amount' => $requestedAmount,
                    'interest_rate'    => null,
                    'interest_type'    => null,
                    'term_months'      => $data['term_months'],
                    'applied_by'       => Auth::id(),
                    'applied_at'       => now(),
                    'status'           => LoanApplicationStatus::Submitted,
                    'recommended_by_employee_id' => $data['recommended_by_employee_id'] ?? null,
                    'recommender_name'           => $data['recommender_name'] ?? null,
                    'recommender_employee_code'  => $data['recommender_employee_code'] ?? null,
                    'recommender_nic'            => $data['recommender_nic'] ?? null,
                    'recommender_phone'          => $data['recommender_phone'] ?? null,
                ]);

                LoanApplicationStatusHistory::record(
                    $loanApplication,
                    LoanApplicationStatus::Submitted,
                    Auth::id(),
                    'Group loan application submitted'
                );

                foreach ($memberCustomerIds as $customerId) {
                    $pivot = $loanApplication->loanApplicationCustomers()->create([
                        'customer_id' => $customerId,
                    ]);
                    $pivot->setRelation('customer', Customer::find($customerId));
                    $pivot->snapshotFromCustomer();
                }

                foreach ($data['guarantors'] ?? [] as $guarantorData) {
                    $guarantor = Guarantor::create(array_merge($guarantorData, [
                        'customer_id' => $memberCustomerIds->first(),
                    ]));

                    $loanApplication->loanApplicationGuarantors()->create([
                        'guarantor_id'   => $guarantor->id,
                        'guarantor_type' => $guarantorData['type'],
                        'status'         => 'pending',
                    ]);
                }

                return $groupLoan;
            });

            // Index the file's documents, the same as an individual loan.
            // Every member's own papers count towards the group's file, and
            // they are attached by now.
            if ($application = $groupLoan->loanApplication) {
                $this->loanDocumentService->syncForApplication($application);
            }

            $this->logActivity('CREATE', 'GroupLoan', "Created group loan ID: {$groupLoan->id}", $data);

            $staffRole = Role::where('name', config('notifications.staff_role'))->first();
            if ($staffRole) {
                foreach ($staffRole->users as $staffUser) {
                    $staffPhone = $staffUser->employee?->phone_primary;
                    if (!empty($staffPhone)) {
                        $this->notificationService->sendSms(
                            'group_loan_submitted',
                            $staffPhone,
                            'CDP Capital: New group loan application pending for review.',
                            // Names the loan so the appended reference can
                            // reach it; without this the reviewer was told a
                            // group loan was waiting but not which one.
                            ['user_id' => $staffUser->id, 'loan_application_id' => $groupLoan->loanApplication?->id]
                        );
                    }
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan created successfully',
                'data'    => $groupLoan->load([
                    'loanProduct',
                    'branch',
                    'items',
                    'loanApplication.loanApplicationCustomers.customer.customerDetail',
                    'loanApplication.loanApplicationGuarantors.guarantor',
                    'appliedByUser:'.User::SUMMARY_COLUMNS,
                ]),
            ], 201);

        } catch (CustomerHasLiveLoanException $e) {
            return $e->toResponse();
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified group loan.
     */
    public function show(string $id)
    {
        try {
            $groupLoan = GroupLoan::with([
                'loanProduct',
                'branch',
                'items',
                'loanApplication.loanApplicationCustomers.customer.customerDetail',
                'loanApplication.installments',
                'loanApplication.payments.customer:'.Customer::SUMMARY_COLUMNS,
                'loanApplication.statusHistory.changedBy:'.User::SUMMARY_COLUMNS,
                'appliedByUser:'.User::SUMMARY_COLUMNS,
                'reviewedByUser:'.User::SUMMARY_COLUMNS,
                'approvedByUser:'.User::SUMMARY_COLUMNS,
            ])->find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan retrieved successfully',
                'data'    => array_merge($groupLoan->toArray(), [
                    'members' => $groupLoan->memberBreakdown(),
                ]),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve group loan',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified group loan's header fields. Items and members are
     * edited through their own sub-resources (group-loan-items,
     * group-loan-members), and only while the group loan is still Available.
     */
    public function update(UpdateGroupLoanRequest $request, string $id)
    {
        try {
            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            if ($groupLoan->status !== GroupLoanStatus::Available) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "This group loan is {$groupLoan->status->value} and its details are locked. Edit it while the group loan is still Available (before approval).",
                    'errors'  => [
                        'group_loan_id' => $groupLoan->id,
                        'status'        => $groupLoan->status->value,
                    ],
                ], 422);
            }

            $data = $request->validated();

            $groupLoan->update($data);

            $this->workflowService->syncAmounts($groupLoan->refresh());

            $this->logActivity('UPDATE', 'GroupLoan', "Updated group loan ID: {$groupLoan->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan updated successfully',
                'data'    => $groupLoan->fresh([
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
                'message' => 'Failed to update group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Toggle the is_active status of a group loan.
     */
    public function toggleStatus(string $id)
    {
        try {
            $groupLoan = GroupLoan::query()->find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            if (!$groupLoan->is_active && ($terminal = $this->terminalStatusResponse($groupLoan))) {
                return $terminal;
            }

            $groupLoan->is_active = !$groupLoan->is_active;
            $groupLoan->save();

            Log::info('Group loan status toggled', [
                'user_id'      => Auth::id(),
                'group_loan_id' => $groupLoan->id,
                'new_status'   => $groupLoan->is_active,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan status updated successfully',
                'data'    => [
                    'id'        => $groupLoan->id,
                    'is_active' => $groupLoan->is_active,
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to toggle group loan status',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate a group loan.
     */
    public function activate(string $id)
    {
        try {
            $groupLoan = GroupLoan::query()->find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            if ($groupLoan->is_active) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Group loan is already active',
                    'data'    => $groupLoan,
                ]);
            }

            if ($terminal = $this->terminalStatusResponse($groupLoan)) {
                return $terminal;
            }

            $groupLoan->update(['is_active' => true]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan activated successfully',
                'data'    => $groupLoan,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to activate group loan',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate a group loan.
     */
    public function deactivate(string $id)
    {
        try {
            $groupLoan = GroupLoan::query()->find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            if (!$groupLoan->is_active) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Group loan is already inactive',
                    'data'    => $groupLoan,
                ]);
            }

            $groupLoan->update(['is_active' => false]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan deactivated successfully',
                'data'    => $groupLoan,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to deactivate group loan',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify a group loan after review and cascade to every member.
     *
     * Only from Reviewed. A group loan that already failed verification is
     * sitting in Reverify and belongs to reverify() instead — see the same
     * split in LoanApplicationController.
     */
    public function verify(Request $request, string $id)
    {
        return $this->runVerification($request, $id, false);
    }

    /**
     * Verify a group loan that was sent back for a second look.
     *
     * The clearing of the old failure reason and the bump of reverify_count
     * happen in GroupLoanWorkflowService::verify(), so the group and
     * individual paths cannot drift apart.
     */
    public function reverify(Request $request, string $id)
    {
        return $this->runVerification($request, $id, true);
    }

    /**
     * The body behind verify() and reverify(). One implementation because it
     * is one check; only the starting status and the wording differ.
     */
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

            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            // The stage lives on the group's single application, not on the
            // header — the header stays Available right through review and
            // verification — so that is what decides which route applies.
            $awaitingReverification = $groupLoan->loanApplication?->status === LoanApplicationStatus::Reverify;

            if ($isReverification && !$awaitingReverification) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This group loan is not awaiting re-verification. Use the verify action instead.',
                ], 422);
            }

            if (!$isReverification && $awaitingReverification) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This group loan failed verification and is awaiting re-verification. Use the re-verify action instead.',
                ], 422);
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

            $groupLoan = $this->workflowService->verify($groupLoan, Auth::id(), $request->input('remarks'), $extra);

            $this->markCheckedDocuments(
                $groupLoan,
                $request->has('verified_document_ids') ? (array) $request->input('verified_document_ids', []) : null,
                'verified_by',
                'verified_at'
            );

            $verb = $isReverification ? 're-verified' : 'verified';

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} {$verb}", [
                'group_loan_id' => $groupLoan->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => $isReverification
                    ? 'Group loan re-verified successfully'
                    : 'Group loan verified successfully',
                'data'    => $groupLoan,
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
                    ? 'Failed to re-verify group loan'
                    : 'Failed to verify group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Review a submitted group loan — the first of the three hands before
     * approval. Records who reviewed it and cascades the stage to the group's
     * single loan application; the header itself stays Available.
     */
    /**
     * Record which of the group's documents an officer ticked off.
     *
     * A group loan's paperwork hangs off its loan application, exactly like an
     * individual one's, so the stamp lands in loan_documents against that
     * application. Members' own documents are shared with any other loan they
     * hold, which is precisely why the stamp cannot live on the document row.
     */
    private function markCheckedDocuments(GroupLoan $groupLoan, ?array $documentIds, string $byColumn, string $atColumn): void
    {
        if (!$application = $groupLoan->loanApplication) {
            return;
        }

        $this->loanDocumentService->markChecked($application, $documentIds, $byColumn, $atColumn);
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

            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
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

            $groupLoan = $this->workflowService->review($groupLoan, Auth::id(), $request->input('remarks'), $extra);

            $this->markCheckedDocuments(
                $groupLoan,
                $request->has('reviewed_document_ids') ? (array) $request->input('reviewed_document_ids', []) : null,
                'reviewed_by',
                'reviewed_at'
            );

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} reviewed", [
                'group_loan_id' => $groupLoan->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan reviewed successfully',
                'data'    => $groupLoan,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to review group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Fail the verification of a reviewed group loan.
     *
     * The mirror of LoanApplicationController::verifyFail, for the loan a
     * group borrows as one. Not a rejection — the file lands in Reverify for a
     * second look, every member is told why by SMS, and verification runs
     * again.
     */
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

            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            $reason = $request->input('reason');

            $groupLoan = $this->workflowService->verifyFail($groupLoan, Auth::id(), $reason, [
                'verify_failure_reason' => $reason,
                'verify_failed_at'      => now(),
            ]);

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} failed verification", [
                'group_loan_id' => $groupLoan->id,
            ]);

            $application = $groupLoan->loanApplication;

            foreach ($application?->notifiableCustomers() ?? collect() as $customer) {
                $message = "Your group loan application verification is failed.\nReason: {$reason}";
                $context = ['loan_application_id' => $application->id, 'customer_id' => $customer->id];

                if (!empty($customer->phone_primary)) {
                    $this->notificationService->sendSms('group_loan_verify_failed', $customer->phone_primary, $message, $context);
                }

                if (!empty($customer->email)) {
                    $this->notificationService->sendEmail('group_loan_verify_failed', $customer->email, 'Group Loan Verification Failed', $message, $context);
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan verification failed. It is now awaiting re-verification and the members have been notified.',
                'data'    => $groupLoan,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fail the verification of this group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function reviewFail(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [

                'reason' => 'required|string|min:5|max:300',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            $reason = $request->input('reason');

            $groupLoan = $this->workflowService->reviewFail($groupLoan, Auth::id(), $reason, [
                'review_failure_reason' => $reason,
                'review_failed_at'      => now(),
            ]);

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} failed review", [
                'group_loan_id' => $groupLoan->id,
            ]);

            $application = $groupLoan->loanApplication;

            foreach ($application?->notifiableCustomers() ?? collect() as $customer) {
                $message = "Your group loan application has been reviewed but it failed.\nReason: {$reason}\nPlease resubmit your loan application documents again.";
                $context = ['loan_application_id' => $application->id, 'customer_id' => $customer->id];

                if (!empty($customer->phone_primary)) {
                    $this->notificationService->sendSms('group_loan_review_failed', $customer->phone_primary, $message, $context);
                }

                if (!empty($customer->email)) {
                    $this->notificationService->sendEmail('group_loan_review_failed', $customer->email, 'Group Loan Review Failed', $message, $context);
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan review failed. The members have been notified to resubmit documents.',
                'data'    => $groupLoan,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fail the review of this group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Put a failed group loan back in the review queue once its documents have
     * been reuploaded.
     *
     * The failure reason is cleared — it described the old document set and
     * would otherwise sit on screen next to the new one — but the count is
     * kept and bumped. The previous failure stays readable in
     * loan_application_status_history.
     */
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

            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            $application = $groupLoan->loanApplication;

            if (!$application) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This group loan has no loan application attached.',
                ], 422);
            }

            if (!Document::where('loan_application_id', $application->id)->exists()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Upload the requested documents before resubmitting this group loan.',
                ], 422);
            }

            $groupLoan = $this->workflowService->resubmit(
                $groupLoan,
                Auth::id(),
                // No fallback wording any more: remarks are required, so the
                // trail records what the group actually said rather than a
                // sentence the server made up on their behalf.
                $request->input('remarks'),
                [
                    'review_failure_reason' => null,
                    'review_failed_at'      => null,
                    'resubmitted_at'        => now(),
                    'resubmission_count'    => (int) $groupLoan->resubmission_count + 1,
                ]
            );

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} resubmitted for review", [
                'group_loan_id' => $groupLoan->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan resubmitted for review successfully',
                'data'    => $groupLoan,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to resubmit group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Approve a group loan under review.
     */
    public function approve(Request $request, string $id)
    {
        try {
            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'approved_amount' => [
                    'nullable',
                    'numeric',
                    'min:0.01',
                    'max:' . (float) $groupLoan->requested_amount,
                ],
                'remarks'         => 'required|string|min:3',
            ], [
                'approved_amount.max' => 'The approved amount cannot be more than the requested amount of '
                    . number_format((float) $groupLoan->requested_amount, 2)
                    . '. Approve the same amount or less.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $approvedAmountOverride = $request->filled('approved_amount')
                ? (float) $request->input('approved_amount')
                : null;

            $groupLoan = $this->workflowService->approve(
                $groupLoan,
                $approvedAmountOverride,
                Auth::id(),
                $request->input('remarks')
            );

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} approved", [
                'group_loan_id' => $groupLoan->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan approved successfully',
                'data'    => $groupLoan,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to approve group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject a group loan under review.
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

            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            $groupLoan = $this->workflowService->reject($groupLoan, $request->input('rejection_reason'), Auth::id());
            $groupLoan->load('loanApplication.loanApplicationCustomers.customer');

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} rejected", [
                'group_loan_id' => $groupLoan->id,
            ]);

            $application = $groupLoan->loanApplication;

            foreach ($application?->notifiableCustomers() ?? collect() as $customer) {
                $message = "Your group loan application has been rejected.\nReason: {$groupLoan->rejection_reason}";
                $context = ['loan_application_id' => $application->id, 'customer_id' => $customer->id];

                if (!empty($customer->phone_primary)) {
                    $this->notificationService->sendSms('group_loan_rejected', $customer->phone_primary, $message, $context);
                }

                if (!empty($customer->email)) {
                    $this->notificationService->sendEmail('group_loan_rejected', $customer->email, 'Group Loan Application Rejected', $message, $context);
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan rejected successfully',
                'data'    => $groupLoan,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to reject group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Put a rejected group loan back into play.
     *
     * The group-loan counterpart of LoanApplicationController::reopen(). The
     * individual endpoint refuses group loans (groupLoanGuardResponse), so
     * this is the only way to reopen one. Every member is checked against the
     * one-live-loan rule first -- see GroupLoanWorkflowService::reopen().
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

            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            $groupLoan = $this->workflowService->reopen($groupLoan, $request->input('remarks'), Auth::id());

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} reopened after rejection", [
                'group_loan_id' => $groupLoan->id,
                'reopened_by'   => Auth::id(),
                'remarks'       => $request->input('remarks'),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan reopened successfully. It can be edited and sent for review again.',
                'data'    => $groupLoan,
            ], 200);

        } catch (CustomerHasLiveLoanException $e) {
            return $e->toResponse();
        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to reopen group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Record the group's answer to an approved offer.
     *
     * The mirror of LoanApplicationController::respondToOffer, for the loan a
     * group borrows as one. Approving less than was asked for is an offer, and
     * the group may take it, ask for time, or refuse -- and disbursement waits
     * for that answer either way.
     *
     * One method behind three routes: they differ only in the status they land
     * on and whether a reason is required.
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

            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            $groupLoan = $this->workflowService->respondToOffer(
                $groupLoan,
                $to,
                Auth::id(),
                $request->input('remarks'),
                $request->input('decline_reason')
            );

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} offer {$to->value}", [
                'group_loan_id'  => $groupLoan->id,
                'decline_reason' => $to === LoanApplicationStatus::Declined ? $request->input('decline_reason') : null,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => match ($to) {
                    LoanApplicationStatus::OnHold   => 'Offer put on hold for the group',
                    LoanApplicationStatus::Accepted => 'Group accepted the approved offer',
                    default                         => 'Group declined the offer — the group loan has been cancelled',
                },
                'data'    => $groupLoan,
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

    /**
     * Disburse an approved group loan.
     */
    public function disburse(string $id)
    {
        try {
            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            $groupLoan = $this->workflowService->disburse($groupLoan, Auth::id());
            $groupLoan->load('loanApplication.loanApplicationCustomers.customer');

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} disbursed", [
                'group_loan_id' => $groupLoan->id,
            ]);

            $application = $groupLoan->loanApplication;

            foreach ($application?->notifiableCustomers() ?? collect() as $customer) {
                $message = 'Congratulations! Your loan has been approved and successfully disbursed. Your repayment schedule is now available.';
                $context = ['loan_application_id' => $application->id, 'customer_id' => $customer->id];

                if (!empty($customer->phone_primary)) {
                    $this->notificationService->sendSms('group_loan_disbursed', $customer->phone_primary, $message, $context);
                }

                if (!empty($customer->email)) {
                    $this->notificationService->sendEmail('group_loan_disbursed', $customer->email, 'Group Loan Disbursed', $message, $context);
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan disbursed successfully',
                'data'    => $groupLoan,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to disburse group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Cancel a group loan that has not yet been approved.
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

            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            $groupLoan = $this->workflowService->cancel($groupLoan, Auth::id(), $request->input('remarks'));

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} cancelled", [
                'group_loan_id' => $groupLoan->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan cancelled successfully',
                'data'    => $groupLoan,
            ], 200);

        } catch (InvalidLoanApplicationTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to cancel group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Soft-delete the specified group loan.
     */
    public function destroy(string $id)
    {
        try {
            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
            }

            if (!in_array($groupLoan->status, [GroupLoanStatus::Available, GroupLoanStatus::Cancelled, GroupLoanStatus::Rejected], true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "This group loan is {$groupLoan->status->value} and can no longer be deleted. Cancel it instead.",
                    'errors'  => [
                        'group_loan_id' => $groupLoan->id,
                        'status'        => $groupLoan->status->value,
                    ],
                ], 422);
            }

            $groupLoan->delete();

            $this->logActivity('DELETE', 'GroupLoan', "Deleted group loan ID: {$groupLoan->id}", [
                'group_loan_id' => $id,
                'deleted_by'    => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete group loan',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
