<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\GroupLoanStatus;
use App\Models\GroupLoan;
use App\Models\GroupLoanItem;
use App\Services\GroupLoanMemberService;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateGroupLoanItemRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class GroupLoanItemController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(protected GroupLoanMemberService $memberService)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Group Loan Item Index', only: ['index', 'show']),
            new Middleware('permission:Group Loan Item Create', only: ['store']),
            new Middleware('permission:Group Loan Item Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of group loan items.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = GroupLoanItem::with('groupLoan');

            if ($request->has('group_loan_id')) {
                $query->where('group_loan_id', $request->group_loan_id);
            }

            $items = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'GroupLoanItem', 'Group loan items index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['group_loan_id']),
                'count'   => $items->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan items retrieved successfully',
                'data'    => $items,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve group loan items',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a new product/material line item to a group loan still in
     * Submitted status, and recalculate the group loan's requested_amount
     * from its items — the loan amount is always backend-computed, never
     * trusted from the client.
     */
    public function store(CreateGroupLoanItemRequest $request)
    {
        try {
            $data = $request->validated();
            $data['line_total'] = round($data['quantity'] * $data['unit_price'], 2);

            $item = DB::transaction(function () use ($data) {
                $item = GroupLoanItem::create($data);
                $this->recalculateRequestedAmount($data['group_loan_id']);
                return $item;
            });

            $this->logActivity('CREATE', 'GroupLoanItem', "Added item '{$item->item_name}' to group loan ID {$item->group_loan_id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan item added successfully',
                'data'    => $item->load('groupLoan'),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to add group loan item',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified group loan item.
     */
    public function show(string $id)
    {
        try {
            $item = GroupLoanItem::with('groupLoan')->find($id);

            if (!$item) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan item not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan item retrieved successfully',
                'data'    => $item,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve group loan item',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a line item from a group loan still in Submitted status, and
     * recalculate the group loan's requested_amount from what remains.
     */
    public function destroy(string $id)
    {
        try {
            $item = GroupLoanItem::find($id);

            if (!$item) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Group loan item not found',
                ], 404);
            }

            $groupLoan = $item->groupLoan;

            if ($groupLoan->status !== GroupLoanStatus::Available) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "This group loan is {$groupLoan->status->value} and can no longer be changed. Items can only be removed while it is still Available (before approval).",
                ], 422);
            }

            if ($groupLoan->items()->count() <= 1) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'A group loan must keep at least one item. To drop this one, add its replacement first, or cancel the whole group loan.',
                ], 422);
            }

            DB::transaction(function () use ($item, $groupLoan) {
                $item->delete();
                $this->recalculateRequestedAmount($groupLoan->id);
            });

            $this->logActivity('DELETE', 'GroupLoanItem', "Removed item ID {$id} from group loan ID {$groupLoan->id}", [
                'item_id'    => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Group loan item removed successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to remove group loan item',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Recompute a group loan's requested_amount as the sum of its current
     * items' line totals, keeping it always in sync with what was actually
     * selected, then re-split that new total across the members — otherwise
     * each member's own requested_amount would still hold the share of the
     * pre-edit total.
     */
    private function recalculateRequestedAmount(int $groupLoanId): void
    {
        $groupLoan = GroupLoan::lockForUpdate()->find($groupLoanId);
        $total = round($groupLoan->items()->sum('line_total'), 2);
        $groupLoan->update(['requested_amount' => $total]);

        $this->memberService->resync($groupLoan->refresh());
    }
}
