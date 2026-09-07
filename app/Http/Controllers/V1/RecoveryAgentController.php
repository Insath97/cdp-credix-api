<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RecoveryAgent;
use App\Models\ExternalRecoveryAgent;
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
            new Middleware('permission:Recovery Agent Index',  only: ['index', 'show', 'combinedList', 'getRecoveryAgentList']),
            new Middleware('permission:Recovery Agent Create', only: ['store']),
            new Middleware('permission:Recovery Agent Update', only: ['update']),
            new Middleware('permission:Recovery Agent Delete', only: ['destroy']),
        ];
    }

    /**
     * Lightweight list of active internal recovery officers, for a picker.
     *
     * Unpaginated and trimmed to what a dropdown needs — index() eager-loads
     * the whole user and branch record, which is far more than a select box
     * requires. Ordered by the officer's own name, which lives on the joined
     * users row rather than on recovery_agents.
     */
    public function getRecoveryAgentList(Request $request)
    {
        try {
            // Columns are table-qualified throughout: the join below brings in
            // `users`, which has its own `is_active`, so the unqualified
            // scopeActive() would make the where clause ambiguous.
            $query = RecoveryAgent::query()->where('recovery_agents.is_active', true);

            if ($request->has('branch_id')) {
                $query->where('recovery_agents.branch_id', $request->branch_id);
            }

            // Flat rows straight from the join rather than an eager-loaded
            // `user` relation: User exposes branch/zone/region/province/parent/
            // children accessors that each resolve through employee(), so
            // loading the relation would fire several extra queries per row and
            // pad the payload with keys a picker never reads.
            $agents = $query
                ->leftJoin('users', 'users.id', '=', 'recovery_agents.user_id')
                ->leftJoin('branches', 'branches.id', '=', 'recovery_agents.branch_id')
                ->orderBy('users.name')
                ->get([
                    'recovery_agents.id',
                    'recovery_agents.user_id',
                    'users.name',
                    'users.username',
                    'recovery_agents.branch_id',
                    'branches.name as branch_name',
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
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
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
     * Display a combined listing of internal and external recovery agents.
     */
    public function combinedList(Request $request)
    {
        try {
            $type     = $request->get('type', 'all');
            $search   = $request->get('search');
            $branchId = $request->get('branch_id');
            $isActive = $request->has('is_active') ? $request->boolean('is_active') : null;

            $results = collect();

            // Fetch Internal Recovery Agents
            if (in_array($type, ['all', 'internal'])) {
                $internalQuery = RecoveryAgent::with(['user', 'branch']);

                if ($branchId !== null) {
                    $internalQuery->where('branch_id', $branchId);
                }

                if ($isActive !== null) {
                    $internalQuery->where('is_active', $isActive);
                }

                if (!empty($search)) {
                    $internalQuery->whereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                }

                $internalAgents = $internalQuery->get()->map(function ($agent) {
                    return [
                        'id'          => $agent->id,
                        'agent_type'  => 'internal',
                        'name'        => $agent->user?->name,
                        'email'       => $agent->user?->email,
                        'phone'       => null,
                        'address'     => null,
                        'user_id'     => $agent->user_id,
                        'branch_id'   => $agent->branch_id,
                        'branch_name' => $agent->branch?->name,
                        'is_active'   => (bool) $agent->is_active,
                        'remarks'     => $agent->remarks,
                        'created_at'  => $agent->created_at,
                        'updated_at'  => $agent->updated_at,
                    ];
                });

                $results = $results->concat($internalAgents);
            }

            // Fetch External Recovery Agents
            if (in_array($type, ['all', 'external'])) {
                $externalQuery = ExternalRecoveryAgent::query();

                if ($isActive !== null) {
                    $externalQuery->where('is_active', $isActive);
                }

                if (!empty($search)) {
                    $externalQuery->search($search);
                }

                if ($branchId === null || $type === 'external') {
                    $externalAgents = $externalQuery->get()->map(function ($extAgent) {
                        return [
                            'id'          => $extAgent->id,
                            'agent_type'  => 'external',
                            'name'        => $extAgent->name,
                            'email'       => $extAgent->email,
                            'phone'       => $extAgent->phone,
                            'address'     => $extAgent->address,
                            'user_id'     => null,
                            'branch_id'   => null,
                            'branch_name' => null,
                            'is_active'   => (bool) $extAgent->is_active,
                            'remarks'     => $extAgent->remarks,
                            'created_at'  => $extAgent->created_at,
                            'updated_at'  => $extAgent->updated_at,
                        ];
                    });

                    $results = $results->concat($externalAgents);
                }
            }

            $this->logActivity('Index', 'CombinedRecoveryAgent', 'Combined recovery agents list accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['type', 'search', 'branch_id', 'is_active']),
                'count'   => $results->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Combined recovery agents retrieved successfully',
                'data'    => $results->values(),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve combined recovery agents',
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
