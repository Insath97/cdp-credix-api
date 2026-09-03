<?php

namespace App\Http\Controllers\V1;

use App\Enums\GroupLoanStatus;
use App\Enums\LoanApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGroupLoanMemberRequest;
use App\Http\Requests\UpdateGroupLoanMemberCustomerRequest;
use App\Http\Requests\UpdateGroupLoanMemberRequest;
use App\Models\Application;
use App\Models\Branch;
use App\Models\GroupLoan;
use App\Models\LoanApplication;
use App\Services\GroupLoanMemberService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Manage the members of a group loan while it is still Available.
 *
 * A "member" is one `loan_applications` row carrying `group_loan_id` +
 * `group_member_no` — there is no separate members table (see the group loan
 * design notes). Once the group loan is approved (Locked) the member list is
 * frozen: by then the approved amount has been split across exactly these
 * members and each has their own installment schedule pending.
 */
class GroupLoanMemberController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(protected GroupLoanMemberService $memberService)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Group Loan Member Index', only: ['index', 'show']),
            new Middleware('permission:Group Loan Member Create', only: ['store']),
            new Middleware('permission:Group Loan Member Update', only: ['update', 'updateCustomer']),
            new Middleware('permission:Group Loan Member Delete', only: ['destroy']),
        ];
    }

    /**
     * List the members of a group loan.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = LoanApplication::whereNotNull('group_loan_id')
                ->with(['customer.customerDetail', 'groupLoan']);

            if ($request->filled('group_loan_id')) {
                $query->where('group_loan_id', $request->group_loan_id);
            }

            $members = $query->orderBy('group_member_no')->paginate($perPage);

            $this->logActivity('Index', 'GroupLoanMember', 'Group loan members index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['group_loan_id']),
                'count'   => $members->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan members retrieved successfully',
                'data'    => $members,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve group loan members',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a single member.
     */
    public function show(string $id)
    {
        try {
            $member = $this->findMember($id, ['customer.customerDetail', 'groupLoan']);

            if (!$member) {
                return $this->memberNotFoundResponse();
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan member retrieved successfully',
                'data'    => $member,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve group loan member',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Add another member to a group loan that is still Available. The new
     * member gets their own generic `applications` row and loan application,
     * built exactly as GroupLoanController::store() builds the originals, and
     * the whole group is then re-split across the larger member count.
     */
    public function store(CreateGroupLoanMemberRequest $request)
    {
        try {
            $data = $request->validated();

            $member = DB::transaction(function () use ($data) {
                $groupLoan = GroupLoan::lockForUpdate()->find($data['group_loan_id']);

                $branchName = !empty($groupLoan->branch_id)
                    ? Branch::find($groupLoan->branch_id)?->name
                    : null;

                $application = Application::create([
                    'application_type'        => 'group_loan',
                    'branch'                  => $branchName,
                    'requested_amount'        => 0,
                    'repayment_period_months' => $groupLoan->term_months,
                ]);

                $member = $groupLoan->memberLoanApplications()->create([
                    'application_id'   => $application->id,
                    'customer_id'      => $data['customer_id'],
                    'loan_product_id'  => $groupLoan->loan_product_id,
                    'branch_id'        => $groupLoan->branch_id,
                    'group_member_no'  => $groupLoan->memberLoanApplications()->max('group_member_no') + 1,
                    'requested_amount' => 0,
                    'interest_rate'    => $groupLoan->service_charge_percentage,
                    'interest_type'    => 'flat',
                    'term_months'      => $groupLoan->term_months,
                    'applied_by'       => Auth::id(),
                    'applied_at'       => now(),
                    'status'           => LoanApplicationStatus::Submitted,
                ]);

                // resync() writes the real requested_amount for every member,
                // including the one just created with a 0 placeholder.
                $this->memberService->resync($groupLoan);

                return $member->refresh();
            });

            $this->logActivity('CREATE', 'GroupLoanMember', "Added member (loan application ID {$member->id}) to group loan ID {$member->group_loan_id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Member added to the group loan successfully',
                'data'    => $member->load(['customer.customerDetail', 'groupLoan']),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to add the member to the group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Swap which customer occupies this member slot. Amounts and member
     * numbering are untouched — only the person changes.
     */
    public function update(UpdateGroupLoanMemberRequest $request, string $id)
    {
        try {
            $member = $this->findMember($id, ['groupLoan']);

            if (!$member) {
                return $this->memberNotFoundResponse();
            }

            $previousCustomerId = $member->customer_id;
            $member->update(['customer_id' => $request->validated()['customer_id']]);

            $this->logActivity('UPDATE', 'GroupLoanMember', "Replaced customer on group loan member ID {$member->id}", [
                'group_loan_id'       => $member->group_loan_id,
                'previous_customer_id' => $previousCustomerId,
                'new_customer_id'     => $member->customer_id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan member updated successfully',
                'data'    => $member->fresh(['customer.customerDetail', 'groupLoan']),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update the group loan member',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Edit the member's personal details. These live on the shared `customers`
     * record, so the edit is refused when the customer is attached to any
     * other loan — the Customer module is the right place for that, where the
     * wider effect is visible.
     */
    public function updateCustomer(UpdateGroupLoanMemberCustomerRequest $request, string $id)
    {
        try {
            $member = $this->findMember($id, ['customer', 'groupLoan']);

            if (!$member) {
                return $this->memberNotFoundResponse();
            }

            if ($member->groupLoan->status !== GroupLoanStatus::Available) {
                return $this->membersLockedResponse($member->groupLoan, 'Member details can only be edited');
            }

            if (!$member->customer) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This member has no customer record attached, so there are no details to edit.',
                ], 422);
            }

            $otherLoanCount = LoanApplication::where('customer_id', $member->customer_id)
                ->where('id', '!=', $member->id)
                ->count();

            if ($otherLoanCount > 0) {
                $name = $member->customer->full_name ?: 'This customer';
                $loanWord = $otherLoanCount === 1 ? 'loan' : 'loans';

                return response()->json([
                    'status'  => 'error',
                    'message' => "{$name} is also on {$otherLoanCount} other {$loanWord}, so editing their details here would change those too. Edit them in the Customer module instead.",
                    'errors'  => [
                        'other_loan_count' => $otherLoanCount,
                        'customer_id'      => $member->customer_id,
                    ],
                ], 422);
            }

            $data = $request->validated();

            $detailKeys = ['gn_division', 'ds_division', 'district', 'province'];
            $detailData = array_intersect_key($data, array_flip($detailKeys));
            $customerData = array_diff_key($data, array_flip($detailKeys));

            DB::transaction(function () use ($member, $customerData, $detailData) {
                if (!empty($customerData)) {
                    $member->customer->update($customerData);
                }

                if (!empty($detailData)) {
                    $member->customer->customerDetail()->updateOrCreate([], $detailData);
                }
            });

            $this->logActivity('UPDATE', 'GroupLoanMember', "Updated customer details for group loan member ID {$member->id}", [
                'group_loan_id' => $member->group_loan_id,
                'customer_id'   => $member->customer_id,
                'fields'        => array_keys($data),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Member details updated successfully',
                'data'    => $member->fresh(['customer.customerDetail', 'groupLoan']),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update the member details',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove a member from a group loan that is still Available, then re-split
     * the requested amount across those who remain.
     */
    public function destroy(string $id)
    {
        try {
            $member = $this->findMember($id, ['groupLoan']);

            if (!$member) {
                return $this->memberNotFoundResponse();
            }

            $groupLoan = $member->groupLoan;

            if ($groupLoan->status !== GroupLoanStatus::Available) {
                return $this->membersLockedResponse($groupLoan, 'Members can only be removed');
            }

            $memberCount = $groupLoan->memberLoanApplications()->count();

            if ($memberCount <= GroupLoanMemberService::MIN_MEMBERS) {
                $min = GroupLoanMemberService::MIN_MEMBERS;

                return response()->json([
                    'status'  => 'error',
                    'message' => "A group loan must keep at least {$min} members, and this one is down to {$memberCount}. Add a replacement member first, or cancel the whole group loan.",
                ], 422);
            }

            DB::transaction(function () use ($member, $groupLoan) {
                $application = $member->application;

                $member->forceDelete();
                $application?->delete();

                $this->memberService->resync($groupLoan->refresh());
            });

            $this->logActivity('DELETE', 'GroupLoanMember', "Removed member ID {$id} from group loan ID {$groupLoan->id}", [
                'member_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Member removed from the group loan successfully',
                'data'    => $groupLoan->fresh(['memberLoanApplications.customer.customerDetail']),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to remove the member from the group loan',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Look up a loan application row that actually belongs to a group loan —
     * an individual loan's id must never resolve here.
     */
    private function findMember(string $id, array $with = []): ?LoanApplication
    {
        return LoanApplication::whereNotNull('group_loan_id')->with($with)->find($id);
    }

    private function memberNotFoundResponse()
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'That group loan member could not be found. It may already have been removed.',
        ], 404);
    }

    private function membersLockedResponse(GroupLoan $groupLoan, string $action)
    {
        return response()->json([
            'status'  => 'error',
            'message' => "This group loan is {$groupLoan->status->value} and its members are locked. {$action} while the group loan is still Available (before approval).",
            'errors'  => [
                'group_loan_id' => $groupLoan->id,
                'status'        => $groupLoan->status->value,
            ],
        ], 422);
    }
}
