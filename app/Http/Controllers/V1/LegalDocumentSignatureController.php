<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\LegalSignatureException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CaptureLegalSignatureRequest;
use App\Http\Requests\ClearLegalSignaturesRequest;
use App\Models\LegalDocument;
use App\Services\LegalDocumentSignatureService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * E-signatures on a legal document, drawn on screen at the branch: the lines
 * to sign, capturing one, and clearing them with a reason.
 */
class LegalDocumentSignatureController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(protected LegalDocumentSignatureService $signatures)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Legal Document Index|Legal Document Sign', only: ['index']),
            new Middleware('permission:Legal Document Sign', only: ['store']),
            new Middleware('permission:Legal Document Clear Signatures', only: ['clear']),
        ];
    }

    /** The lines to sign, who has signed (with the images to print), and whether it is complete. */
    public function index(string $id)
    {
        try {
            $document = LegalDocument::find($id);

            if (!$document) {
                return $this->notFound();
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Legal document signatures retrieved successfully',
                'data'    => $this->signatures->status($document),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve legal document signatures',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function store(CaptureLegalSignatureRequest $request, string $id)
    {
        try {
            $document = LegalDocument::find($id);

            if (!$document) {
                return $this->notFound();
            }

            $signature = $this->signatures->capture(
                $document,
                $request->input('signer'),
                $request->file('signature') ?? $request->input('signature')
            );

            $this->logActivity('SIGN', 'LegalDocument', "Captured the {$signature->signer_key} signature on legal document {$document->reference_no}", [
                'legal_document_id' => $document->id,
                'signature_id'      => $signature->id,
                'signer_key'        => $signature->signer_key,
                'signer_name'       => $signature->signer_name,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => "{$signature->signer_name} has signed",
                'data'    => $this->signatures->status($document->fresh()),
            ], 201);
        } catch (LegalSignatureException $e) {
            return $e->toResponse();
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to save the signature',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function clear(ClearLegalSignaturesRequest $request, string $id)
    {
        try {
            $document = LegalDocument::find($id);

            if (!$document) {
                return $this->notFound();
            }

            $count = $this->signatures->clear($document, $request->input('signer'), $request->input('reason'));

            $this->logActivity('CLEAR_SIGNATURES', 'LegalDocument', "Cleared {$count} signature(s) on legal document {$document->reference_no}", [
                'legal_document_id' => $document->id,
                'signer_key'        => $request->input('signer'),
                'reason'            => $request->input('reason'),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => "Cleared {$count} signature(s)",
                'data'    => $this->signatures->status($document->fresh()),
            ], 200);
        } catch (LegalSignatureException $e) {
            return $e->toResponse();
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to clear the signatures',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'Legal document not found',
        ], 404);
    }
}
