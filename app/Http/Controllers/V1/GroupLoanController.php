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
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateGroupLoanRequest;
use App\Http\Requests\UpdateGroupLoanRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
            new Middleware('permission:Group Loan Index', only: ['index', 'show']),
            new Middleware('permission:Group Loan Create', only: ['store']),
            new Middleware('permission:Group Loan Update', only: ['update']),
            new Middleware('permission:Group Loan Toggle Status', only: ['toggleStatus', 'activate', 'deactivate']),
            new Middleware('permission:Group Loan Delete', only: ['destroy']),
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
                'appliedByUser',
                'reviewedByUser',
                'approvedByUser',
            ]);

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $groupLoans = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'GroupLoan', 'Group loans index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'is_active']),
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
     * Store a newly created group loan, along with its product/material line
     * items and one loan application per member — all created together in a
     * single request, since neither has a separate reusable catalog/picker.
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

                // Group Loan uses a service charge in place of interest,
                // snapshotted from System Settings at submission time rather
                // than taken as an officer-entered figure.
                $serviceChargePercentage = (float) Setting::get('group_loan_service_charge_percentage', 0);

                $groupLoan = GroupLoan::create([
                    'loan_product_id'           => $data['loan_product_id'],
                    'branch_id'                 => $data['branch_id'] ?? null,
                    'group_name'                => $data['group_name'],
                    'number_of_members'         => $data['number_of_members'],
                    'competency'                => $data['competency'],
                    'requested_amount'          => $requestedAmount,
                    'service_charge_percentage' => $serviceChargePercentage,
                    'term_months'               => $data['term_months'],
                    'applied_by'                => Auth::id(),
                    'applied_at'                => now(),
                    'status'                    => LoanApplicationStatus::Submitted,
                ]);

                foreach ($items as $item) {
                    $groupLoan->items()->create($item);
                }

                $branchName = !empty($data['branch_id'])
                    ? Branch::find($data['branch_id'])?->name
                    : null;

                $memberCount = count($data['members']);
                $memberShare = round($requestedAmount / $memberCount, 2);
                $runningTotal = 0;

                foreach ($data['members'] as $index => $member) {
                    $isLast = $index === $memberCount - 1;
                    $memberRequestedAmount = $isLast
                        ? round($requestedAmount - $runningTotal, 2)
                        : $memberShare;
                    $runningTotal += $memberRequestedAmount;

                    $application = Application::create([
                        'application_type'        => 'group_loan',
                        'branch'                   => $branchName,
                        'requested_amount'         => $memberRequestedAmount,
                        'repayment_period_months'  => $data['term_months'],
                    ]);

                    // loan_applications.interest_rate is a shared, required
                    // column also used by Individual Loan — for a group-loan
                    // member row it holds the service charge percentage, so
                    // the existing per-loan interest formula (reused
                    // unmodified in GroupLoanWorkflowService::approve())
                    // naturally computes the service charge amount.
                    $memberLoanApplication = $groupLoan->memberLoanApplications()->create([
                        'application_id'    => $application->id,
                        'customer_id'       => $member['customer_id'] ?? null,
                        'loan_product_id'   => $data['loan_product_id'],
                        'branch_id'         => $data['branch_id'] ?? null,
                        'group_member_no'   => $index + 1,
                        'requested_amount'  => $memberRequestedAmount,
                        'interest_rate'     => $serviceChargePercentage,
                        'interest_type'     => 'flat',
                        'term_months'       => $data['term_months'],
                        'applied_by'        => Auth::id(),
                        'applied_at'        => now(),
                        'status'            => LoanApplicationStatus::Submitted,
                    ]);

                    $groupLoan->members()->create([
                        'loan_application_id' => $memberLoanApplication->id,
                        'customer_id'         => $member['customer_id'] ?? null,
                        'member_name'         => $member['member_name'],
                        'nic'                 => $member['nic'],
                        'address'             => $member['address'],
                        'phone_number'        => $member['phone_number'],
                        'gn_division'         => $member['gn_division'],
                        'ds_division'         => $member['ds_division'],
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
                            'CDP Crdix: New group loan application pending for review.',
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
                    'memberLoanApplications.customer',
                    'members',
                    'appliedByUser',
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
                'members',
                'memberLoanApplications.customer',
                'memberLoanApplications.installments',
                'memberLoanApplications.statusHistory.changedBy',
                'appliedByUser',
                'reviewedByUser',
                'approvedByUser',
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
                'data'    => $groupLoan,
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
     * Update the specified group loan's header fields. Items/members are
     * immutable after creation — cancel and recreate to fix a mistake.
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

            $data = $request->validated();

            $groupLoan->update($data);

            $this->logActivity('UPDATE', 'GroupLoan', "Updated group loan ID: {$groupLoan->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan updated successfully',
                'data'    => $groupLoan->fresh([
                    'loanProduct',
                    'branch',
                    'appliedByUser',
                    'reviewedByUser',
                    'approvedByUser',
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
            $groupLoan->load('members');

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} rejected", [
                'group_loan_id' => $groupLoan->id,
            ]);

            foreach ($groupLoan->members as $member) {
                if (!empty($member->phone_number)) {
                    $this->notificationService->sendSms(
                        'group_loan_rejected',
                        $member->phone_number,
                        "Your group loan application has been rejected.\nReason: {$groupLoan->rejection_reason}",
                        ['loan_application_id' => $member->loan_application_id, 'customer_id' => $member->customer_id]
                    );
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
            $groupLoan->load('members');

            $this->logActivity('UPDATE', 'GroupLoan', "Group loan ID: {$groupLoan->id} disbursed", [
                'group_loan_id' => $groupLoan->id,
            ]);

            foreach ($groupLoan->members as $member) {
                if (!empty($member->phone_number)) {
                    $this->notificationService->sendSms(
                        'group_loan_disbursed',
                        $member->phone_number,
                        'Congratulations! Your loan has been approved and successfully disbursed. Your repayment schedule is now available.',
                        ['loan_application_id' => $member->loan_application_id, 'customer_id' => $member->customer_id]
                    );
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
