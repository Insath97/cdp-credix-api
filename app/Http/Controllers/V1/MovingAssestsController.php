<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\MovingAssests;
use App\Enums\LoanApplicationStatus;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateMovingAssetsRequest;
use App\Http\Requests\UpdateMovingAssetsRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MovingAssestsController extends Controller implements HasMiddleware
{
     use ActivityLogTrait;

     public static function middleware(): array
     {
         return [
             new Middleware('permission:Moving Asset Index', only: ['index', 'show']),
             new Middleware('permission:Moving Asset Create', only: ['store']),
             new Middleware('permission:Moving Asset Update', only: ['update']),
             new Middleware('permission:Moving Asset Delete', only: ['destroy']),
         ];
     }

     public function index(Request $request)
     {
         try {
             $perPage = $request->get('per_page', 15);
             $query = MovingAssests::with(['customer']);

             if ($request->has('search')) {
                 $query->search($request->search);
             }

             if ($request->has('customer_id')) {
                 $query->where('customer_id', $request->customer_id);
             }

             if ($request->has('is_active')) {
                 $query->where('is_active', $request->is_active);
             }

             if ($request->has('used_for_loan')) {
                 $terminalStatuses = [
                     LoanApplicationStatus::Rejected->value,
                     LoanApplicationStatus::Cancelled->value,
                     LoanApplicationStatus::Closed->value,
                 ];

                 if ($request->boolean('used_for_loan')) {
                     $query->whereHas('loanApplications', fn ($q) => $q->whereNotIn('status', $terminalStatuses));
                 } else {
                     $query->whereDoesntHave('loanApplications', fn ($q) => $q->whereNotIn('status', $terminalStatuses));
                 }
             }

             $moving_assests = $query->orderBy('created_at', 'desc')->paginate($perPage);

             $this->logActivity('Index', 'Moving Assets', 'Moving assets index accessed', [
                 'user_id' => Auth::id(),
                 'filters' => $request->only(['search', 'customer_id', 'is_active']),
                 'count' => $moving_assests->count()
             ]);

             return response()->json([
                 'status' => 'success',
                 'message' => 'Moving assets retrieved successfully',
                 'data' => $moving_assests
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to retrieve moving assets',
                 'error' => $th->getMessage()
             ], 500);
         }
     }

     public function store(CreateMovingAssetsRequest $request)
     {
         try {
             $data = $request->validated();
             $customer = \App\Models\Customer::where('customer_id', $data['customer_id'])
                 ->orWhere('customer_code', $data['customer_id'])
                 ->first();
             if ($customer) {
                 $data['customer_id'] = $customer->id;
             }
             $moving_assests = MovingAssests::create($data);

             $this->logActivity('CREATE', 'Moving Assets', "Created moving assets: {$moving_assests->id}", $data);

             return response()->json([
                 'status' => 'success',
                 'message' => 'Moving assets created successfully',
                 'data' => $moving_assests->load('customer'),
             ], 201);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to create moving assets',
                 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
             ], 500);
         }
     }

     public function show(string $id)
     {
         try {
             $moving_assests = MovingAssests::with(['customer'])->find($id);

             if (!$moving_assests) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Moving assets not found'
                 ], 404);
             }

             return response()->json([
                 'status' => 'success',
                 'message' => 'Moving assets retrieved successfully',
                 'data' => $moving_assests
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to retrieve moving assets',
                 'error' => $th->getMessage()
             ], 500);
         }
     }

     public function update(UpdateMovingAssetsRequest $request, string $id)
     {
         try {
             $moving_assests = MovingAssests::find($id);

             if (!$moving_assests) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Moving assets not found'
                 ], 404);
             }

             $data = $request->validated();
             $customer = \App\Models\Customer::where('customer_id', $data['customer_id'])
                 ->orWhere('customer_code', $data['customer_id'])
                 ->first();
             if ($customer) {
                 $data['customer_id'] = $customer->id;
             }
             $moving_assests->update($data);

             $this->logActivity('UPDATE', 'Moving Assets', "Updated moving assets: {$moving_assests->id}", $data);

             return response()->json([
                 'status' => 'success',
                 'message' => 'Moving assets updated successfully',
                 'data' => $moving_assests->load('customer'),
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to update moving assets',
                 'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
             ], 500);
         }
     }

     public function destroy(string $id)
     {
         try {
             $moving_assests = MovingAssests::find($id);

             if (!$moving_assests) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Moving assets not found'
                 ], 404);
             }

             $moving_assests->delete();

             $this->logActivity('DELETE', 'Moving Assets', "Deleted moving assets: {$id}");

             return response()->json([
                 'status' => 'success',
                 'message' => 'Moving assets deleted successfully'
             ], 200);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to delete moving assets',
                 'error' => $th->getMessage()
             ], 500);
         }
     }
}
