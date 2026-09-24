<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoanApplication;
use App\Models\LoanRevision;
use App\Models\Customer;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use App\Traits\FileUploadTrait;
use App\Http\Requests\CreateLoanRevisionRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Enums\LoanRevisionStatus;
use App\Exceptions\InvalidLoanRevisionTransitionException;
use App\Services\LoanRevisionService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;

class LoanRevisionController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait, ScopesToUserBranch;

    public function __construct(
        protected LoanRevisionService $revisionService,
        protected NotificationService $notificationService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Revision Index',   only: ['index', 'show', 'downloadDocument']),
            new Middleware('permission:Loan Revision Create',  only: ['store']),
            new Middleware('permission:Loan Revision Approve', only: ['approve']),
            new Middleware('permission:Loan Revision Reject',  only: ['reject']),
            new Middleware('permission:Loan Revision Cancel',  only: ['cancel']),
        ];
    }

    /**
     * Display a listing of loan revisions.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanRevision::with([
                'loanApplication.customer:'.Customer::SUMMARY_COLUMNS,
                'loanApplication.application',
                'requester',
                'approver:'.User::SUMMARY_COLUMNS,
            ]);

            if ($request->filled('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // A branch officer sees their own branch's rows only;
            // the branch is reached through the parent records.
            $this->scopeToUserBranchVia($query, ['loanApplication' => 'loan_application_id']);

            $revisions = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanRevision', 'Loan revisions index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'status']),
                'count'   => $revisions->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan revisions retrieved successfully',
                'data'    => $revisions,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan revisions',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new loan revision, submitted directly for approval.
     */
    public function store(CreateLoanRevisionRequest $request)
    {
        try {
            $data = $request->validated();

            $loanApplication = LoanApplication::find($data['loan_application_id']);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($request->hasFile('document')) {
                $data['document'] = $this->handleFileUpload($request, 'document', null, 'loan-revisions');
            }

            $revision = $this->revisionService->create($loanApplication, $data, Auth::id());

            $this->logActivity('CREATE', 'LoanRevision', "Created loan revision ID: {$revision->id} for loan application ID: {$loanApplication->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan revision created and submitted for approval',
                'data'    => $revision->load(['loanApplication', 'requester']),
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create loan revision',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan revision.
     */
    public function show(string $id)
    {
        try {
            $revision = LoanRevision::with([
                'loanApplication.customer:'.Customer::SUMMARY_COLUMNS,
                'loanApplication.application',
                'requester',
                'approver:'.User::SUMMARY_COLUMNS,
                'installments',
            ])->find($id);

            if (!$revision) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan revision not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan revision retrieved successfully',
                'data'    => $revision,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan revision',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a loan revision pending approval, activating the revised schedule.
     */
    public function approve(Request $request, string $id)
    {
        try {
            $revision = LoanRevision::find($id);

            if (!$revision) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan revision not found',
                ], 404);
            }

            $revision = $this->revisionService->transition(
                $revision,
                LoanRevisionStatus::Approved,
                Auth::id(),
                $request->input('remarks')
            );

            $this->logActivity('UPDATE', 'LoanRevision', "Loan revision ID: {$revision->id} approved", [
                'loan_revision_id' => $revision->id,
            ]);

            $loanApplication = $revision->loanApplication;
            if ($loanApplication) {
                $revisionMessage = "Your loan repayment schedule has been revised. New installment: {$revision->revised_installment_amount}. Effective {$revision->effective_date}.";

                foreach ($loanApplication->notifiableCustomers() as $notifyCustomer) {
                    if (!empty($notifyCustomer->phone_primary)) {
                        $this->notificationService->sendSms(
                            'loan_revision_approved',
                            $notifyCustomer->phone_primary,
                            $revisionMessage,
                            ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                        );
                    }

                    if ($loanApplication->isJointLoan() && !empty($notifyCustomer->email)) {
                        $this->notificationService->sendEmail(
                            'loan_revision_approved',
                            $notifyCustomer->email,
                            'Loan Revision Approved',
                            $revisionMessage,
                            ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                        );
                    }
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan revision approved successfully',
                'data'    => $revision,
            ], 200);

        } catch (InvalidLoanRevisionTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to approve loan revision',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject a loan revision pending approval.
     */
    public function reject(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'remarks' => 'required|string|min:3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $revision = LoanRevision::find($id);

            if (!$revision) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan revision not found',
                ], 404);
            }

            $revision = $this->revisionService->transition(
                $revision,
                LoanRevisionStatus::Rejected,
                Auth::id(),
                $request->input('remarks')
            );

            $this->logActivity('UPDATE', 'LoanRevision', "Loan revision ID: {$revision->id} rejected", [
                'loan_revision_id' => $revision->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan revision rejected successfully',
                'data'    => $revision,
            ], 200);

        } catch (InvalidLoanRevisionTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to reject loan revision',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Cancel a loan revision pending approval.
     */
    public function cancel(Request $request, string $id)
    {
        try {
            $revision = LoanRevision::find($id);

            if (!$revision) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan revision not found',
                ], 404);
            }

            $revision = $this->revisionService->transition(
                $revision,
                LoanRevisionStatus::Cancelled,
                Auth::id(),
                $request->input('remarks')
            );

            $this->logActivity('UPDATE', 'LoanRevision', "Loan revision ID: {$revision->id} cancelled", [
                'loan_revision_id' => $revision->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan revision cancelled successfully',
                'data'    => $revision,
            ], 200);

        } catch (InvalidLoanRevisionTransitionException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to cancel loan revision',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Stream the supporting document attached to a loan revision, so admin/
     * authorized staff can view or download the customer's evidence during
     * the approval/rejection process.
     */
    public function downloadDocument(string $id)
    {
        try {
            $revision = LoanRevision::find($id);

            if (!$revision || !$revision->document) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan revision document not found',
                ], 404);
            }

            // Resolved and contained through FileUploadTrait, not public_path().
            // Uploads now live outside the web root, and the resolver refuses a
            // stored path that escapes its base directory.
            $absolutePath = $this->resolveStoredFile($revision->document);

            if ($absolutePath === null) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Document file is missing',
                ], 404);
            }

            $this->logActivity('Show', 'LoanRevision', "Downloaded supporting document for loan revision ID: {$revision->id}", [
                'user_id' => Auth::id(),
                'loan_revision_id' => $revision->id,
            ]);

            return response()->file($absolutePath);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to download loan revision document',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
