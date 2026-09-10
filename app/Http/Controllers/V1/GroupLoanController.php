<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Application;
use App\Models\Branch;
use App\Models\GroupLoan;
use App\Models\Setting;
use App\Models\Customer;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateGroupLoanRequest;
use App\Http\Requests\UpdateGroupLoanRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Enums\GroupLoanStatus;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Services\GroupLoanWorkflowService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class GroupLoanController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(
        protected GroupLoanWorkflowService $workflowService,
        protected NotificationService $notificationService,
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
            new Middleware('permission:Group Loan Review', only: ['review']),
            new Middleware('permission:Group Loan Verify', only: ['verify']),
            new Middleware('permission:Group Loan Approve', only: ['approve']),
            new Middleware('permission:Group Loan Reject', only: ['reject']),
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
            $counts = GroupLoan::query()
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

            $groupLoan = DB::transaction(function () use ($data) {
                $items = collect($data['items'])->map(function ($item) {
                    $item['line_total'] = round($item['quantity'] * $item['unit_price'], 2);
                    return $item;
                });

                // The loan amount is always backend-computed from the
                // requested items — never trusted from the client.
                $requestedAmount = round($items->sum('line_total'), 2);

                // Group Loan uses a service charge in place of interest, always
                // taken from System Settings — there is no per-application
                // override, and no interest rate is involved at any point.
                $serviceChargePercentage = (float) Setting::get('group_loan_service_charge_percentage', 0);

                $memberCustomerIds = collect($data['members'])
                    ->pluck('customer_id')
                    ->filter()
                    ->unique()
                    ->values();

                $groupLoan = GroupLoan::create([
                    'loan_product_id'           => $data['loan_product_id'],
                    'branch_id'                 => $data['branch_id'] ?? null,
                    'group_name'                => $data['group_name'],
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
                    // A Group Loan has no interest rate. Repayment is derived
                    // solely from the group's service charge percentage, which
                    // lives on the group_loans header.
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

                foreach ($memberCustomerIds as $customerId) {
                    $loanApplication->loanApplicationCustomers()->create([
                        'customer_id' => $customerId,
                    ]);
                }

                return $groupLoan;
            });

            $this->logActivity('CREATE', 'GroupLoan', "Created group loan ID: {$groupLoan->id}", $data);

            $staffRole = Role::where('name', config('notifications.staff_role'))->first();
            if ($staffRole) {
                foreach ($staffRole->users as $staffUser) {
                    $staffPhone = $staffUser->employee?->phone_primary;
                    if (!empty($staffPhone)) {
                        $this->notificationService->sendSms(
                            'group_loan_submitted',
                            $staffPhone,
                            'CDP Credix: New group loan application pending for review.',
                            ['user_id' => $staffUser->id]
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
                    'appliedByUser:'.User::SUMMARY_COLUMNS,
                ]),
            ], 201);

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
                // `members` carries each member's own share of the group's
                // money — including their individual monthly installment —
                // derived from the single loan application, never stored.
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

            // Editable only while Available, for the same reason the member
            // list is frozen after approval: once the group loan is Locked the
            // approved amount has been split across exactly these members on
            // exactly these terms, and rewriting the header afterwards would
            // silently disagree with the per-member loan applications and the
            // schedules already generated from them.
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

            // Toggling a finished group loan back on would show it as "Active".
            // Only block turning it ON — turning it off is always fine.
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
     */
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

            $groupLoan = GroupLoan::find($id);

            if (!$groupLoan) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan not found',
                ], 404);
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

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} verified", [
                'group_loan_id' => $groupLoan->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan verified successfully',
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
                'message' => 'Failed to verify group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Review a submitted group loan — the first of the three hands before
     * approval. Records who reviewed it and cascades the stage to the group's
     * single loan application; the header itself stays Available.
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

            // Same ceiling as Individual Loan: a group can be approved for
            // less than it requested, never more. The requested amount is
            // itself computed from the group's items, so approving above it
            // would lend money against nothing.
            $validator = Validator::make($request->all(), [
                'approved_amount' => [
                    'nullable',
                    'numeric',
                    'min:0.01',
                    'max:' . (float) $groupLoan->requested_amount,
                ],
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
