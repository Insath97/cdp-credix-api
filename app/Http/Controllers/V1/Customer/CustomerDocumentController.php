<?php

namespace App\Http\Controllers\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use App\Traits\ResolvesAuthenticatedCustomerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerDocumentController extends Controller
{
    // FileUploadTrait is what provides resolveStoredFile(), which download()
    // uses to turn a stored path into a readable one. It was referenced
    // without being used here, so every download died on an undefined method
    // and the blanket catch reported it as a 500.
    use ActivityLogTrait, FileUploadTrait, ResolvesAuthenticatedCustomerTrait;

    /**
     * List the customer's own uploaded documents.
     */
    public function index(Request $request)
    {
        try {
            $customerId = $this->myCustomerId();
            // Clamped through the base controller: an unclamped per_page lets any
            // caller force a 500. A negative value is truthy, so nothing replaced
            // it, and the query kept the OFFSET while dropping the LIMIT.
            $perPage = $this->perPage($request);

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
     * Stream one of the customer's own documents.
     *
     * Resolved through FileUploadTrait rather than public_path(), which is what
     * this used to do. Two things changed underneath it: uploads now land in
     * private storage instead of the web root, and the resolver refuses any
     * path that escapes its base directory. The second matters even for files
     * that are where they should be, because the stored path was reachable from
     * the create endpoint until recently -- a row pointing at ../.env would
     * otherwise have been streamed straight back here.
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

            $absolutePath = $this->resolveStoredFile($document->file_path);

            if ($absolutePath === null) {
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
