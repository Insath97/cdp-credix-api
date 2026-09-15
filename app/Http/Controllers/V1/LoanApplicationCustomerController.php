<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\LoanApplication;
use App\Models\LoanApplicationCustomer;
use App\Models\Customer;
use App\Enums\GroupLoanStatus;
use App\Enums\LoanApplicationStatus;
use App\Services\GroupLoanWorkflowService;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateLoanApplicationCustomerRequest;
use App\Http\Requests\UpdateLoanApplicationCustomerDetailsRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Manage the customers attached to a loan application.
 *
 * This one resource covers both Joint Loan co-borrowers and Group Loan
 * members — they are the same mechanism, one `loan_application_customers` row
 * per person against a single loan application. The two differ only in when
 * the list may be changed: a joint loan's while it is still Submitted, a group
 * loan's while the group loan is still Available (before approval).
 */
class LoanApplicationCustomerController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(protected GroupLoanWorkflowService $groupLoanWorkflowService)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Customer Index',  only: ['index', 'show']),
            new Middleware('permission:Loan Application Customer Create', only: ['store']),
            new Middleware('permission:Loan Application Customer Update', only: ['updateCustomerDetails']),
            new Middleware('permission:Loan Application Customer Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of loan application customers (Joint Loan co-borrowers
     * and Group Loan members).
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanApplicationCustomer::with(['loanApplication', 'customer:'.Customer::SUMMARY_COLUMNS]);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            $records = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanApplicationCustomer', 'Loan application customers index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id']),
                'count'   => $records->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application customers retrieved successfully',
                'data'    => $records,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application customers',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach a customer to a loan application — a co-borrower, turning an
     * individual loan into a Joint Loan, or another member of a Group Loan.
     */
    public function store(CreateLoanApplicationCustomerRequest $request)
    {
        try {
            $data = $request->validated();

            $record = DB::transaction(function () use ($data) {
                $loanApplication = LoanApplication::with('groupLoan')->find($data['loan_application_id']);

                // First co-borrower ever added: bring the loan's own primary customer
                // into the pivot too, so it always holds the complete borrower set.
                if ($loanApplication && $loanApplication->loanApplicationCustomers()->count() === 0) {
                    LoanApplicationCustomer::create([
                        'loan_application_id' => $loanApplication->id,
                        'customer_id'         => $loanApplication->customer_id,
                    ]);
                }

                $record = LoanApplicationCustomer::create($data);

                // A group loan's header tracks how many members it has.
                if ($loanApplication?->groupLoan) {
                    $this->groupLoanWorkflowService->syncAmounts($loanApplication->groupLoan);
                }

                return $record;
            });

            $this->logActivity('CREATE', 'LoanApplicationCustomer', "Added customer ID {$record->customer_id} to loan application ID {$record->loan_application_id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Customer added to loan application successfully',
                'data'    => $record->load(['loanApplication', 'customer:'.Customer::SUMMARY_COLUMNS]),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to add customer to loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan application customer.
     */
    public function show(string $id)
    {
        try {
            $record = LoanApplicationCustomer::with(['loanApplication', 'customer:'.Customer::SUMMARY_COLUMNS])->find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application customer not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application customer retrieved successfully',
                'data'    => $record,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application customer',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Edit an attached customer's personal details.
     *
     * These fields live on the shared `customers` record, so the edit is
     * refused when that customer is attached to any other loan — the Customer
     * module is the right place for that, where the wider effect is visible.
     */
    public function updateCustomerDetails(UpdateLoanApplicationCustomerDetailsRequest $request, string $id)
    {
        try {
            $record = LoanApplicationCustomer::with(['customer', 'loanApplication.groupLoan'])->find($id);

            if (!$record) {
                return $this->notFoundResponse();
            }

            $loanApplication = $record->loanApplication;

            if ($locked = $this->membersLockedResponse($loanApplication, 'Member details can only be edited')) {
                return $locked;
            }

            if (!$record->customer) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This member has no customer record attached, so there are no details to edit.',
                ], 422);
            }

            $otherLoanCount = LoanApplication::forCustomer($record->customer_id)
                ->where('id', '!=', $record->loan_application_id)
                ->count();

            if ($otherLoanCount > 0) {
                $name = $record->customer->full_name ?: 'This customer';
                $loanWord = $otherLoanCount === 1 ? 'loan' : 'loans';

                return response()->json([
                    'status'  => 'error',
                    'message' => "{$name} is also on {$otherLoanCount} other {$loanWord}, so editing their details here would change those too. Edit them in the Customer module instead.",
                    'errors'  => [
                        'other_loan_count' => $otherLoanCount,
                        'customer_id'      => $record->customer_id,
                    ],
                ], 422);
            }

            $data = $request->validated();

            $detailKeys = ['gn_division', 'ds_division', 'district', 'province'];
            $detailData = array_intersect_key($data, array_flip($detailKeys));
            $customerData = array_diff_key($data, array_flip($detailKeys));

            DB::transaction(function () use ($record, $customerData, $detailData) {
                if (!empty($customerData)) {
                    $record->customer->update($customerData);
                }

                if (!empty($detailData)) {
                    $record->customer->customerDetail()->updateOrCreate([], $detailData);
                }
            });

            $this->logActivity('UPDATE', 'LoanApplicationCustomer', "Updated customer details for loan application customer ID {$record->id}", [
                'loan_application_id' => $record->loan_application_id,
                'customer_id'         => $record->customer_id,
                'fields'              => array_keys($data),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Member details updated successfully',
                'data'    => $record->fresh(['customer.customerDetail', 'loanApplication.groupLoan']),
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
     * Remove a co-borrower or group member from the loan application.
     */
    public function destroy(string $id)
    {
        try {
            $record = LoanApplicationCustomer::with('loanApplication.groupLoan')->find($id);

            if (!$record) {
                return $this->notFoundResponse();
            }

            $loanApplication = $record->loanApplication;
            $groupLoan = $loanApplication?->groupLoan;

            if ($locked = $this->membersLockedResponse($loanApplication, 'Members can only be removed')) {
                return $locked;
            }

            // A group loan must keep enough members to still be a group.
            if ($groupLoan) {
                $memberCount = $loanApplication->loanApplicationCustomers()->count();

                if ($memberCount <= GroupLoanWorkflowService::MIN_MEMBERS) {
                    $min = GroupLoanWorkflowService::MIN_MEMBERS;

                    return response()->json([
                        'status'  => 'error',
                        'message' => "A group loan must keep at least {$min} members, and this one is down to {$memberCount}. Add a replacement member first, or cancel the whole group loan.",
                    ], 422);
                }
            }

            DB::transaction(function () use ($record, $loanApplication, $groupLoan) {
                $record->delete();

                // The removed person may have been the primary applicant on the
                // row itself; promote a remaining member so the loan never
                // points at someone who is no longer on it.
                if ($loanApplication && (int) $loanApplication->customer_id === (int) $record->customer_id) {
                    $replacement = $loanApplication->loanApplicationCustomers()->value('customer_id');

                    if ($replacement) {
                        $loanApplication->update(['customer_id' => $replacement]);
                    }
                }

                if ($groupLoan) {
                    $this->groupLoanWorkflowService->syncAmounts($groupLoan);
                }
            });

            $this->logActivity('DELETE', 'LoanApplicationCustomer', "Removed customer ID {$record->customer_id} from loan application ID {$record->loan_application_id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Customer removed from loan application successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to remove customer from loan application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    private function notFoundResponse()
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'Loan application customer not found',
        ], 404);
    }

    /**
     * The shared "is this list still editable?" gate.
     *
     * A Group Loan's members may be added, removed and edited only while the
     * group loan is Available — once approved (Locked) the list is frozen,
     * because by then the approved amount has been split across exactly these
     * members. A Joint Loan's co-borrowers keep the original Submitted rule.
     *
     * Returns a 422 response when the change is not allowed, or null when it is.
     */
    private function membersLockedResponse(?LoanApplication $loanApplication, string $action)
    {
        if (!$loanApplication) {
            return null;
        }

        $groupLoan = $loanApplication->groupLoan;

        if ($groupLoan) {
            if ($groupLoan->status === GroupLoanStatus::Available) {
                return null;
            }

            return response()->json([
                'status'  => 'error',
                'message' => "This group loan is {$groupLoan->status->value} and its members are locked. {$action} while the group loan is still Available (before approval).",
                'errors'  => [
                    'group_loan_id' => $groupLoan->id,
                    'status'        => $groupLoan->status->value,
                ],
            ], 422);
        }

        if ($loanApplication->status !== LoanApplicationStatus::Submitted) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Customers can only be changed while the loan application is in Submitted status.',
            ], 422);
        }

        return null;
    }
}
