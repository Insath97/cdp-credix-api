<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RecoveryAgent;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateRecoveryAgentRequest;
use App\Http\Requests\UpdateRecoveryAgentRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RecoveryAgentController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Recovery Agent Index',  only: ['index', 'show']),
            new Middleware('permission:Recovery Agent Create', only: ['store']),
            new Middleware('permission:Recovery Agent Update', only: ['update']),
            new Middleware('permission:Recovery Agent Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of recovery agents.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = RecoveryAgent::with(['user', 'branch']);

            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $agents = $query->orderByDesc('id')->paginate($perPage);

            $this->logActivity('Index', 'RecoveryAgent', 'Recovery agents index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['branch_id', 'is_active']),
                'count'   => $agents->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery agents retrieved successfully',
                'data'    => $agents,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve recovery agents',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created recovery agent.
     */
    public function store(CreateRecoveryAgentRequest $request)
    {
        try {
            $data = $request->validated();
            $agent = RecoveryAgent::create($data);

            $this->logActivity('CREATE', 'RecoveryAgent', "Created recovery agent ID: {$agent->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery agent created successfully',
                'data'    => $agent->load(['user', 'branch']),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create recovery agent',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified recovery agent.
     */
    public function show(string $id)
    {
        try {
            $agent = RecoveryAgent::with(['user', 'branch'])->find($id);

            if (!$agent) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Recovery agent not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery agent retrieved successfully',
                'data'    => $agent,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve recovery agent',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified recovery agent.
     */
    public function update(UpdateRecoveryAgentRequest $request, string $id)
    {
        try {
            $agent = RecoveryAgent::find($id);

            if (!$agent) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Recovery agent not found',
                ], 404);
            }

            $data = $request->validated();
            $agent->update($data);

            $this->logActivity('UPDATE', 'RecoveryAgent', "Updated recovery agent ID: {$agent->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery agent updated successfully',
                'data'    => $agent->fresh(['user', 'branch']),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update recovery agent',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified recovery agent from storage.
     */
    public function destroy(string $id)
    {
        try {
            $agent = RecoveryAgent::find($id);

            if (!$agent) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Recovery agent not found',
                ], 404);
            }

            $agent->delete();

            $this->logActivity('DELETE', 'RecoveryAgent', "Deleted recovery agent ID: {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery agent deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete recovery agent',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
