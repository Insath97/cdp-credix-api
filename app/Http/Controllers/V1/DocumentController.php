<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DocumentController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Document Index', only: ['index', 'show']),
            new Middleware('permission:Document Create', only: ['store']),
            new Middleware('permission:Document Update', only: ['update', 'toggleStatus']),
            new Middleware('permission:Document Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of documents.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Document::with(['uploader']);

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('documentable_type')) {
                $typeMap = [
                    'customer' => \App\Models\Customer::class,
                    'guarantor' => \App\Models\Guarantor::class,
                    'application' => \App\Models\Application::class,
                ];
                $typeInput = strtolower($request->documentable_type);
                $resolvedType = $typeMap[$typeInput] ?? $request->documentable_type;
                $query->where('documentable_type', $resolvedType);
            }

            if ($request->has('documentable_id')) {
                $query->where('documentable_id', $request->documentable_id);
            }

            if ($request->has('document_type')) {
                $query->where('document_type', $request->document_type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $documents = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'Document', 'Documents index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'documentable_type', 'documentable_id', 'document_type', 'status', 'is_active']),
                'count' => $documents->count()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Documents retrieved successfully',
                'data' => $documents
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve documents',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created document.
     */
    public function store(CreateDocumentRequest $request)
    {
        try {
            $data = $request->validated();

            // Map polymorphic relation alias to full class name
            $typeMap = [
                'customer' => \App\Models\Customer::class,
                'guarantor' => \App\Models\Guarantor::class,
                'application' => \App\Models\Application::class,
            ];
            $typeInput = strtolower($data['documentable_type']);
            if (array_key_exists($typeInput, $typeMap)) {
                $data['documentable_type'] = $typeMap[$typeInput];
            }

            // Verify that the polymorphic model exists
            $modelClass = $data['documentable_type'];
            if (!class_exists($modelClass) || !$modelClass::find($data['documentable_id'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The linked documentable model does not exist.'
                ], 422);
            }

            // Handle file upload if present
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $path = $file->storeAs('documents', $fileName, 'public');
                $data['file_path'] = '/storage/' . $path;
            }

            // Remove file parameter from data array to prevent insert errors
            unset($data['file']);

            // Set system fields
            $data['uploaded_by'] = Auth::id();
            $data['uploaded_at'] = now();

            $document = Document::create($data);

            $this->logActivity('CREATE', 'Document', "Uploaded document: {$document->document_name}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Document created successfully',
                'data' => $document->load('uploader')
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create document',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified document.
     */
    public function show(string $id)
    {
        try {
            $document = Document::with(['uploader'])->find($id);

            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Document retrieved successfully',
                'data' => $document
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve document',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified document.
     */
    public function update(UpdateDocumentRequest $request, string $id)
    {
        try {
            $document = Document::find($id);

            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found'
                ], 404);
            }

            $data = $request->validated();

            // Handle file upload replacement
            if ($request->hasFile('file')) {
                // Delete old file if it exists in public storage
                if (!empty($document->file_path)) {
                    $oldPath = str_replace('/storage/', '', $document->file_path);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                $file = $request->file('file');
                $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $path = $file->storeAs('documents', $fileName, 'public');
                $data['file_path'] = '/storage/' . $path;
            }

            unset($data['file']);

            $document->update($data);

            $this->logActivity('UPDATE', 'Document', "Updated document: {$document->document_name}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Document updated successfully',
                'data' => $document->load('uploader')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update document',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(string $id)
    {
        try {
            $document = Document::find($id);

            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found'
                ], 404);
            }

            $document->delete();

            $this->logActivity('DELETE', 'Document', "Deleted document: {$document->document_name}", [
                'document_id' => $id,
                'deleted_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Document deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete document',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update the document validation status.
     */
    public function toggleStatus(Request $request, string $id)
    {
        try {
            $document = Document::find($id);

            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found'
                ], 404);
            }

            $request->validate([
                'status' => 'required|string|in:active,rejected,expired',
                'remarks' => 'nullable|string',
            ]);

            $document->status = $request->status;
            if ($request->has('remarks')) {
                $document->remarks = $request->remarks;
            }
            $document->save();

            $this->logActivity('TOGGLE_STATUS', 'Document', "Toggled status of document {$document->document_name} to {$request->status}", [
                'document_id' => $id,
                'new_status' => $request->status,
                'remarks' => $request->remarks,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Document status updated successfully',
                'data' => $document->load('uploader')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update document status',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
