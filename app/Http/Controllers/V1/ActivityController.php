<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog; // Imported correct model
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ActivityController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            // Matches the permissions added in your PermissionsSeeder
            new Middleware('permission:Activity Index', only: ['index', 'filterOptions']),
            new Middleware('permission:Activity Show', only: ['show']),
        ];
    }

    /**
     * The actions and modules that actually appear in the log.
     *
     * The screen used to offer a hardcoded action list -- VIEW, LOGIN, LOGOUT,
     * DOWNLOAD, EXPORT -- none of which this application has ever written,
     * while INDEX, which is over four fifths of every row, was not on it at
     * all. The module dropdown had the opposite problem: it was built from
     * whichever fifteen rows the current page happened to hold.
     *
     * Read from the log itself, so both lists stay true as the app grows.
     */
    public function filterOptions(): JsonResponse
    {
        try {
            return response()->json([
                'status'  => 'success',
                'message' => 'Activity filter options retrieved successfully',
                'data'    => [
                    'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action')->filter()->values(),
                    'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module')->filter()->values(),
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve activity filter options',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            
            // Query ActivityLog and load limited user columns safely
            $query = ActivityLog::with('user:id,name,email');

            // Search by user details or log description
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'LIKE', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%");
                        });
                });
            }

            if ($request->has('module') && $request->module != '') {
                $query->where('module', $request->module);
            }

            if ($request->has('action') && $request->action != '') {
                $query->where('action', $request->action);
            }

            if ($request->has('start_date') && $request->start_date != '') {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date') && $request->end_date != '') {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $activities = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Activities retrieved successfully',
                'data' => $activities
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve activities',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $activity = ActivityLog::with('user:id,name,email')->find($id);
            
            if (!$activity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Activity not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Activity retrieved successfully',
                'data' => $activity
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve activity',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }  
    }
}
