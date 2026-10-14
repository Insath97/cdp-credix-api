<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalSearchRequest;
use App\Services\GlobalSearchService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

/**
 * Look one person up across every module at once.
 *
 * Given an identification document, answers who they are, whether the system
 * knows them as an employee, a customer, a guarantor or several of those at
 * once, and every loan they touch -- saying for each one whether they borrowed
 * it, shared it, or stood surety for somebody else's.
 *
 * Read-only. Nothing here writes anything except the activity log entry, which
 * is deliberate: this endpoint can pull up a named person's whole financial
 * position from a number printed on their ID card, so who ran it and what they
 * searched for is itself worth recording.
 */
class GlobalSearchController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(
        protected GlobalSearchService $globalSearchService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Global Search', only: ['search']),
        ];
    }

    public function search(GlobalSearchRequest $request)
    {
        try {
            $data = $request->validated();

            $result = $this->globalSearchService->search(
                trim($data['id_type']),
                trim($data['id_number'])
            );

            $this->logActivity('Index', 'GlobalSearch', 'Global search performed', [
                'user_id'   => Auth::id(),
                'id_type'   => $data['id_type'],
                'id_number' => $data['id_number'],
                'found'     => $result['found'],
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => $result['found']
                    ? 'Person found'
                    : 'No person found for that identification',
                'data'    => $result,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to run the global search',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

  
    public function idTypes(Request $request)
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Identification types retrieved successfully',
            'data'    => \App\Http\Requests\GlobalSearchRequest::ID_TYPES,
        ], 200);
    }
}
