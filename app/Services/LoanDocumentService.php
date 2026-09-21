<?php

namespace App\Services;

use App\Models\Document;
use App\Models\LoanApplication;
use App\Models\LoanDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Owns the loan_documents link: which documents belong to an application's
 * file, and who reviewed or verified each of them on that application.
 *
 * Shared by LoanApplicationController and GroupLoanController so the document
 * audit trail is identical whatever the loan's shape.
 */
class LoanDocumentService
{
    /**
     * Attach every document that forms part of this application's file.
     *
     * Two sources, and both are needed:
     *
     *  - documents uploaded against the application itself, which carry its
     *    loan_application_id;
     *  - the documents of every customer on the application, which carry only
     *    a customer_id. These are the ones that were silently missing: an
     *    existing customer's papers already exist when the application is
     *    created, nothing ever stamped them with the new loan_application_id,
     *    and so the review scope matched nothing at all.
     *
     * Idempotent, so it is safe to call on creation and again before every
     * review and verify -- which is exactly what catches documents uploaded
     * after the application was created.
     *
     * @return int how many links were newly created
     */
    public function syncForApplication(LoanApplication $loanApplication): int
    {
        try {
            $customerIds = $loanApplication->notifiableCustomers()
                ->pluck('id')
                ->push($loanApplication->customer_id)
                ->filter()
                ->unique()
                ->all();

            $documentIds = Document::query()
                ->where(function ($query) use ($loanApplication, $customerIds) {
                    $query->where('loan_application_id', $loanApplication->id);

                    if (!empty($customerIds)) {
                        $query->orWhereIn('customer_id', $customerIds);
                    }
                })
                ->pluck('id');

            if ($documentIds->isEmpty()) {
                return 0;
            }

            $existing = LoanDocument::where('loan_application_id', $loanApplication->id)
                ->pluck('document_id')
                ->all();

            $missing = $documentIds->diff($existing)->values();

            foreach ($missing as $documentId) {
                LoanDocument::create([
                    'loan_application_id' => $loanApplication->id,
                    'document_id'         => $documentId,
                ]);
            }

            return $missing->count();
        } catch (\Throwable $th) {
            // The application itself is already saved. A failure to build the
            // document index must not turn a successful submission into a 500.
            Log::warning('Failed to sync loan documents', [
                'loan_application_id' => $loanApplication->id,
                'error'               => $th->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Record who checked which documents on this application.
     *
     * $documentIds null means the caller said nothing about documents, so
     * nothing is touched -- that is how an ordinary review with no checklist
     * submitted leaves the previous stamps alone. An empty array means the
     * caller explicitly cleared every tick, so every stamp is cleared.
     *
     * Only this application's rows are ever written. That is the whole point
     * of the table: the same document ticked on a different loan keeps its own
     * stamp, where before it was overwritten, and anything left unticked here
     * no longer erased another loan's record.
     */
    public function markChecked(
        LoanApplication $loanApplication,
        ?array $documentIds,
        string $byColumn,
        string $atColumn
    ): void {
        if ($documentIds === null) {
            return;
        }

        try {
            // Pick up anything uploaded since the application was created, so
            // a document added during review can still be ticked.
            $this->syncForApplication($loanApplication);

            $ids = array_values(array_unique(array_filter(array_map('intval', $documentIds))));

            $scope = LoanDocument::where('loan_application_id', $loanApplication->id);

            (clone $scope)->whereNotIn('document_id', $ids ?: [0])
                ->update([$byColumn => null, $atColumn => null]);

            if ($ids) {
                (clone $scope)->whereIn('document_id', $ids)
                    ->update([$byColumn => Auth::id(), $atColumn => now()]);
            }
        } catch (\Throwable $th) {
            Log::warning('Failed to mark checked loan documents', [
                'loan_application_id' => $loanApplication->id,
                'column'              => $byColumn,
                'error'               => $th->getMessage(),
            ]);
        }
    }
}
