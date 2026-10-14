<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Permission Index', only: ['index', 'show']),
            new Middleware('permission:Permission Create', only: ['store']),
            new Middleware('permission:Permission Update', only: ['update']),
            new Middleware('permission:Permission Delete', only: ['destroy']),

            // getPermissionList appeared in none of the lists above, so the
            // whole permission catalogue was readable by any authenticated
            // principal -- a customer's portal token included. It is not a
            // secret worth much on its own, but it is a precise map of what
            // this system can do and what each role could be given.
            //
            // Gated on alternatives rather than on Permission Index alone,
            // because two screens legitimately read it: the Permissions list
            // itself, and the checkbox tree on the role form, which is reached
            // by someone holding a Role permission rather than a Permission one.
            new Middleware(
                'permission:Permission Index|Role Index|Role Create|Role Update',
                only: ['getPermissionList'],
            ),
        ];
    }

    public function index(Request $request)
    {
        try {
            // Clamped: per_page=0 reaches LengthAwarePaginator, which divides
            // by the page size, so the DivisionByZeroError surfaced through the
            // catch-all below as an opaque 500. A huge value loaded all 200-odd
            // rows at once.
            $perPage = $this->perPage($request);

            $query = Permission::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('guard_name', 'LIKE', "%{$search}%")
                        ->orWhere('group_name', 'LIKE', "%{$search}%");
                });
            }

            // filled(), not has(): a cleared dropdown sends `?group_name=`, and
            // has() is true for a present-but-empty parameter, so the query
            // became `where group_name = ''` and the screen answered "no
            // permissions found" for a filter the user had just reset.
            if ($request->filled('guard_name')) {
                $query->where('guard_name', $request->guard_name);
            }

            if ($request->filled('group_name')) {
                $query->where('group_name', $request->group_name);
            }

            $query->orderBy('group_name', 'asc')->orderBy('name', 'asc');

            $permissions = $query->paginate($perPage);

            // An empty page is still a page.
            //
            // This used to return a bare [] for an empty result and a paginator
            // object otherwise, so `data` changed shape depending on the rows
            // found. The client reads total and last_page off that object to
            // draw its pager, and on the empty branch there was nothing to
            // read.

            return response()->json([
                'status' => 'success',
                'message' => 'Permissions retrieved successfully',
                'data' => $permissions
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve permissions',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function create()
    {
    }

    public function store(CreatePermissionRequest $request)
    {
        try {
            $data = $request->validated();

            $data['guard_name'] = 'api';

            $permission = Permission::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Permission created successfully',
                'data' => $permission
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create permission',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $permission = Permission::find($id);

            if (!$permission) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Permission not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Permission retrieved successfully',
                'data' => $permission
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve permission',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function edit(string $id)
    {
    }

    public function update(UpdatePermissionRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $permission = Permission::find($id);

            if (!$permission) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Permission not found',
                    'data' => []
                ], 404);
            }

            if (isset($data['guard_name'])) {
                unset($data['guard_name']);
            }

            $permission->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Permission updated successfully',
                'data' => $permission
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update permission',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $permission = Permission::find($id);

            if (!$permission) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Permission not found',
                    'data' => []
                ], 404);
            }

            // Check if permission is assigned to any role
            if ($permission->roles()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete permission. It is assigned to one or more roles.',
                    'data' => [
                        'assigned_roles_count' => $permission->roles()->count()
                    ]
                ], 422);
            }

            $permission->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Permission deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete permission',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function getPermissionList()
    {
        try {
            $permissions = Permission::select('id', 'name', 'group_name')
                ->orderBy('group_name', 'asc')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Permissions retrieved successfully',
                'data' => $permissions
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve permissions',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
