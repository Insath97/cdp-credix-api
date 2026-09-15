<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RecoveryActivity;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateRecoveryActivityRequest;
use App\Http\Requests\UpdateRecoveryActivityRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RecoveryActivityController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Recovery Activity Index',  only: ['index', 'show']),
            new Middleware('permission:Recovery Activity Create', only: ['store']),
            new Middleware('permission:Recovery Activity Update', only: ['update']),
            new Middleware('permission:Recovery Activity Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of recovery activities.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = RecoveryActivity::with(['recoveryCase', 'performedBy:'.User::SUMMARY_COLUMNS]);

            if ($request->has('recovery_case_id')) {
                $query->where('recovery_case_id', $request->recovery_case_id);
            }

            if ($request->has('activity_type')) {
                $query->where('activity_type', $request->activity_type);
            }

            $activities = $query->orderByDesc('performed_at')->paginate($perPage);

            $this->logActivity('Index', 'RecoveryActivity', 'Recovery activities index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['recovery_case_id', 'activity_type']),
                'count'   => $activities->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery activities retrieved successfully',
                'data'    => $activities,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve recovery activities',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created recovery activity.
     */
    public function store(CreateRecoveryActivityRequest $request)
    {
        try {
            $data = $request->validated();
            $data['performed_by'] = Auth::id();
            $data['performed_at'] = $data['performed_at'] ?? now();

            $activity = RecoveryActivity::create($data);

            $this->logActivity('CREATE', 'RecoveryActivity', "Created recovery activity ID: {$activity->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery activity recorded successfully',
                'data'    => $activity->load(['recoveryCase', 'performedBy:'.User::SUMMARY_COLUMNS]),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to record recovery activity',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified recovery activity.
     */
    public function show(string $id)
    {
        try {
            $activity = RecoveryActivity::with(['recoveryCase', 'performedBy:'.User::SUMMARY_COLUMNS])->find($id);

            if (!$activity) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Recovery activity not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery activity retrieved successfully',
                'data'    => $activity,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve recovery activity',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified recovery activity.
     */
    public function update(UpdateRecoveryActivityRequest $request, string $id)
    {
        try {
            $activity = RecoveryActivity::find($id);

            if (!$activity) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Recovery activity not found',
                ], 404);
            }

            $data = $request->validated();
            $activity->update($data);

            $this->logActivity('UPDATE', 'RecoveryActivity', "Updated recovery activity ID: {$activity->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery activity updated successfully',
                'data'    => $activity->fresh(['recoveryCase', 'performedBy:'.User::SUMMARY_COLUMNS]),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update recovery activity',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified recovery activity from storage.
     */
    public function destroy(string $id)
    {
        try {
            $activity = RecoveryActivity::find($id);

            if (!$activity) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Recovery activity not found',
                ], 404);
            }

            $activity->delete();

            $this->logActivity('DELETE', 'RecoveryActivity', "Deleted recovery activity ID: {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery activity deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete recovery activity',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
