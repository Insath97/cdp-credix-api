<?php

namespace App\Http\Controllers\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\ResolvesAuthenticatedCustomerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerPaymentController extends Controller
{
    use ActivityLogTrait, ResolvesAuthenticatedCustomerTrait;

    /**
     * List the customer's own payment history.
     *
     * An optional loan_application_id filter is re-guarded against
     * ownership below — it is never trusted blindly, so a mismatched id
     * simply yields an empty list rather than another customer's payments.
     */
    public function index(Request $request)
    {
        try {
            $customerId = $this->myCustomerId();
            // Clamped through the base controller: an unclamped per_page lets any
            // caller force a 500. A negative value is truthy, so nothing replaced
            // it, and the query kept the OFFSET while dropping the LIMIT.
            $perPage = $this->perPage($request);

            $query = Payment::whereHas('loanApplication', function ($q) use ($customerId) {
                $q->forCustomer($customerId);
            })->with(['loanApplication.application', 'loanInstallment']);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id)
                    ->whereHas('loanApplication', function ($q) use ($customerId) {
                        $q->forCustomer($customerId);
                    });
            }

            $payments = $query->orderByDesc('paid_at')->paginate($perPage);
            $payments->getCollection()->transform(fn ($payment) => $this->shapePayment($payment));

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed payment history', [
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payments retrieved successfully',
                'data' => $payments,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payments',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Show a single payment (acts as the digital receipt) belonging to the customer.
     */
    public function show(string $id)
    {
        try {
            $customerId = $this->myCustomerId();

            $payment = Payment::where('id', $id)
                ->whereHas('loanApplication', function ($q) use ($customerId) {
                    $q->forCustomer($customerId);
                })
                ->with(['loanApplication.application', 'loanInstallment', 'receivedBy:'.User::SUMMARY_COLUMNS])
                ->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment not found',
                ], 404);
            }

            $data = $this->shapePayment($payment);
            $data['remarks'] = $payment->remarks;
            $data['received_by'] = $payment->receivedBy?->name;

            return response()->json([
                'status' => 'success',
                'message' => 'Payment retrieved successfully',
                'data' => $data,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Shape a payment into the "Payment History" section response.
     *
     * payment_status is hard-coded "completed" since the payments table has
     * no status column — a Payment row is only ever created after money is
     * confirmed received.
     */
    protected function shapePayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'receipt_no' => $payment->receipt_no,
            'paid_at' => $payment->paid_at,
            'loan_number' => $payment->loanApplication?->application?->application_no,
            'installment_no' => $payment->loanInstallment?->installment_no,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'payment_status' => 'completed',
        ];
    }
}
