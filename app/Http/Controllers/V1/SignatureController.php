<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSignatureRequest;
use App\Models\Signature;
use App\Models\Customer;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SignatureController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Signature Index',  only: ['index', 'show']),
            new Middleware('permission:Signature Create', only: ['store']),
            new Middleware('permission:Signature Delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Signature::with(['customer:' . Customer::SUMMARY_COLUMNS]);

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            $signatures = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'data'    => $signatures,
                'message' => 'Signatures retrieved successfully',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'An error occurred while fetching signatures.',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Store React canvas vector stroke points into JSON column.
     */
    public function store(CreateSignatureRequest $request)
    {
        $data = $request->validated();
        $lockKey = "submitting_signature_{$data['customer_id']}";

        // If the customer already clicked submit, block any other requests for 5 seconds
        if (!Cache::add($lockKey, true, 5)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Your signature is already being submitted. Please wait.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $signature = Signature::create([
                'customer_id'    => $data['customer_id'],
                'signature_data' => $data['signature_data'],
                'signed_at'      => now(),
            ]);

            DB::commit();

            $this->logActivity('CREATE', 'Signature', "Created signature for customer ID: {$signature->customer_id}");

            return response()->json([
                'status'  => 'success',
                'message' => 'Signature stored successfully',
                'data'    => $signature,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Signature store failed: ' . $th->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to store signature',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        } finally {
            Cache::forget($lockKey);
        }
    }

    /**
     * Retrieve signature for React canvas redrawing.
     */
    public function show(string $id)
    {
        try {
            $signature = Signature::with(['customer:' . Customer::SUMMARY_COLUMNS])->find($id);

            if (!$signature) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Signature not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Signature retrieved successfully',
                'data'    => $signature,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve signature',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $signature = Signature::find($id);

            if (!$signature) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Signature not found',
                ], 404);
            }

            $signature->delete();

            $this->logActivity('Delete', 'Signature', 'Signature deleted', [
                'deleter_id'   => Auth::id(),
                'customer_id'  => $signature->customer_id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Signature deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete signature',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
