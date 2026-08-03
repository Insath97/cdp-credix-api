<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\LoanInstallment;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreatePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class PaymentController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

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
            $query = Payment::with(['loanApplication', 'loanInstallment', 'receivedBy']);

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

            $payment = DB::transaction(function () use ($data) {
                $data['received_by'] = Auth::id();
                $data['paid_at'] = $data['paid_at'] ?? now();
                $data['receipt_no'] = (string) Str::uuid();

                $payment = Payment::create($data);
                $payment->receipt_no = 'RCPT-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
                $payment->save();

                if (!empty($data['loan_installment_id'])) {
                    $installment = LoanInstallment::lockForUpdate()->find($data['loan_installment_id']);
                    $installment->amount_paid += $payment->amount;
                    $installment->balance = max(0, $installment->amount_due - $installment->amount_paid);
                    $installment->status = $installment->balance <= 0 ? 'paid' : 'partially_paid';
                    if ($installment->balance <= 0) {
                        $installment->paid_at = $payment->paid_at;
                    }
                    $installment->save();
                }

                $loanApplication = $payment->loanApplication()->lockForUpdate()->first();
                if ($loanApplication && $loanApplication->outstanding_balance !== null) {
                    $loanApplication->outstanding_balance = max(0, $loanApplication->outstanding_balance - $payment->amount);
                    $loanApplication->save();
                }

                return $payment;
            });

            $this->logActivity('CREATE', 'Payment', "Created payment ID: {$payment->id} ({$payment->receipt_no})", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Payment recorded successfully',
                'data'    => $payment->load(['loanApplication', 'loanInstallment', 'receivedBy']),
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
            $payment = Payment::with(['loanApplication', 'loanInstallment', 'receivedBy'])->find($id);

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
                'data'    => $payment->fresh(['loanApplication', 'loanInstallment', 'receivedBy']),
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
                if ($payment->loan_installment_id) {
                    $installment = LoanInstallment::lockForUpdate()->find($payment->loan_installment_id);
                    if ($installment) {
                        $installment->amount_paid = max(0, $installment->amount_paid - $payment->amount);
                        $installment->balance = $installment->amount_due - $installment->amount_paid;
                        $installment->status = $installment->amount_paid <= 0
                            ? 'upcoming'
                            : ($installment->balance <= 0 ? 'paid' : 'partially_paid');
                        if ($installment->status !== 'paid') {
                            $installment->paid_at = null;
                        }
                        $installment->save();
                    }
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
}
