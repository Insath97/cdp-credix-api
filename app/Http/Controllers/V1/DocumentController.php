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
use App\Services\LoanDocumentService;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use App\Traits\FileUploadTrait;
use App\Http\Requests\CreateDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DocumentController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait, ScopesToUserBranch;

    public function __construct(
        protected LoanDocumentService $loanDocumentService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            // 'applications' was in none of these lists, so the feed behind the
            // document upload screen -- every loan application, with the
            // customer's name, phone, email, product, branch and status --
            // answered any authenticated principal, a customer's portal token
            // included.
            new Middleware('permission:Document Index', only: ['index', 'show', 'applications']),
            new Middleware('permission:Document Create', only: ['store']),
            new Middleware('permission:Document Update', only: ['update']),
            new Middleware('permission:Document Delete', only: ['destroy']),
        ];
    }

    /**
     * Refuse to add, change or remove documents once the loan has been paid out.
     *
     * The upload screen already stops offering disbursed applications, but a
     * screen is not a rule -- document ids are sequential and the update and
     * delete endpoints are reachable directly. The freeze is enforced here so
     * that "nobody can change a disbursed file's documents" is actually true
     * rather than merely not offered.
     *
     * Documents that hang off no loan application at all (the customer
     * registration form uploads those) are never frozen.
     *
     * Returns a 422 response when the change must be refused, or null.
     */
    private function frozenApplicationResponse($loanApplicationId)
    {
        if (empty($loanApplicationId)) {
            return null;
        }

        $loanApplication = LoanApplication::select('id', 'application_id', 'status')
            ->with('application:id,application_no')
            ->find($loanApplicationId);

        if (!$loanApplication || $loanApplication->status->allowsDocumentChanges()) {
            return null;
        }

        $reference = $loanApplication->application?->application_no ?? "ID {$loanApplication->id}";

        return response()->json([
            'status'  => 'error',
            'message' => "Loan application {$reference} is {$loanApplication->status->value}. Its documents are the record the disbursement was made on and can no longer be changed.",
            'errors'  => [
                'loan_application_id' => $loanApplication->id,
                'status'              => $loanApplication->status->value,
            ],
        ], 422);
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
                // Who stamped this paper off during review and then again
                // during verification — so each document card can print a real
                // name ("Reviewed by Alice · 12 Jan") instead of two bare ids.
                'reviewer:'.User::SUMMARY_COLUMNS,
                'verifier:'.User::SUMMARY_COLUMNS,
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
                $loanApplicationId = $request->loan_application_id;

                // A document can name a customer only -- the customer
                // registration form and the New Loan customer step both attach
                // files before the application exists -- and a customer's
                // papers are meant to follow them across loans. The document
                // view for one application must still surface those rows, or
                // "what did the customer hand in" silently drops everything
                // they uploaded without a loan_application_id.
                $customerIds = LoanApplication::where('id', $loanApplicationId)
                    ->with(['loanApplicationCustomers' => fn ($query) => $query->select('loan_application_id', 'customer_id')])
                    ->get(['id', 'customer_id'])
                    ->flatMap(fn ($application) => array_merge(
                        [$application->customer_id],
                        $application->loanApplicationCustomers->pluck('customer_id')->all(),
                    ))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $query->where(function ($query) use ($loanApplicationId, $customerIds) {
                    $query->where('loan_application_id', $loanApplicationId);
                    if (!empty($customerIds)) {
                        $query->orWhereIn('customer_id', $customerIds);
                    }
                });

                // Who checked each document ON THIS APPLICATION. The stamps
                // used to sit on the document row, which meant a customer's
                // shared NIC copy showed whichever loan had looked at it last.
                // Scoped to the one application asked for, so the checklist
                // reads its own verdicts and nobody else's.
                $query->with(['loanDocuments' => fn ($q) => $q
                    ->where('loan_application_id', $loanApplicationId)
                    ->with(['reviewedBy:'.User::SUMMARY_COLUMNS, 'verifiedBy:'.User::SUMMARY_COLUMNS])]);
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

            // A branch officer sees their own branch's documents only; the
            // branch comes off whichever parent the document actually has.
            $this->scopeToUserBranchVia($query, ['loanApplication' => 'loan_application_id', 'customer' => 'customer_id']);

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

            // The rows here are loan applications, which carry branch_id.
            $this->scopeToUserBranch($query);

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
    /**
     * Attach a freshly uploaded document to the loan applications it belongs to.
     *
     * The link is what carries the per-application review and verify stamps,
     * so a document that never gets indexed can never be ticked off.
     */
    private function indexNewDocument(Document $document): void
    {
        $applications = collect();

        if ($document->loan_application_id) {
            $applications = LoanApplication::where('id', $document->loan_application_id)->get();
        } elseif ($document->customer_id) {
            // Only files still open to change. A disbursed or closed loan's
            // paperwork is settled, and LoanApplicationStatus::allowsDocumentChanges()
            // is the same test frozenApplicationResponse() uses to refuse edits.
            $applications = LoanApplication::where(function ($query) use ($document) {
                $query->where('customer_id', $document->customer_id)
                    ->orWhereHas('loanApplicationCustomers', fn ($q) => $q->where('customer_id', $document->customer_id));
            })->get();

            $applications = $applications->filter(
                fn (LoanApplication $application) => $application->status->allowsDocumentChanges()
            );
        }

        foreach ($applications as $application) {
            $this->loanDocumentService->syncForApplication($application);
        }
    }

    public function store(CreateDocumentRequest $request)
    {
        try {
            $data = $request->validated();

            if ($frozen = $this->frozenApplicationResponse($data['loan_application_id'] ?? null)) {
                return $frozen;
            }

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

            // Index it against the applications whose file it belongs to.
            //
            // Uploaded for one application: that application. Uploaded against
            // the customer instead (their NIC copy, their pay slips), it joins
            // the file of every application of theirs still being decided --
            // otherwise a document handed in mid-review would never appear on
            // the checklist the officer is about to tick.
            $this->indexNewDocument($document);

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
            ]);

            // Confined the same way index() is. A bare find($id) meant the
            // branch filter stopped at the listing, and any document was one
            // guessed id away from any officer.
            $this->scopeToUserBranchVia($document, ['loanApplication' => 'loan_application_id', 'customer' => 'customer_id']);

            $document = $document->find($id);

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
            // Confined the same way index() is, so the branch rule is an
            // access rule rather than a listing filter. A bare find($id)
            // left every document one guessed id away from any officer.
            $document = $this->scopeToUserBranchVia(
                Document::query(),
                ['loanApplication' => 'loan_application_id', 'customer' => 'customer_id'],
            )->find($id);

            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found'
                ], 404);
            }

            $data = $request->validated();

            // Both ends: the application the document is on now, and the one
            // it is being moved to. Either being disbursed freezes the change.
            foreach ([$document->loan_application_id, $data['loan_application_id'] ?? null] as $applicationId) {
                if ($frozen = $this->frozenApplicationResponse($applicationId)) {
                    return $frozen;
                }
            }

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
            // Confined the same way index() is, so the branch rule is an
            // access rule rather than a listing filter. A bare find($id)
            // left every document one guessed id away from any officer.
            $document = $this->scopeToUserBranchVia(
                Document::query(),
                ['loanApplication' => 'loan_application_id', 'customer' => 'customer_id'],
            )->find($id);

            if (!$document) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found'
                ], 404);
            }

            if ($frozen = $this->frozenApplicationResponse($document->loan_application_id)) {
                return $frozen;
            }

            // The file stays. The row soft-deletes, so it can be restored,
            // and destroying the bytes here left every restored document
            // pointing at nothing. Only a force-delete should remove them.
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
