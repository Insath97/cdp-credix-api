<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Models\Customer;
use App\Traits\ActivityLogTrait;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class NotificationController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Notification Index', only: ['index', 'show']),
        ];
    }

    /**
     * Display a listing of notifications.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Notification::with(['loanApplication', 'customer:'.Customer::SUMMARY_COLUMNS, 'user']);

            // ?mine=1 -- the ones addressed to the signed-in user, which is what
            // the header bell wants. Without it this endpoint answers with the
            // whole outbound log: every SMS and email sent to every customer,
            // recipient numbers and addresses included. That is a delivery log
            // for an administrator to read, not somebody's inbox.
            if ($request->boolean('mine')) {
                $query->where('user_id', Auth::id());
            }

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $notifications = $query->orderByDesc('id')->paginate($perPage);

            $this->logActivity('Index', 'Notification', 'Notifications index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['mine', 'loan_application_id', 'customer_id', 'type', 'status']),
                'count'   => $notifications->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Notifications retrieved successfully',
                'data'    => $notifications,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve notifications',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified notification.
     */
    public function show(string $id)
    {
        try {
            $notification = Notification::with(['loanApplication', 'customer:'.Customer::SUMMARY_COLUMNS, 'user'])->find($id);

            if (!$notification) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Notification not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Notification retrieved successfully',
                'data'    => $notification,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve notification',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
