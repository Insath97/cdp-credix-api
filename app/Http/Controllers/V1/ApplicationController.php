<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ApplicationController extends Controller implements HasMiddleware
{
     use ActivityLogTrait;

     public static function middleware(): array
     {
         return [
             new Middleware('permission:Application Index', only: ['index', 'show']),
             new Middleware('permission:Application Create', only: ['store']),
             new Middleware('permission:Application Update', only: ['update']),
             new Middleware('permission:Application Delete', only: ['destroy']),
         ];
     }

     public function index(Request $request)
     {
         try {
             $perPage = $request->get('per_page', 15);
             $query = Application::query();

             if ($request->has('search')) {
                 $query->search($request->search);
             }

             if ($request->has('status')) {
                 $query->where('status', $request->status);
             }

             if ($request->has('application_type')) {
                 $query->where('application_type', $request->application_type);
             }

             if ($request->has('branch')) {
                 $query->where('branch', $request->branch);
             }

             $applications = $query->orderBy('created_at', 'desc')->paginate($perPage);

             $this->logActivity('Index', 'Application', 'Applications index accessed', [
                 'user_id' => Auth::id(),
                 'filters' => $request->only(['search', 'status', 'application_type', 'branch']),
                 'count' => $applications->count()
             ]);

             return response()->json([
                 'status' => 'success',
                 'message' => 'Applications retrieved successfully',
                 'data' => $applications
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to retrieve applications',
                 'error' => $th->getMessage()
             ], 500);
         }
     }

     public function store(CreateApplicationRequest $request)
     {
         try {
             $data = $request->validated();
             $application = Application::create($data);

             $this->logActivity('CREATE', 'Application', "Created application: {$application->application_no}", $data);

             return response()->json([
                 'status' => 'success',
                 'message' => 'Application created successfully',
                 'data' => $application,
             ], 201);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to create application',
                 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
             ], 500);
         }
     }

     public function show(string $id)
     {
         try {
             $application = Application::with(['customers', 'applicationHistories', 'guarantors'])->find($id);

             if (!$application) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Application not found'
                 ], 404);
             }

             return response()->json([
                 'status' => 'success',
                 'message' => 'Application retrieved successfully',
                 'data' => $application
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to retrieve application',
                 'error' => $th->getMessage()
             ], 500);
         }
     }

     public function update(UpdateApplicationRequest $request, string $id)
     {
         try {
             $application = Application::find($id);

             if (!$application) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Application not found'
                 ], 404);
             }

             $data = $request->validated();
             $application->update($data);

             $this->logActivity('UPDATE', 'Application', "Updated application: {$application->application_no}", $data);

             return response()->json([
                 'status' => 'success',
                 'message' => 'Application updated successfully',
                 'data' => $application,
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to update application',
                 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
             ], 500);
         }
     }

     public function destroy(string $id)
     {
         try {
             $application = Application::find($id);

             if (!$application) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Application not found'
                 ], 404);
             }

             $application->delete();

             $this->logActivity('DELETE', 'Application', "Deleted application: {$id}");

             return response()->json([
                 'status' => 'success',
                 'message' => 'Application deleted successfully'
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to delete application',
                 'error' => $th->getMessage()
             ], 500);
         }
     }
}
