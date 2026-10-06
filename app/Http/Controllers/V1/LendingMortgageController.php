<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\LendingMortgage;
use Illuminate\Http\Request;

class LendingMortgageController extends Controller
{
    /**
     * Return lending mortgages filtered by security type for the frontend dropdown.
     */
    public function options(Request $request)
    {
        try {
            $query = LendingMortgage::query();

            if ($request->filled('type')) {
                $canonicalType = LendingMortgage::canonicalType($request->input('type'));
                $query->where('type', $canonicalType);
            }

            $query->where('status', LendingMortgage::STATUS_ACTIVE);

            $mortgages = $query->orderBy('name', 'asc')->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'Lending mortgages retrieved successfully',
                'data'    => $mortgages,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve lending mortgages',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active lending mortgage plans by security type.
     *
     * GET /api/v1/lending-mortgages/plans?type=property
     */
    public function plansBySecurityType(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string'],
        ]);

        $canonicalType = LendingMortgage::canonicalType($request->type);

        $plans = LendingMortgage::where(function ($q) use ($request, $canonicalType) {
                $q->where('type', $canonicalType)
                  ->orWhere('type', $request->type);
            })
            ->where(function ($q) {
                $q->where('status', LendingMortgage::STATUS_ACTIVE)
                  ->orWhere('status', '1');
            })
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'code',
                'percentage',
            ])
            ->map(function ($plan) {
                return [
                    'id'         => $plan->id,
                    'name'       => $plan->name,
                    'code'       => $plan->code,
                    'percent'    => (float) $plan->percentage,
                    'percentage' => (float) $plan->percentage,
                ];
            });

        return response()->json($plans);
    }
}
