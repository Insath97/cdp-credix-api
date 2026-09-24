<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RecoveryCase;
use App\Models\Customer;
use App\Models\User;
use App\Services\NotificationService;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use App\Http\Requests\CreateRecoveryCaseAgentRequest;
use App\Http\Requests\CreateRecoveryCaseRequest;
use App\Http\Requests\UpdateRecoveryCaseRequest;
use App\Services\RecoveryCaseService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RecoveryCaseController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, ScopesToUserBranch;

    public function __construct(
        protected NotificationService $notificationService,
        protected RecoveryCaseService $recoveryCaseService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Recovery Case Index',  only: ['index', 'show']),
            new Middleware('permission:Recovery Case Create', only: ['store']),
            new Middleware('permission:Recovery Case Update', only: ['update', 'assignAgent']),
            new Middleware('permission:Recovery Case Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of recovery cases.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = RecoveryCase::with([

                'customer:'.Customer::SUMMARY_COLUMNS,
                'loanApplication.customer:'.Customer::SUMMARY_COLUMNS,

                'loanApplication.application',
                'loanApplication.loanProduct',
                'loanApplication.groupLoan',
                'assignedAgent:'.User::SUMMARY_COLUMNS,

                'externalAgent',
                'openedBy:'.User::SUMMARY_COLUMNS,
                'externalAgent:id,name,phone,email',
            ]);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('assigned_agent_id')) {
                $query->where('assigned_agent_id', $request->assigned_agent_id);
            }

            // A branch officer sees their own branch's rows only;
            // the branch is reached through the parent records.
            $this->scopeToUserBranchVia($query, ['loanApplication' => 'loan_application_id', 'customer' => 'customer_id']);

            $cases = $query->orderByDesc('opened_at')->paginate($perPage);

            $this->logActivity('Index', 'RecoveryCase', 'Recovery cases index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'status', 'assigned_agent_id']),
                'count'   => $cases->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery cases retrieved successfully',
                'data'    => $cases,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve recovery cases',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created recovery case.
     */
    public function store(CreateRecoveryCaseRequest $request)
    {
        try {
            $data = $request->validated();
            $data['opened_by'] = Auth::id();
            $data['opened_at'] = $data['opened_at'] ?? now();
            $data['case_no'] = $this->recoveryCaseService->placeholderCaseNo();

            $case = RecoveryCase::create($data);
            $this->recoveryCaseService->applyCaseNo($case);

            $this->logActivity('CREATE', 'RecoveryCase', "Created recovery case ID: {$case->id} ({$case->case_no})", $data);

            $case->load(['loanApplication.application', 'loanApplication.customer:'.Customer::CONTACT_COLUMNS, 'assignedAgent.employee', 'openedBy:'.User::SUMMARY_COLUMNS]);

            if ($case->loanApplication) {
                $recoveryMessage = "Your loan account has become overdue. Please contact us immediately to avoid further recovery actions.";

                foreach ($this->caseRecipients($case) as $notifyCustomer) {
                    if (!empty($notifyCustomer->phone_primary)) {
                        $this->notificationService->sendSms(
                            'recovery_case_opened_customer',
                            $notifyCustomer->phone_primary,
                            $recoveryMessage,
                            ['loan_application_id' => $case->loan_application_id, 'customer_id' => $notifyCustomer->id]
                        );
                    }

                    if ($case->loanApplication->isJointLoan() && !empty($notifyCustomer->email)) {
                        $this->notificationService->sendEmail(
                            'recovery_case_opened_customer',
                            $notifyCustomer->email,
                            'Recovery Case Opened',
                            $recoveryMessage,
                            ['loan_application_id' => $case->loan_application_id, 'customer_id' => $notifyCustomer->id]
                        );
                    }
                }
            }

            if ($case->assignedAgent && !empty($case->assignedAgent->employee?->phone_primary)) {
                // The case number and the customer's name, not their row ids.
                // An agent cannot look a borrower up by "Customer ID: 3", and
                // the loan number is appended by NotificationService anyway.
                $agentCustomerName = $case->loanApplication?->customer?->full_name ?? 'the customer';

                $this->notificationService->sendSms(
                    'recovery_case_opened_agent',
                    $case->assignedAgent->employee->phone_primary,
                    "CDP Capital: A new overdue recovery case ({$case->case_no}) has been assigned to you. Customer: {$agentCustomerName}. Please follow up.",
                    ['loan_application_id' => $case->loan_application_id, 'user_id' => $case->assigned_agent_id]
                );
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery case created successfully',
                'data'    => $case,
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create recovery case',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Who the case itself is opened against.
     *
     * A Group Loan case names one member (customer_id), because members fall
     * behind independently and get their own case -- so only that member is
     * told their account has gone to recovery. Texting the whole group meant
     * every member was warned about somebody else's arrears, once per sibling
     * case opened. Individual and Joint loans carry no customer_id on the case
     * (the loan is the debtor), so they still notify every attached customer.
     */
    protected function caseRecipients(RecoveryCase $case): \Illuminate\Support\Collection
    {
        $loanApplication = $case->loanApplication;

        if (!$loanApplication) {
            return collect();
        }

        if ($case->customer_id) {
            $member = $loanApplication->notifiableCustomers()
                ->firstWhere('id', $case->customer_id);

            if ($member) {
                return collect([$member]);
            }
        }

        return $loanApplication->notifiableCustomers();
    }

    /**
     * Put an agent on a recovery case and tell them about it.
     *
     * Auto-created cases (the 30-day internal one and the 45-day external one)
     * are opened unassigned by design — an admin decides who works them. This
     * is the endpoint that does it: it refuses an agent that does not match the
     * case's stage, and it is the only path that notifies the agent, which the
     * generic update endpoint never did.
     */
    public function assignAgent(CreateRecoveryCaseAgentRequest $request, string $id)
    {
        try {
            $case = RecoveryCase::find($id);

            if (!$case) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'That recovery case could not be found.',
                ], 404);
            }

            if (!in_array($case->status, RecoveryCaseService::LIVE_STATUSES, true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "This case is already {$case->status} and no longer needs an agent.",
                    'errors'  => ['status' => $case->status],
                ], 422);
            }

            $data = $request->validated();

            $case->fill([
                'assigned_agent_id' => $data['assigned_agent_id'] ?? $case->assigned_agent_id,
                'external_agent_id' => $data['external_agent_id'] ?? $case->external_agent_id,
            ]);

            // Read before save(), while the original attributes are still
            // there. isDirty() rather than a === on the ids, so a validated
            // "5" against a stored 5 does not read as a change.
            $agentChanged = $case->isDirty(['assigned_agent_id', 'external_agent_id']);

            // Picking up a case is what moves it out of the untouched 'open'
            // state — nothing else in the app ever set in_progress.
            if ($case->status === 'open') {
                $case->status = 'in_progress';
            }

            if (!empty($data['remarks'])) {
                $case->remarks = trim(($case->remarks ?? '') . ' ' . $data['remarks']);
            }

            $case->save();

            // Only when the case actually changed hands. Re-saving the same
            // assignment -- an admin appending remarks, re-picking the agent
            // already on it, or a double-submitted form -- used to text that
            // agent the identical "assigned to you" SMS all over again.
            if ($agentChanged) {
                $this->recoveryCaseService->notifyAssignedAgent($case);
            }

            $this->logActivity('UPDATE', 'RecoveryCase', "Assigned an agent to recovery case {$case->case_no}", [
                'recovery_case_id'  => $case->id,
                'stage'             => $case->stage,
                'assigned_agent_id' => $case->assigned_agent_id,
                'external_agent_id' => $case->external_agent_id,
                'assigned_by'       => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Agent assigned to the recovery case successfully',
                'data'    => $case->fresh(['loanApplication.customer:'.Customer::CONTACT_COLUMNS, 'assignedAgent.employee', 'externalAgent', 'openedBy:'.User::SUMMARY_COLUMNS]),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to assign an agent to the recovery case',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified recovery case.
     */
    public function show(string $id)
    {
        try {
            $case = RecoveryCase::with(['loanApplication.customer:'.Customer::CONTACT_COLUMNS, 'loanApplication.application', 'assignedAgent:'.User::SUMMARY_COLUMNS, 'openedBy:'.User::SUMMARY_COLUMNS, 'activities.performedBy:'.User::SUMMARY_COLUMNS, 'externalAgent'])->find($id);

            if (!$case) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Recovery case not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery case retrieved successfully',
                'data'    => $case,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve recovery case',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified recovery case.
     */
    public function update(UpdateRecoveryCaseRequest $request, string $id)
    {
        try {
            $case = RecoveryCase::find($id);

            if (!$case) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Recovery case not found',
                ], 404);
            }

            $data = $request->validated();

            if (isset($data['status']) && in_array($data['status'], ['resolved', 'closed']) && empty($data['closed_at'])) {
                $data['closed_at'] = now();
            }

            $case->update($data);

            $this->logActivity('UPDATE', 'RecoveryCase', "Updated recovery case ID: {$case->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery case updated successfully',
                'data'    => $case->fresh(['loanApplication', 'assignedAgent:'.User::SUMMARY_COLUMNS, 'openedBy:'.User::SUMMARY_COLUMNS, 'externalAgent:id,name,phone,email']),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update recovery case',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified recovery case from storage.
     */
    public function destroy(string $id)
    {
        try {
            $case = RecoveryCase::find($id);

            if (!$case) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Recovery case not found',
                ], 404);
            }

            $case->delete();

            $this->logActivity('DELETE', 'RecoveryCase', "Deleted recovery case ID: {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery case deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete recovery case',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
