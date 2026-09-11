<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLegalDocumentTemplateRequest;
use App\Http\Requests\UpdateLegalDocumentTemplateRequest;
use App\Models\LegalDocumentTemplate;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * The legal paperwork registered against each loan product.
 *
 * Which agreement a product is sold on is configuration, not code: the legal
 * desk decides it, it differs per product and per language, and it changes
 * when a product does.
 */
class LegalDocumentTemplateController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Legal Template Index',  only: ['index', 'show', 'getActiveList']),
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
                'loanProduct:id,name,code,loan_type_id,loan_term_id',
                'creator:' . User::SUMMARY_COLUMNS,
            ]);

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            if ($request->filled('loan_product_id')) {
                $query->where('loan_product_id', $request->loan_product_id);
            }

            if ($request->filled('document_type')) {
                $query->where('document_type', $request->document_type);
            }

            if ($request->filled('language')) {
                $query->where('language', $request->language);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $templates = $query->orderBy('loan_product_id')->orderBy('document_type')->paginate($perPage);

            $this->logActivity('Index', 'LegalDocumentTemplate', 'Legal document templates index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'loan_product_id', 'document_type', 'language', 'is_active']),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document templates retrieved successfully',
                'data'    => $templates,
                // The screens build their type and language pickers from these
                // rather than keeping their own copy that can drift.
                'meta'    => [
                    'document_types' => LegalDocumentTemplate::TYPES,
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
     * has to offer whatever the chosen product actually has.
     */
    public function getActiveList(Request $request)
    {
        try {
            $query = LegalDocumentTemplate::where('is_active', true)
                ->with('loanProduct:id,name,code');

            if ($request->filled('loan_product_id')) {
                $query->where('loan_product_id', $request->loan_product_id);
            }

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

            $data['language'] = $data['language'] ?? 'en';
            $data['created_by'] = Auth::id();

            $template = LegalDocumentTemplate::create($data);

            $this->logActivity('CREATE', 'LegalDocumentTemplate', "Created legal document template ID: {$template->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document template created successfully',
                'data'    => $template->load(['loanProduct:id,name,code', 'creator:' . User::SUMMARY_COLUMNS]),
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
                'loanProduct:id,name,code,loan_type_id,loan_term_id',
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
            unset($data['file']);

            $template->update($data);

            $this->logActivity('UPDATE', 'LegalDocumentTemplate', "Updated legal document template ID: {$template->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document template updated successfully',
                'data'    => $template->fresh(['loanProduct:id,name,code', 'creator:' . User::SUMMARY_COLUMNS]),
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
                'data'    => $template->fresh(['loanProduct:id,name,code', 'creator:' . User::SUMMARY_COLUMNS]),
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
