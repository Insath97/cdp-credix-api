<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ExternalRecoveryAgent;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateExternalRecoveryAgentRequest;
use App\Http\Requests\UpdateExternalRecoveryAgentRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ExternalRecoveryAgentController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:External Recovery Agent Index',  only: ['index', 'show', 'getExternalRecoveryAgentList']),
            new Middleware('permission:External Recovery Agent Create', only: ['store']),
            new Middleware('permission:External Recovery Agent Update', only: ['update']),
            new Middleware('permission:External Recovery Agent Delete', only: ['destroy']),
        ];
    }

    /**
     * Lightweight list of active external recovery agencies, for a picker.
     *
     * Unpaginated and trimmed to what a dropdown needs — the contact phone is
     * included because the assign screen shows who will be called.
     */
    public function getExternalRecoveryAgentList(Request $request)
    {
        try {
            $query = ExternalRecoveryAgent::active();

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $agents = $query
                ->select('id', 'name', 'phone', 'email')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'External recovery agents retrieved successfully',
                'data'    => $agents,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve external recovery agents',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display a listing of external recovery agents.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = ExternalRecoveryAgent::query();

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $agents = $query->orderByDesc('id')->paginate($perPage);

            $this->logActivity('Index', 'ExternalRecoveryAgent', 'External recovery agents index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'is_active']),
                'count'   => $agents->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'External recovery agents retrieved successfully',
                'data'    => $agents,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve external recovery agents',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created external recovery agent.
     */
    public function store(CreateExternalRecoveryAgentRequest $request)
    {
        try {
            $data = $request->validated();
            $agent = ExternalRecoveryAgent::create($data);

            $this->logActivity('CREATE', 'ExternalRecoveryAgent', "Created external recovery agent: {$agent->name}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'External recovery agent created successfully',
                'data'    => $agent,
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create external recovery agent',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified external recovery agent.
     */
    public function show(string $id)
    {
        try {
            $agent = ExternalRecoveryAgent::find($id);

            if (!$agent) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'External recovery agent not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'External recovery agent retrieved successfully',
                'data'    => $agent,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve external recovery agent',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified external recovery agent.
     */
    public function update(UpdateExternalRecoveryAgentRequest $request, string $id)
    {
        try {
            $agent = ExternalRecoveryAgent::find($id);

            if (!$agent) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'External recovery agent not found',
                ], 404);
            }

            $data = $request->validated();
            $agent->update($data);

            $this->logActivity('UPDATE', 'ExternalRecoveryAgent', "Updated external recovery agent ID: {$agent->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'External recovery agent updated successfully',
                'data'    => $agent->fresh(),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update external recovery agent',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified external recovery agent from storage.
     */
    public function destroy(string $id)
    {
        try {
            $agent = ExternalRecoveryAgent::find($id);

            if (!$agent) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'External recovery agent not found',
                ], 404);
            }

            $agent->delete();

            $this->logActivity('DELETE', 'ExternalRecoveryAgent', "Deleted external recovery agent ID: {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'External recovery agent deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete external recovery agent',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
