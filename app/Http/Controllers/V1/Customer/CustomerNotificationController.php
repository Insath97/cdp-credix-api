<?php

namespace App\Http\Controllers\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Traits\ActivityLogTrait;
use App\Traits\ResolvesAuthenticatedCustomerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerNotificationController extends Controller
{
    use ActivityLogTrait, ResolvesAuthenticatedCustomerTrait;

    /**
     * List the customer's own notifications (read-only window into the
     * existing notifications log — no new notifications are created here,
     * and no read/unread tracking, per plan decisions).
     */
    public function index(Request $request)
    {
        try {
            $customerId = $this->myCustomerId();
            // Clamped through the base controller: an unclamped per_page lets any
            // caller force a 500. A negative value is truthy, so nothing replaced
            // it, and the query kept the OFFSET while dropping the LIMIT.
            $perPage = $this->perPage($request);

            $query = Notification::where('customer_id', $customerId);

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $notifications = $query->orderByDesc('id')
                ->paginate($perPage)
                ->through(fn ($notification) => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'channel' => $notification->channel,
                    'subject' => $notification->subject,
                    'message' => $notification->message,
                    'status' => $notification->status,
                    'sent_at' => $notification->sent_at,
                    'created_at' => $notification->created_at,
                ]);

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed notifications', [
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Notifications retrieved successfully',
                'data' => $notifications,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve notifications',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
