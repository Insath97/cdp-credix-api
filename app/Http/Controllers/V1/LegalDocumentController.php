<?php

namespace App\Http\Controllers\V1;

use App\Enums\LegalDocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLegalDocumentRequest;
use App\Http\Requests\UpdateLegalDocumentRequest;
use App\Models\Customer;
use App\Models\LegalDocument;
use App\Models\LegalDocumentTemplate;
use App\Models\LoanApplication;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The legal documents drawn up against loan applications.
 *
 * A row starts Pending — queued for the legal desk — and reaches Created once
 * the agreement has actually been drawn up, at which point the date and the
 * officer are stamped. Those two stamps are written by this controller and
 * never taken from the caller: a document cannot claim to have been prepared
 * earlier, or by someone else, than it was.
 */
class LegalDocumentController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Legal Document Index',  only: ['index', 'show']),
            new Middleware('permission:Legal Document Create', only: ['store']),
            new Middleware('permission:Legal Document Update', only: ['update', 'recordPrint']),
            new Middleware('permission:Legal Document Toggle Status', only: ['toggleStatus']),
            new Middleware('permission:Legal Document Delete', only: ['destroy']),
        ];
    }

    /**
     * The relations every legal document is read with.
     *
     * The preparation and print screens need the borrower and the loan on the
     * same row — the document is meaningless without them — so they are loaded
     * once here rather than fetched per row by the client.
     */
    private function withRelations(): array
    {
        return [
            'loanApplication:id,application_id,customer_id,loan_product_id,branch_id,requested_amount,approved_amount,interest_rate,term_months,status,approval_reference_no',
            'loanApplication.application:id,application_no',
            'loanApplication.customer:' . Customer::SUMMARY_COLUMNS,
            'loanApplication.loanProduct:id,name,code',
            'loanApplication.branch:id,name,code',
            'template:id,loan_product_id,document_type,language,title,file_path,source_file_name',
            'creator:' . User::SUMMARY_COLUMNS,
        ];
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = LegalDocument::with($this->withRelations());

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->filled('document_type')) {
                $query->where('document_type', $request->document_type);
            }

            if ($request->filled('language')) {
                $query->where('language', $request->language);
            }

            // Accepts a comma-separated list, so the screen can ask for
            // "pending,created" in one call.
            if ($request->filled('status')) {
                $query->whereIn('status', array_filter(array_map('trim', explode(',', $request->status))));
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $documents = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LegalDocument', 'Legal documents index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'loan_application_id', 'document_type', 'language', 'status', 'is_active']),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal documents retrieved successfully',
                'data'    => $documents,
                'meta'    => [
                    'statuses'       => LegalDocumentStatus::values(),
                    'document_types' => LegalDocumentTemplate::TYPES,
                    'languages'      => LegalDocumentTemplate::LANGUAGES,
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve legal documents',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function store(CreateLegalDocumentRequest $request)
    {
        try {
            $data = $request->validated();

            $status = LegalDocumentStatus::from($data['status'] ?? LegalDocumentStatus::Pending->value);

            $document = DB::transaction(function () use ($data, $status) {
                // Inside the transaction so the reference counter is covered by
                // the same lock nextReference() takes.
                return LegalDocument::create([
                    'loan_application_id'        => $data['loan_application_id'],
                    'legal_document_template_id' => $data['legal_document_template_id'] ?? null,
                    'reference_no'               => LegalDocument::nextReference(),
                    'document_type'              => $data['document_type'],
                    'language'                   => $data['language'] ?? 'en',
                    'status'                     => $status,
                    // Stamped only on Created. A Pending row has not been drawn
                    // up yet, so it has no creation date to show.
                    'legal_document_created_date' => $status === LegalDocumentStatus::Created ? now() : null,
                    'created_by'                 => Auth::id(),
                    'details'                    => $data['details'] ?? null,
                    'remarks'                    => $data['remarks'] ?? null,
                    'is_active'                  => $data['is_active'] ?? true,
                ]);
            });

            $this->logActivity('CREATE', 'LegalDocument', "Created legal document {$document->reference_no}", [
                'legal_document_id'   => $document->id,
                'loan_application_id' => $document->loan_application_id,
                'status'              => $status->value,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document created successfully',
                'data'    => $document->load($this->withRelations()),
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create legal document',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $document = LegalDocument::with($this->withRelations())->find($id);

            if (!$document) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Legal document not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document retrieved successfully',
                'data'    => $document,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve legal document',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function update(UpdateLegalDocumentRequest $request, string $id)
    {
        try {
            $document = LegalDocument::find($id);

            if (!$document) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Legal document not found',
                ], 404);
            }

            $data = $request->validated();

            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $to = LegalDocumentStatus::from($data['status']);

                // Stamped the first time it reaches Created and never
                // rewritten: "when was this drawn up" has to survive every
                // later edit. Going back to Pending clears it, because a
                // document that is no longer prepared has no such date.
                if ($to === LegalDocumentStatus::Created) {
                    if ($document->legal_document_created_date === null) {
                        $data['legal_document_created_date'] = now();
                    }
                    if ($document->created_by === null) {
                        $data['created_by'] = Auth::id();
                    }
                } else {
                    $data['legal_document_created_date'] = null;
                }
            }

            $document->update($data);

            $this->logActivity('UPDATE', 'LegalDocument', "Updated legal document {$document->reference_no}", [
                'legal_document_id' => $document->id,
                'status'            => $document->status?->value,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document updated successfully',
                'data'    => $document->fresh($this->withRelations()),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update legal document',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $document = LegalDocument::find($id);

            if (!$document) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Legal document not found',
                ], 404);
            }

            $reference = $document->reference_no;
            $document->delete();

            $this->logActivity('DELETE', 'LegalDocument', "Deleted legal document {$reference}");

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete legal document',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Record that a copy of this agreement was printed.
     *
     * The count is incremented here rather than accepted from the caller: "we
     * issued three originals" is a fact about what left the printer, and a
     * number a client can set to anything it likes is not that fact.
     */
    public function recordPrint(string $id)
    {
        try {
            $document = LegalDocument::find($id);

            if (!$document) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Legal document not found',
                ], 404);
            }

            $document->increment('printed_count');
            $document->update(['last_printed_at' => now()]);

            $this->logActivity('PRINT', 'LegalDocument', "Printed legal document {$document->reference_no}", [
                'legal_document_id' => $document->id,
                'printed_count'     => $document->printed_count,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document print recorded successfully',
                'data'    => $document->fresh($this->withRelations()),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to record the legal document print',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Flip the visibility flag.
     *
     * Note this moves `is_active` — not `status`, which is the legal workflow
     * (pending / created) and is moved through update().
     */
    public function toggleStatus(string $id)
    {
        try {
            $document = LegalDocument::find($id);

            if (!$document) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Legal document not found',
                ], 404);
            }

            $document->update(['is_active' => !$document->is_active]);

            Log::info('Legal document status toggled', [
                'user_id'           => Auth::id(),
                'legal_document_id' => $document->id,
                'is_active'         => $document->is_active,
            ]);

            $this->logActivity('TOGGLE_STATUS', 'LegalDocument', "Toggled legal document {$document->reference_no}", [
                'legal_document_id' => $document->id,
                'is_active'         => $document->is_active,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document ' . ($document->is_active ? 'activated' : 'deactivated') . ' successfully',
                // Same shape as index / show / store / update: the whole record
                // with its relations, so a caller never has to know which
                // endpoint it called to know what comes back.
                'data'    => $document->fresh($this->withRelations()),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to toggle legal document status',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Loan applications with the legal state the Prepare Documents tab reads.
     *
     * The screen otherwise had to fetch every application and then every legal
     * document and join them in the browser; this answers its one question --
     * which files still need paperwork -- in a single call.
     */
    public function applications(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = LoanApplication::with([
                'application:id,application_no',
                'customer:' . Customer::SUMMARY_COLUMNS,
                'loanProduct:id,name,code',
                'branch:id,name,code',
                'legalDocuments:id,loan_application_id,reference_no,document_type,language,status,legal_document_created_date',
            ]);

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('status')) {
                $query->whereIn('status', array_filter(array_map('trim', explode(',', $request->status))));
            }

            // ?legal_state=not_prepared | prepared
            if ($request->filled('legal_state')) {
                $request->legal_state === 'prepared'
                    ? $query->has('legalDocuments')
                    : $query->doesntHave('legalDocuments');
            }

            $applications = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan applications retrieved successfully',
                'data'    => $applications,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan applications',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
