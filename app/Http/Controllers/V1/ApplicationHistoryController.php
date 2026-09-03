<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ApplicationHistory;
use App\Models\Customer;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateApplicationHistoryRequest;
use App\Http\Requests\UpdateApplicationHistoryRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ApplicationHistoryController extends Controller implements HasMiddleware
{
     use ActivityLogTrait;

     public static function middleware(): array
     {
         return [
             new Middleware('permission:Application History Index', only: ['index', 'show']),
             new Middleware('permission:Application History Create', only: ['store']),
             new Middleware('permission:Application History Update', only: ['update']),
             new Middleware('permission:Application History Delete', only: ['destroy']),
         ];
     }

     public function index(Request $request)
     {
         try {
             $perPage = $request->get('per_page', 15);
             $query = ApplicationHistory::with(['application', 'customer:'.Customer::SUMMARY_COLUMNS]);

             if ($request->has('search')) {
                 $query->search($request->search);
             }

             if ($request->has('application_id')) {
                 $query->where('application_id', $request->application_id);
             }

             if ($request->has('customer_id')) {
                 $query->where('customer_id', $request->customer_id);
             }

             $histories = $query->orderBy('created_at', 'desc')->paginate($perPage);

             $this->logActivity('Index', 'Application History', 'Application histories index accessed', [
                 'user_id' => Auth::id(),
                 'filters' => $request->only(['search', 'application_id', 'customer_id']),
                 'count' => $histories->count()
             ]);

             return response()->json([
                 'status' => 'success',
                 'message' => 'Application histories retrieved successfully',
                 'data' => $histories
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to retrieve application histories',
                 'error' => $th->getMessage()
             ], 500);
         }
     }

     public function store(CreateApplicationHistoryRequest $request)
     {
         try {
             $data = $request->validated();
             $customer = \App\Models\Customer::where('customer_id', $data['customer_id'])
                 ->orWhere('customer_code', $data['customer_id'])
                 ->first();
             if ($customer) {
                 $data['customer_id'] = $customer->id;
             }
             $history = ApplicationHistory::create($data);

             $this->logActivity('CREATE', 'Application History', "Created application history: {$history->id}", $data);

             return response()->json([
                 'status' => 'success',
                 'message' => 'Application history created successfully',
                 'data' => $history->load(['application', 'customer:'.Customer::SUMMARY_COLUMNS]),
             ], 201);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to create application history',
                 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
             ], 500);
         }
     }

     public function show(string $id)
     {
         try {
             $history = ApplicationHistory::with(['application', 'customer:'.Customer::SUMMARY_COLUMNS])->find($id);

             if (!$history) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Application history not found'
                 ], 404);
             }

             return response()->json([
                 'status' => 'success',
                 'message' => 'Application history retrieved successfully',
                 'data' => $history
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to retrieve application history',
                 'error' => $th->getMessage()
             ], 500);
         }
     }

     public function update(UpdateApplicationHistoryRequest $request, string $id)
     {
         try {
             $history = ApplicationHistory::find($id);

             if (!$history) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Application history not found'
                 ], 404);
             }

             $data = $request->validated();
             $customer = \App\Models\Customer::where('customer_id', $data['customer_id'])
                 ->orWhere('customer_code', $data['customer_id'])
                 ->first();
             if ($customer) {
                 $data['customer_id'] = $customer->id;
             }
             $history->update($data);

             $this->logActivity('UPDATE', 'Application History', "Updated application history: {$history->id}", $data);

             return response()->json([
                 'status' => 'success',
                 'message' => 'Application history updated successfully',
                 'data' => $history->load(['application', 'customer:'.Customer::SUMMARY_COLUMNS]),
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to update application history',
                 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
             ], 500);
         }
     }

     public function destroy(string $id)
     {
         try {
             $history = ApplicationHistory::find($id);

             if (!$history) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Application history not found'
                 ], 404);
             }

             $history->delete();

             $this->logActivity('DELETE', 'Application History', "Deleted application history: {$id}");

             return response()->json([
                 'status' => 'success',
                 'message' => 'Application history deleted successfully'
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to delete application history',
                 'error' => $th->getMessage()
             ], 500);
         }
     }
}
