<?php

namespace App\Http\Controllers\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Traits\ActivityLogTrait;
use App\Traits\ResolvesAuthenticatedCustomerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerDocumentController extends Controller
{
    use ActivityLogTrait, ResolvesAuthenticatedCustomerTrait;

    /**
     * List the customer's own uploaded documents.
     */
    public function index(Request $request)
    {
        try {
            $customerId = $this->myCustomerId();
            $perPage = $request->get('per_page', 15);

            $query = Document::where('customer_id', $customerId)->active();

            if ($request->has('document_type')) {
                $query->where('document_type', $request->document_type);
            }

            $documents = $query->orderByDesc('created_at')
                ->paginate($perPage)
                ->through(fn ($document) => [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'document_name' => $document->document_name,
                    'status' => $document->status,
                    'uploaded_at' => $document->uploaded_at,
                    'download_url' => url("/api/v1/my/documents/{$document->id}/download"),
                ]);

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed documents', [
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Documents retrieved successfully',
                'data' => $documents,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve documents',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Stream one of the customer's own documents. Files are stored under
     * public/uploads/documents/ (via FileUploadTrait, not the Storage disk),
     * so file_path is resolved relative to public_path().
     */
    public function download(string $id)
    {
        try {
            $customerId = $this->myCustomerId();

            $document = Document::where('id', $id)
                ->where('customer_id', $customerId)
                ->first();

            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found',
                ], 404);
            }

            $absolutePath = public_path($document->file_path);

            if (!is_file($absolutePath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document file is missing',
                ], 404);
            }

            $this->logActivity('Show', 'CustomerPortal', "Customer downloaded document ID: {$document->id}", [
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
            ]);

            return response()->file($absolutePath);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download document',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
