<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Customer;
use App\Models\Document;
use App\Models\LoanApplication;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use App\Http\Requests\CreateDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DocumentController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Document Index', only: ['index', 'show']),
            new Middleware('permission:Document Create', only: ['store']),
            new Middleware('permission:Document Update', only: ['update']),
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
            $query = Document::with([
                // The list names whoever the document belongs to. Guarantor
                // documents carry no customer_id at all, so both relations are
                // loaded or those rows show a dash forever.
                'customer:'.Customer::SUMMARY_COLUMNS.',employment_status',
                'guarantor:id,customer_id,full_name,id_number,employment_status,employer_name',
                'loanApplication.application:id,application_no',
                'uploader:'.User::SUMMARY_COLUMNS,
            ]);

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('document_type')) {
                $query->where('document_type', $request->document_type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // The loan application document checklist loads everything already
            // collected for one application in a single call, then matches it
            // against its slots. Without these filters it would have to pull
            // every document in the system and filter client-side.
            if ($request->filled('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->filled('guarantor_id')) {
                $query->where('guarantor_id', $request->guarantor_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $documents = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'Document', 'Documents index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'document_type', 'status', 'is_active', 'loan_application_id', 'customer_id', 'guarantor_id']),
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
     * Loan applications that have documents, one row each, with a count.
     *
     * The flat document list stopped being readable once every application
     * started contributing a dozen slots -- an officer looking for "what did we
     * collect for APP-BRCOL-26090012" had to scan hundreds of rows. This is the
     * index they actually want; the documents themselves are one click away,
     * filtered by loan_application_id.
     *
     * Documents uploaded outside an application (the customer registration
     * form attaches them to the customer only) have no application to group
     * under, so their count is returned separately in `meta` rather than being
     * silently dropped -- nothing should become invisible just because the list
     * changed shape.
     */
    public function applications(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = LoanApplication::query()
                ->has('documents')
                ->withCount('documents')
                ->with([
                    'application:id,application_no',
                    'customer:'.Customer::SUMMARY_COLUMNS.',employment_status',
                    'loanProduct:id,name',
                ]);

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $applications = $query
                ->orderByDesc('updated_at')
                ->paginate($perPage);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan applications with documents retrieved successfully',
                'data'    => $applications,
                'meta'    => [
                    'unlinked_count' => Document::whereNull('loan_application_id')->count(),
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan applications with documents',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
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

            $filePath = $this->handleFileUpload(
                $request,
                'file',
                null,
                'documents',
                $data['document_name'] ?? ''
            );

            if ($filePath) {
                $data['file_path'] = $filePath;
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
                'data' => $document->load('uploader:'.User::SUMMARY_COLUMNS)
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
            $document = Document::with([
                // The list names whoever the document belongs to. Guarantor
                // documents carry no customer_id at all, so both relations are
                // loaded or those rows show a dash forever.
                'customer:'.Customer::SUMMARY_COLUMNS.',employment_status',
                'guarantor:id,customer_id,full_name,id_number,employment_status,employer_name',
                'loanApplication.application:id,application_no',
                'uploader:'.User::SUMMARY_COLUMNS,
            ])->find($id);

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

            $filePath = $this->handleFileUpload(
                $request,
                'file',
                $document->file_path,
                'documents',
                $document->document_name
            );

            if ($filePath) {
                $data['file_path'] = $filePath;
            }

            unset($data['file']);

            $document->update($data);

            $this->logActivity('UPDATE', 'Document', "Updated document: {$document->document_name}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Document updated successfully',
                'data' => $document->load('uploader:'.User::SUMMARY_COLUMNS)
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

            if ($document->file_path) {
                $this->deleteFile($document->file_path);
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
}
