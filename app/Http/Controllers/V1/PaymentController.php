<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\LoanInstallment;
use App\Models\LoanRevision;
use App\Models\User;
use App\Models\Customer;
use App\Enums\LoanApplicationStatus;
use App\Enums\LoanRevisionStatus;
use App\Services\GroupLoanWorkflowService;
use App\Services\LoanApplicationWorkflowService;
use App\Services\NotificationService;
use App\Services\RecoveryCaseService;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreatePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class PaymentController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(
        protected LoanApplicationWorkflowService $workflowService,
        protected GroupLoanWorkflowService $groupLoanWorkflowService,
        protected RecoveryCaseService $recoveryCaseService,
        protected NotificationService $notificationService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Payment Index',  only: ['index', 'show']),
            new Middleware('permission:Payment Create', only: ['store']),
            new Middleware('permission:Payment Update', only: ['update']),
            new Middleware('permission:Payment Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Payment::with([
                'loanApplication.customer:' . Customer::SUMMARY_COLUMNS,
                'loanApplication.loanProduct',
                'loanApplication.branch',
                'loanApplication.application',
                'loanApplication.groupLoan',
                'loanInstallment.customer:' . Customer::SUMMARY_COLUMNS,
                'customer:' . Customer::SUMMARY_COLUMNS,
                'receivedBy:' . User::SUMMARY_COLUMNS,
            ]);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('loan_installment_id')) {
                $query->where('loan_installment_id', $request->loan_installment_id);
            }

            $payments = $query->orderByDesc('paid_at')->paginate($perPage);

            $this->logActivity('Index', 'Payment', 'Payments index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'loan_installment_id']),
                'count'   => $payments->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Payments retrieved successfully',
                'data'    => $payments,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve payments',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created payment and apply it to the installment/loan balance.
     */
    public function store(CreatePaymentRequest $request)
    {
        try {
            $data = $request->validated();

            if (!empty($data['loan_application_id'])) {
                $hasUnresolvedRevision = LoanRevision::where('loan_application_id', $data['loan_application_id'])
                    ->whereIn('status', array_map(fn ($status) => $status->value, LoanRevisionStatus::unresolved()))
                    ->exists();

                if ($hasUnresolvedRevision) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'This loan application has a revision pending approval and cannot accept new payments until it is resolved.',
                    ], 422);
                }
            }

            $payment = DB::transaction(function () use ($data) {
                $data['received_by'] = Auth::id();
                $data['paid_at'] = $data['paid_at'] ?? now();
                $data['receipt_no'] = (string) Str::uuid();

                $payment = Payment::create($data);
                $payment->receipt_no = 'RCPT-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
                $payment->save();

                $unappliedExcess = 0.0;
                if (!empty($data['loan_installment_id'])) {
                    $installment = LoanInstallment::lockForUpdate()->find($data['loan_installment_id']);

                    // A Group Loan installment already knows which member owes
                    // it, so a receipt booked against it is attributed to that
                    // member without the caller having to say so.
                    if (empty($data['customer_id']) && $installment?->customer_id) {
                        $payment->customer_id = $installment->customer_id;
                        $payment->save();
                    }

                    $unappliedExcess = $this->applyPaymentToInstallment($installment, $payment);
                }

                $loanApplication = $payment->loanApplication()->lockForUpdate()->first();
                $loanClosed = false;
                if ($loanApplication && $loanApplication->outstanding_balance !== null) {
                    $loanApplication->outstanding_balance = max(0, $loanApplication->outstanding_balance - $payment->amount);
                    $loanApplication->save();

                    // Fully repaid means no installment still owes anything.
                    // That is the truthful test — outstanding_balance is a
                    // mutable running counter that can drift (LoanRevisionService
                    // already logs warnings about exactly that). The exists()
                    // guard means a loan carrying no schedule at all can never
                    // close by accident.
                    $isFullyRepaid = $loanApplication->installments()->exists()
                        && !$loanApplication->installments()->where('balance', '>', 0)->exists();

                    // Closeable from Overdue as well as Active: a borrower who
                    // fell behind and then cleared everything must still close.
                    // LoanApplicationStatus allows Overdue -> Closed; only this
                    // condition was blocking it, which left a repaid loan stuck
                    // at Overdue and then reverted to Active with a zero balance
                    // by loans:mark-overdue — never reaching Closed at all.
                    $isRepaying = in_array($loanApplication->status, [
                        LoanApplicationStatus::Active,
                        LoanApplicationStatus::Overdue,
                    ], true);

                    if ($isFullyRepaid && $isRepaying) {
                        $loanApplication = $this->workflowService->transition(
                            $loanApplication,
                            LoanApplicationStatus::Closed,
                            Auth::id()
                        );
                        $loanClosed = true;

                        // A group loan borrows as one loan, so its header
                        // reaches Closed as soon as that loan is repaid in
                        // full. No-ops if the header is not Disbursed.
                        if ($loanApplication->group_loan_id) {
                            $groupLoan = $loanApplication->groupLoan()->lockForUpdate()->first();

                            if ($groupLoan) {
                                $this->groupLoanWorkflowService->closeIfFullyRepaid($groupLoan, Auth::id());
                            }
                        }
                    }
                }

                return [$payment, $loanApplication, $loanClosed, $unappliedExcess];
            });

            [$payment, $loanApplication, $loanClosed, $unappliedExcess] = $payment;

            $this->logActivity('CREATE', 'Payment', "Created payment ID: {$payment->id} ({$payment->receipt_no})", $data);

            if ($loanApplication) {
                // Settle any live recovery case the moment the debt is gone.
                // Done post-commit so a recovery-case write can never roll the
                // payment itself back. loans:mark-overdue also does this for a
                // borrower who merely catches up without closing the loan.
                if ($loanClosed) {
                    $this->recoveryCaseService->resolveOpenCases(
                        $loanApplication,
                        'Automatically resolved: the loan application was fully repaid and closed.'
                    );
                }

                $isJoint = $loanApplication->isJointLoan();

                foreach ($loanApplication->notifiableCustomers() as $notifyCustomer) {
                    $receivedMessage = "Payment received successfully.\nAmount: {$payment->amount}\nThank you for your payment.";

                    if (!empty($notifyCustomer->phone_primary)) {
                        $this->notificationService->sendSms(
                            'payment_received',
                            $notifyCustomer->phone_primary,
                            $receivedMessage,
                            ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                        );
                    }

                    if ($isJoint && !empty($notifyCustomer->email)) {
                        $this->notificationService->sendEmail(
                            'payment_received',
                            $notifyCustomer->email,
                            'Payment Received',
                            $receivedMessage,
                            ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                        );
                    }

                    if ($loanClosed) {
                        $closedMessage = 'Congratulations! Your loan has been successfully closed. Thank you for banking with us.';

                        if (!empty($notifyCustomer->phone_primary)) {
                            $this->notificationService->sendSms(
                                'loan_closed',
                                $notifyCustomer->phone_primary,
                                $closedMessage,
                                ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                            );
                        }

                        if ($isJoint && !empty($notifyCustomer->email)) {
                            $this->notificationService->sendEmail(
                                'loan_closed',
                                $notifyCustomer->email,
                                'Loan Closed',
                                $closedMessage,
                                ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                            );
                        }
                    }
                }
            }

            return response()->json([
                'status'          => 'success',
                'message'         => 'Payment recorded successfully',
                'data'            => $payment->load(['loanApplication', 'loanInstallment', 'receivedBy:'.User::SUMMARY_COLUMNS]),
                'unapplied_excess' => $unappliedExcess > 0 ? $unappliedExcess : null,
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to record payment',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified payment (acts as the receipt).
     */
    public function show(string $id)
    {
        try {
            $payment = Payment::with([
                'loanApplication.customer:' . Customer::SUMMARY_COLUMNS,
                'loanApplication.loanProduct',
                'loanApplication.branch',
                'loanApplication.application',
                'loanApplication.groupLoan',
                'loanInstallment.customer:' . Customer::SUMMARY_COLUMNS,
                'customer:' . Customer::SUMMARY_COLUMNS,
                'receivedBy:' . User::SUMMARY_COLUMNS,
            ])->find($id);

            if (!$payment) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Payment not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Payment retrieved successfully',
                'data'    => $payment,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve payment',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified payment's metadata (method/remarks/paid_at only).
     */
    public function update(UpdatePaymentRequest $request, string $id)
    {
        try {
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Payment not found',
                ], 404);
            }

            $data = $request->validated();
            $payment->update($data);

            $this->logActivity('UPDATE', 'Payment', "Updated payment ID: {$payment->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Payment updated successfully',
                'data'    => $payment->fresh(['loanApplication', 'loanInstallment', 'receivedBy:'.User::SUMMARY_COLUMNS]),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update payment',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified payment and reverse its effect on the installment/loan balance.
     */
    public function destroy(string $id)
    {
        try {
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Payment not found',
                ], 404);
            }

            DB::transaction(function () use ($payment) {
                $carriedForward = collect($payment->carry_forward_breakdown ?? [])->sum('amount');

                if ($payment->loan_installment_id) {
                    $installment = LoanInstallment::lockForUpdate()->find($payment->loan_installment_id);
                    if ($installment) {
                        // Only reverse what actually landed on this installment --
                        // the rest was carried forward and is reversed separately below.
                        $appliedHere = $payment->amount - $carriedForward;
                        $installment->amount_paid = max(0, $installment->amount_paid - $appliedHere);
                        $installment->recalculateBalance();
                        $installment->status = $installment->amount_paid <= 0
                            ? 'upcoming'
                            : ($installment->balance <= 0 ? 'paid' : 'partially_paid');
                        if ($installment->status !== 'paid') {
                            $installment->paid_at = null;
                        }
                        $installment->save();
                    }
                }

                foreach ($payment->carry_forward_breakdown ?? [] as $entry) {
                    $target = LoanInstallment::lockForUpdate()->find($entry['loan_installment_id']);
                    if (!$target) {
                        continue;
                    }
                    $target->amount_paid = max(0, $target->amount_paid - $entry['amount']);
                    $target->recalculateBalance();
                    $target->status = $target->amount_paid <= 0
                        ? 'upcoming'
                        : ($target->balance <= 0 ? 'paid' : 'partially_paid');
                    if ($target->status !== 'paid') {
                        $target->paid_at = null;
                    }
                    $target->save();
                }

                $loanApplication = $payment->loanApplication()->lockForUpdate()->first();
                if ($loanApplication && $loanApplication->outstanding_balance !== null) {
                    $loanApplication->outstanding_balance += $payment->amount;
                    $loanApplication->save();
                }

                $payment->delete();
            });

            $this->logActivity('DELETE', 'Payment', "Deleted payment ID: {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Payment deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete payment',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply a payment to its targeted installment, capping the amount
     * actually credited there at what the installment still owed, and
     * carrying any excess forward onto the loan's subsequent installments.
     *
     * @return float Any excess that could not be applied anywhere (the
     *                customer overpaid beyond the entire remaining loan).
     */
    private function applyPaymentToInstallment(LoanInstallment $installment, Payment $payment): float
    {
        $incoming = (float) $payment->amount;
        $preBalance = (float) $installment->balance;

        $appliedHere = min($incoming, $preBalance);
        $excess = round($incoming - $appliedHere, 2);

        $installment->amount_paid += $appliedHere;
        $installment->recalculateBalance();
        $installment->status = $installment->balance <= 0 ? 'paid' : 'partially_paid';
        if ($installment->balance <= 0) {
            $installment->paid_at = $payment->paid_at;
        }
        $installment->save();

        if ($excess > 0) {
            return $this->carryForwardExcess($installment, $payment, $excess);
        }

        return 0.0;
    }

    /**
     * Walk forward through the loan's remaining unpaid/live installments in
     * installment_no order, applying as much of the excess as each one's own
     * (pre-existing) balance can absorb, until the excess is exhausted or
     * there are no more eligible installments. Records what was carried
     * forward on the Payment row itself, for audit and reversal purposes.
     *
     * @return float Any excess that could not be applied anywhere.
     */
    private function carryForwardExcess(LoanInstallment $fromInstallment, Payment $payment, float $excess): float
    {
        $breakdown = [];
        $notes = [];

        $candidates = LoanInstallment::where('loan_application_id', $fromInstallment->loan_application_id)
            // Stay inside this member's own installments. A Group Loan gives
            // every member their own rows, so without this scope one member's
            // overpayment would silently pay down another member's balance.
            // Rows predating per-member installments carry a null customer_id
            // and keep the original loan-wide behaviour.
            ->when(
                $fromInstallment->customer_id !== null,
                fn ($query) => $query->where('customer_id', $fromInstallment->customer_id)
            )
            ->where('installment_no', '>', $fromInstallment->installment_no)
            ->whereNotIn('status', LoanInstallment::SETTLED_STATUSES)
            ->orderBy('installment_no')
            ->lockForUpdate()
            ->get();

        foreach ($candidates as $next) {
            if ($excess <= 0) {
                break;
            }

            $portion = min($excess, (float) $next->balance);
            if ($portion <= 0) {
                continue;
            }

            $next->amount_paid += $portion;
            $next->recalculateBalance();
            $next->status = $next->balance <= 0 ? 'paid' : 'partially_paid';
            if ($next->balance <= 0) {
                $next->paid_at = $payment->paid_at;
            }
            $next->save();

            $breakdown[] = ['loan_installment_id' => $next->id, 'installment_no' => $next->installment_no, 'amount' => $portion];
            $notes[] = "{$portion} carried forward to installment #{$next->installment_no}";
            $excess = round($excess - $portion, 2);
        }

        if (!empty($breakdown)) {
            $payment->carry_forward_breakdown = $breakdown;
            $payment->remarks = trim(($payment->remarks ? $payment->remarks . ' ' : '') . 'Includes ' . implode(', ', $notes) . '.');
        }

        if ($excess > 0) {
            $payment->remarks = trim(($payment->remarks ? $payment->remarks . ' ' : '') . "Overpayment of {$excess} could not be applied to any installment.");
        }

        if (!empty($breakdown) || $excess > 0) {
            $payment->save();
        }

        return $excess;
    }
}
