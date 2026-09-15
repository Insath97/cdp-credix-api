<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EmployeeController extends Controller implements HasMiddleware
{
    /**
     * Define the middleware for this controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Employee Index', only: ['getActiveList', 'getEmployeeList']),
        ];
    }

    /**
     * Get a list of active employees (lightweight list).
     */
    public function getActiveList(Request $request)
    {
        return $this->getEmployeeList($request);
    }

    /**
     * Get a list of employees (lightweight list).
     */
    public function getEmployeeList(Request $request)
    {
        try {
            $query = Employee::active();

            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            if ($request->has('designation_id')) {
                $query->where('designation_id', $request->designation_id);
            }

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            $employees = $query->orderBy('full_name', 'asc')
                ->get([
                    'id', 'full_name', 'employee_code', 'id_number',
                    'phone', 'phone_primary',
                    'branch_id', 'department_id', 'designation_id',
                ])
                ->each(function (Employee $employee) {
                    $employee->phone = $employee->phone ?: $employee->phone_primary;
                });

            return response()->json([
                'status' => 'success',
                'message' => 'Employees retrieved successfully',
                'data' => $employees,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve employees',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
