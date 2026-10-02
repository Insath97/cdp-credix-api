<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLegalDocumentTemplateRequest;
use App\Http\Requests\UpdateLegalDocumentTemplateRequest;
use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentTemplate;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * The legal paperwork registered against each loan type.
 *
 * Which agreement a loan is drawn up on is configuration, not code: the legal
 * desk decides it per loan type and per language, and a loan application's
 * documents follow its loan type (application -> product -> loan type).
 */
class LegalDocumentTemplateController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    private const WITH_LOAN_TYPE = 'loanType:id,code,title';

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Legal Template Index',  only: ['index', 'show', 'getActiveList']),
            new Middleware('permission:Legal Template Index|Legal Document Index|Legal Document Create', only: ['forApplication']),
            new Middleware('permission:Legal Template Create', only: ['store']),
            new Middleware('permission:Legal Template Update', only: ['update']),
            new Middleware('permission:Legal Template Toggle Status', only: ['toggleStatus']),
            new Middleware('permission:Legal Template Delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = LegalDocumentTemplate::with([
                self::WITH_LOAN_TYPE,
                'creator:' . User::SUMMARY_COLUMNS,
            ]);

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $this->filterByLoanType($query, $request);

            if ($request->filled('document_type')) {
                $query->where('document_type', $request->document_type);
            }

            if ($request->filled('language')) {
                $query->where('language', $request->language);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $templates = $query->orderBy('loan_type_id')->orderBy('document_type')->paginate($perPage);

            $this->logActivity('Index', 'LegalDocumentTemplate', 'Legal document templates index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'loan_type_id', 'loan_product_id', 'document_type', 'language', 'is_active']),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document templates retrieved successfully',
                'data'    => $templates,
                // The screens build their type and language pickers from these
                // rather than keeping their own copy that can drift.
                'meta'    => [
                    'document_types' => LegalDocumentType::options(),
                    'languages'      => LegalDocumentTemplate::LANGUAGES,
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve legal document templates',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Every active template, unpaginated — for the preparation screen, which
     * has to offer whatever the loan's loan type actually has.
     */
    public function getActiveList(Request $request)
    {
        try {
            $query = LegalDocumentTemplate::where('is_active', true)
                ->with(self::WITH_LOAN_TYPE);

            $this->filterByLoanType($query, $request);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document templates retrieved successfully',
                'data'    => $query->orderBy('document_type')->get(),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve legal document templates',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * A loan application's legal paperwork: the documents its product needs,
     * each with the active templates of its loan type
     * (application -> product -> loan type).
     *
     *   GET /legal-document-templates/application/{loanApplicationId}?language=ta
     */
    public function forApplication(Request $request, string $loanApplicationId)
    {
        try {
            $loanApplication = LoanApplication::with([
                'application:id,application_no',
                'loanProduct:id,name,code,loan_type_id',
                'loanProduct.loanType:id,code,title',
                'loanProduct.legalDocuments',
            ])->find($loanApplicationId);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            $product = $loanApplication->loanProduct;
            $productTypes = $product?->legalDocumentTypes() ?? [];

            $templates = LegalDocumentTemplate::forLoanApplication($loanApplication)
                ->whereIn('document_type', $productTypes)
                ->with(self::WITH_LOAN_TYPE)
                ->when($request->filled('language'), fn ($q) => $q->where('language', $request->language))
                ->orderBy('document_type')
                ->orderBy('language')
                ->get();

            // The product's documents in the enum's order, each with the
            // languages it has a template in.
            $documentTypes = collect(LegalDocumentType::cases())
                ->filter(fn (LegalDocumentType $type) => in_array($type->value, $productTypes, true))
                ->map(fn (LegalDocumentType $type) => [
                    'value'     => $type->value,
                    'label'     => $type->label(),
                    'languages' => $templates->where('document_type', $type->value)->pluck('language')->values(),
                ])
                ->values();

            return response()->json([
                'status'  => 'success',
                'message' => $productTypes
                    ? 'Legal document templates for the loan application retrieved successfully'
                    : 'No legal documents are set up for ' . ($product?->name ?? 'this loan product') . '. Choose them on the loan product first.',
                'data'    => [
                    'loan_application_id' => (int) $loanApplication->id,
                    'application_no'      => $loanApplication->application?->application_no,
                    'loan_product'        => $product?->only(['id', 'name', 'code']),
                    'loan_type'           => $product?->loanType?->only(['id', 'code', 'title']),
                    'document_types'      => $documentTypes,
                    'templates'           => $templates,
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve the legal document templates for the loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * ?loan_type_id=, or the loan type of ?loan_product_id= -- the filter the
     * screens sent when templates were per product.
     */
    private function filterByLoanType(Builder $query, Request $request): void
    {
        $loanTypeId = $request->filled('loan_type_id')
            ? $request->integer('loan_type_id')
            : ($request->filled('loan_product_id')
                ? LoanProduct::whereKey($request->integer('loan_product_id'))->value('loan_type_id')
                : null);

        if ($request->filled('loan_type_id') || $request->filled('loan_product_id')) {
            $query->where('loan_type_id', $loanTypeId);
        }
    }

    public function store(CreateLegalDocumentTemplateRequest $request)
    {
        try {
            $data = $request->validated();

            $filePath = $this->handleFileUpload($request, 'file', null, 'legal-templates');
            if ($filePath) {
                $data['file_path'] = $filePath;
                $data['source_file_name'] = $request->file('file')->getClientOriginalName();
            }
            unset($data['file']);

            unset($data['loan_product_id']);   // only ever a stand-in for its loan type

            $data['language'] = $data['language'] ?? 'en';
            $data['created_by'] = Auth::id();

            $template = LegalDocumentTemplate::create($data);

            $this->logActivity('CREATE', 'LegalDocumentTemplate', "Created legal document template ID: {$template->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document template created successfully',
                'data'    => $template->load([self::WITH_LOAN_TYPE, 'creator:' . User::SUMMARY_COLUMNS]),
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create legal document template',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $template = LegalDocumentTemplate::with([
                self::WITH_LOAN_TYPE,
                'creator:' . User::SUMMARY_COLUMNS,
            ])->find($id);

            if (!$template) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Legal document template not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document template retrieved successfully',
                'data'    => $template,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve legal document template',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function update(UpdateLegalDocumentTemplateRequest $request, string $id)
    {
        try {
            $template = LegalDocumentTemplate::find($id);

            if (!$template) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Legal document template not found',
                ], 404);
            }

            $data = $request->validated();

            // Replacing the source file removes the old one; leaving it out
            // keeps whatever is on record.
            $filePath = $this->handleFileUpload($request, 'file', $template->file_path, 'legal-templates');
            if ($filePath) {
                $data['file_path'] = $filePath;
                $data['source_file_name'] = $request->file('file')->getClientOriginalName();
            }
            unset($data['file'], $data['loan_product_id']);

            $template->update($data);

            $this->logActivity('UPDATE', 'LegalDocumentTemplate', "Updated legal document template ID: {$template->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document template updated successfully',
                'data'    => $template->fresh([self::WITH_LOAN_TYPE, 'creator:' . User::SUMMARY_COLUMNS]),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update legal document template',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $template = LegalDocumentTemplate::withCount('legalDocuments')->find($id);

            if (!$template) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Legal document template not found',
                ], 404);
            }

            // Refuse while it is still in use. The documents drawn from it keep
            // their own snapshot of type and language, so nothing would break —
            // but removing the template a live agreement points at should be a
            // decision, not an accident. Deactivating is the way to retire one.
            if ($template->legal_documents_count > 0) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "This template has {$template->legal_documents_count} document(s) prepared from it. Deactivate it instead of deleting it.",
                ], 422);
            }

            // Hard delete, not a soft one. MySQL's unique index counts
            // soft-deleted rows, so a ghost row would keep holding the
            // (product, type, language) slot and the next attempt to register
            // that template would fail on a constraint the validator -- which
            // correctly ignores trashed rows -- had just said was free.
            // Nothing is lost: a template with documents drawn from it is
            // refused above, so only unused ones ever reach here.
            $template->forceDelete();

            $this->logActivity('DELETE', 'LegalDocumentTemplate', "Deleted legal document template ID: {$id}");

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document template deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete legal document template',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function toggleStatus(string $id)
    {
        try {
            $template = LegalDocumentTemplate::find($id);

            if (!$template) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Legal document template not found',
                ], 404);
            }

            $template->update(['is_active' => !$template->is_active]);

            Log::info('Legal document template status toggled', [
                'user_id'     => Auth::id(),
                'template_id' => $template->id,
                'is_active'   => $template->is_active,
            ]);

            $this->logActivity('TOGGLE_STATUS', 'LegalDocumentTemplate', "Toggled legal document template ID: {$template->id}", [
                'template_id' => $template->id,
                'is_active'   => $template->is_active,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document template ' . ($template->is_active ? 'activated' : 'deactivated') . ' successfully',
                // Every action on this controller answers with the whole
                // record, loaded the same way.
                'data'    => $template->fresh([self::WITH_LOAN_TYPE, 'creator:' . User::SUMMARY_COLUMNS]),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to toggle legal document template status',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
