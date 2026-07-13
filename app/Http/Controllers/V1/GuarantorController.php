<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Guarantor;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\UpdateGuarantorRequest;
use App\Http\Requests\CreateGuarantorRequest;


class GuarantorController extends Controller
{
     use ActivityLogTrait;

     public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Guarantor::with(['customer']);

            if ($request->has('search') ) {
                $query->search($request->search);
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            $guarantors = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'Guarantor', 'Guarantors index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'customer_id']),
                'count' => $guarantors->count()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantors retrieved successfully',
                'data' => $guarantors
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve guarantors',
                'error' => $th->getMessage()
            ], 500);
        }

    }

     public function store(CreateGuarantorRequest $request)
    {
        try {
            $data = $request->validated();
            $guarantor = Guarantor::create($data);

            $this->logActivity('CREATE', 'Guarantor', "Created guarantor: {$guarantor->full_name}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantor created successfully',
                'data' => $guarantor->load('customer'),
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create guarantor',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

     public function show(string $id)
    {
        try {
            $guarantor = Guarantor::with(['customer'])->find($id);

            if (!$guarantor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Guarantor not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantor retrieved successfully',
                'data' => $guarantor
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve guarantor',
                'error' => $th->getMessage()
            ], 500);
        }
    }

     public function update(UpdateGuarantorRequest $request, string $id)
    {
        try {
            $guarantor = Guarantor::find($id);

            if (!$guarantor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Guarantor not found'
                ], 404);
            }

            $data = $request->validated();
            $guarantor->update($data);

            $this->logActivity('Update', 'Guarantor', 'Guarantor updated', [
                'updater_id' => Auth::id(),
                'guarantor_id' => $guarantor->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantor updated successfully',
                'data' => $guarantor
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update guarantor',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $guarantor = Guarantor::find($id);

            if (!$guarantor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Guarantor not found'
                ], 404);
            }

            $guarantor->delete();

            $this->logActivity('Delete', 'Guarantor', 'Guarantor deleted (soft)', [
                'deleter_id' => Auth::id(),
                'guarantor_id' => $id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantor deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete guarantor',
                'error' => $th->getMessage()
            ], 500);
        }
    }

     public function toggleStatus(string $id)
    {
        try {
            $guarantor = Guarantor::find($id);

            if (!$guarantor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Guarantor not found'
                ], 404);
            }

            $guarantor->is_active = !$guarantor->is_active;
            $guarantor->save();

            $this->logActivity('Toggle Status', 'Guarantor', 'Guarantor status toggled', [
                'user_id' => Auth::id(),
                'guarantor_id' => $guarantor->id,
                'new_status' => $guarantor->is_active
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantor status updated successfully',
                'data' => [
                    'id' => $guarantor->id,
                    'is_active' => $guarantor->is_active
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle guarantor status',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
