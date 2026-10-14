<?php

namespace App\Http\Controllers\V1;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLegalDocumentRequest;
use App\Http\Requests\UpdateLegalDocumentRequest;
use App\Models\Customer;
use App\Models\LegalDocument;
use App\Models\LegalDocumentTemplate;
use App\Models\LoanApplication;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use Illuminate\Http\JsonResponse;
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
    use ActivityLogTrait, ScopesToUserBranch;

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
            'loanApplication.loanProduct:id,name,code,loan_type_id',
            'loanApplication.loanProduct.loanType:id,code,title',
            'loanApplication.branch:id,name,code',
            'template:id,document_type,language,title,file_path,source_file_name',            
            'creator:' . User::SUMMARY_COLUMNS,
        ];
    }

    /**
     * The template a legal document is drawn from. The loan's product must
     * use the document type, and its loan type supplies the active template
     * for that type and language. A template id sent by the caller must be
     * exactly that template.
     *
     * @return LegalDocumentTemplate|JsonResponse the template, or the 422 to send back
     */
    private function templateFor(LoanApplication $loanApplication, string $documentType, string $language, ?int $templateId)
    {
        $loanApplication->loadMissing(['loanProduct.loanType', 'loanProduct.legalDocuments']);
        $product = $loanApplication->loanProduct;
        $loanTypeName = $product?->loanType?->title ?? 'this loan';

        $refuse = fn (string $field, string $message) => response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => [['field' => $field, 'messages' => [$message]]],
        ], 422);

        $productTypes = $product?->legalDocumentTypes() ?? [];
        if (!in_array($documentType, $productTypes, true)) {
            $productName = $product?->name ?? 'This loan product';

            return $refuse('document_type', $productTypes
                ? sprintf('%s does not use the %s. Its legal documents are: %s.', $productName,
                    LegalDocumentType::labelFor($documentType), implode(', ', array_map([LegalDocumentType::class, 'labelFor'], $productTypes)))
                : "No legal documents are set up for {$productName}. Choose them on the loan product first.");
        }

        $template = LegalDocumentTemplate::forLoanApplication($loanApplication)
            ->where('document_type', $documentType)
            ->where('language', $language)
            ->first();

        if ($template && ($templateId === null || $templateId === $template->id)) {
            return $template;
        }

        [$field, $message] = $template
            ? ['legal_document_template_id', "That template is not the {$loanTypeName} template for this document type and language."]
            : ['document_type', sprintf(
                'No active %s template in %s is registered for %s loans. Register one under Legal Templates first.',
                LegalDocumentType::labelFor($documentType),
                LegalDocumentTemplate::LANGUAGES[$language] ?? $language,
                $loanTypeName
            )];

        return $refuse($field, $message);
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

            // A branch officer sees their own branch's legal documents only.
            $this->scopeToUserBranchVia($query, ['loanApplication' => 'loan_application_id']);

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
                    'document_types' => LegalDocumentType::options(),
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
            $language = $data['language'] ?? 'en';

            // The loan's product decides the document types; its loan type, the template.
            $template = $this->templateFor(
                LoanApplication::findOrFail($data['loan_application_id']),
                $data['document_type'],
                $language,
                isset($data['legal_document_template_id']) ? (int) $data['legal_document_template_id'] : null
            );
            if ($template instanceof JsonResponse) {
                return $template;
            }

            $status = LegalDocumentStatus::from($data['status'] ?? LegalDocumentStatus::Pending->value);

            $document = DB::transaction(function () use ($data, $status, $template, $language) {
                // Inside the transaction so the reference counter is covered by
                // the same lock nextReference() takes.
                return LegalDocument::create([
                    'loan_application_id'        => $data['loan_application_id'],
                    'legal_document_template_id' => $template->id,
                    'reference_no'               => LegalDocument::nextReference(),
                    'document_type'              => $data['document_type'],
                    'language'                   => $language,
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


            // Re-checked against the product and loan type only when the edit
            // moves what the template depends on, so a document is still
            // editable for its details and status as it stands.
            $loanApplicationId = (int) ($data['loan_application_id'] ?? $document->loan_application_id);
            $documentType      = $data['document_type'] ?? $document->document_type;
            $language          = $data['language'] ?? $document->language;
            $templateId        = isset($data['legal_document_template_id']) ? (int) $data['legal_document_template_id'] : null;

            if ($loanApplicationId !== (int) $document->loan_application_id
                || $documentType !== $document->document_type
                || $language !== $document->language
                || ($templateId !== null && $templateId !== (int) $document->legal_document_template_id)) {
                $template = $this->templateFor(LoanApplication::findOrFail($loanApplicationId), $documentType, $language, $templateId);
                if ($template instanceof JsonResponse) {
                    return $template;
                }
                $data['legal_document_template_id'] = $template->id;
            }

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

            // The rows here are loan applications, which carry branch_id.
            $this->scopeToUserBranch($query);

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
