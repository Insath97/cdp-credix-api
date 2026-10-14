<?php

namespace App\Services;

use App\Enums\LoanApplicationStatus;
use App\Models\Customer;
use App\Models\CustomerBankDetail;
use App\Models\CustomerDetailCorrectionRequest;
use App\Models\Guarantor;
use App\Models\LoanApplication;
use App\Models\LoanApplicationGuarantor;
use App\Models\LoanCustomerConfirmation;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Corrections the customer asks for from their loan review link.
 *
 * Submitting a request changes nothing. Staff review it and either reject it
 * or approve it, which writes the value to its target record -- a borrower,
 * a guarantor, a guarantor link or the loan itself -- and keeps the old value,
 * the new value and any knock-on changes on the request as its audit trail.
 * Approving takes the review permission plus the target's own edit
 * permission. Once no request is open on the loan, a fresh review link goes
 * out so the customer confirms the corrected details.
 */
class CustomerCorrectionService
{
    public const MAX_OPEN_PER_CUSTOMER = 10;

    public const REVIEW_PERMISSION = 'Loan Customer Correction Review';
    public const VIEW_PERMISSION = 'Loan Customer Correction Index';
    // Bank details of a customer on a live loan (see GuardsCustomerLoanRecords).
    public const LIVE_LOAN_OVERRIDE_PERMISSION = 'Customer Financials Live Loan Override';

    /**
     * Customer fields a correction can write, with their validation. A change
     * to the registered phone or the NIC resets KYC (OTP 1); the rest are
     * confirmed again by OTP 2 alone.
     */
    public const CUSTOMER_FIELDS = [
        'full_name'          => ['label' => 'Full Name', 'rules' => ['required', 'string', 'max:500']],
        'name_with_initials' => ['label' => 'Name with Initials', 'rules' => ['required', 'string', 'max:255']],
        'id_number'          => ['label' => 'ID Number (NIC/Passport)', 'rules' => ['required', 'string', 'max:50']],
        'date_of_birth'      => ['label' => 'Date of Birth', 'rules' => ['required', 'date', 'before:today']],
        'phone_primary'      => ['label' => 'Primary Phone Number', 'rules' => ['required', 'string', 'max:20']],
        'phone_secondary'    => ['label' => 'Secondary Phone Number', 'rules' => ['nullable', 'string', 'max:20']],
        'email'              => ['label' => 'Email Address', 'rules' => ['required', 'email', 'max:255']],
        'whatsapp_number'    => ['label' => 'WhatsApp Number', 'rules' => ['nullable', 'string', 'max:20']],
        'address_line_1'     => ['label' => 'Address Line 1', 'rules' => ['required', 'string', 'max:255']],
        'address_line_2'     => ['label' => 'Address Line 2', 'rules' => ['nullable', 'string', 'max:255']],
        'landmark'           => ['label' => 'Landmark', 'rules' => ['nullable', 'string', 'max:255']],
        'city'               => ['label' => 'City', 'rules' => ['required', 'string', 'max:255']],
        'state'              => ['label' => 'State', 'rules' => ['nullable', 'string', 'max:255']],
        'postal_code'        => ['label' => 'Postal Code', 'rules' => ['nullable', 'string', 'max:255']],
        'employment_status'  => ['label' => 'Employment Status', 'rules' => ['nullable', 'string', 'max:50']],
        'occupation'         => ['label' => 'Occupation', 'rules' => ['nullable', 'string', 'max:255']],
        'employer_name'      => ['label' => 'Employer Name', 'rules' => ['nullable', 'string', 'max:255']],
        'employer_phone'     => ['label' => 'Employer Phone', 'rules' => ['nullable', 'string', 'max:20']],
        'employer_email'     => ['label' => 'Employer Email', 'rules' => ['nullable', 'email', 'max:255']],
        'business_name'      => ['label' => 'Business Name', 'rules' => ['nullable', 'string', 'max:255']],
        'monthly_income'     => ['label' => 'Monthly Income', 'rules' => ['nullable', 'numeric', 'min:0']],
    ];

    public const KYC_RESET_FIELDS = ['phone_primary', 'id_number'];

    public const GUARANTOR_FIELDS = [
        'full_name'     => ['label' => 'Full Name', 'rules' => ['required', 'string', 'max:500']],
        'id_number'     => ['label' => 'ID Number', 'rules' => ['required', 'string', 'max:50']],
        'date_of_birth' => ['label' => 'Date of Birth', 'rules' => ['nullable', 'date', 'before:today']],
        'phone_primary' => ['label' => 'Phone Number', 'rules' => ['required', 'string', 'max:20']],
        'occupation'    => ['label' => 'Occupation', 'rules' => ['nullable', 'string', 'max:255']],
        'employer_name' => ['label' => 'Employer Name', 'rules' => ['nullable', 'string', 'max:255']],
        'business_name' => ['label' => 'Business Name', 'rules' => ['nullable', 'string', 'max:255']],
    ];

    public const LOAN_FIELDS = [
        'approved_amount'        => 'Loan Amount',
        'term_months'            => 'Loan Term (Months)',
        'monthly_repayment_date' => 'Monthly Repayment Day',
    ];

    /**
     * Where the customer page's field keys are found in the snapshot they
     * were shown, so the recorded "current value" is the server's, not the
     * browser's.
     */
    protected const SNAPSHOT_PATHS = [
        'full_name'           => 'kyc.full_name',
        'name_with_initials'  => 'kyc.name_with_initials',
        'id_number'           => 'kyc.id_number',
        'date_of_birth'       => 'kyc.date_of_birth',
        'phone_primary'       => 'kyc.phone_primary',
        'phone_secondary'     => 'kyc.phone_secondary',
        'email'               => 'kyc.email',
        'whatsapp_number'     => 'kyc.whatsapp_number',
        'address_line_1'      => 'kyc.address.line_1',
        'address_line_2'      => 'kyc.address.line_2',
        'landmark'            => 'kyc.address.landmark',
        'city'                => 'kyc.address.city',
        'state'               => 'kyc.address.state',
        'postal_code'         => 'kyc.address.postal_code',
        'employment_status'   => 'kyc.employment.status',
        'occupation'          => 'kyc.employment.occupation',
        'employer_name'       => 'kyc.employment.employer_name',
        'employer_phone'      => 'kyc.employment.employer_phone',
        'employer_email'      => 'kyc.employment.employer_email',
        'monthly_income'      => 'kyc.employment.monthly_income',
        'business_name'       => 'kyc.business.name',
        'requested_amount'    => 'loan.requested_amount',
        'term_months'         => 'loan.term_months',
        'monthly_installment' => 'loan.monthly_installment',
        'repayment_day'       => 'loan.monthly_repayment_date',
    ];

    /**
     * The page's Stage 3 keys that map straight onto a loan field.
     */
    protected const LOAN_KEY_TARGETS = [
        'requested_amount' => 'loan.approved_amount',
        'term_months'      => 'loan.term_months',
        'repayment_day'    => 'loan.monthly_repayment_date',
    ];

    public function __construct(
        protected LoanCustomerConfirmationService $confirmationService,
        protected NotificationService $notificationService
    ) {
    }

    /**
     * The customer asks for a correction from their review link.
     */
    public function submit(string $token, array $data): CustomerDetailCorrectionRequest
    {
        $confirmation = LoanCustomerConfirmation::where('token_hash', hash('sha256', trim($token)))->first();

        if (!$confirmation) {
            throw new Exception('This review link is not valid.', 404);
        }

        // A customer may still report a mistake after confirming; a link that
        // was replaced, failed or ran out cannot be used.
        if (!$confirmation->isConfirmed()) {
            if (!$confirmation->isPending()) {
                throw new Exception('This review link is no longer valid. Please contact your branch for a new link.', 410);
            }
            if ($confirmation->isLinkExpired()) {
                throw new Exception('This review link has expired. Please contact your branch for a new link.', 410);
            }
        }

        $loanApplication = $confirmation->loanApplication;

        if (!$loanApplication || !$this->isCorrectable($loanApplication)) {
            throw new Exception('This loan application can no longer be corrected. Please contact your branch.');
        }

        $stage = $this->parseStage($data['stage'] ?? '');

        if (!$stage) {
            throw new Exception('Please choose the review stage the incorrect detail is in.');
        }

        $openCount = CustomerDetailCorrectionRequest::open()
            ->where('loan_application_id', $confirmation->loan_application_id)
            ->where('customer_id', $confirmation->customer_id)
            ->count();

        if ($openCount >= self::MAX_OPEN_PER_CUSTOMER) {
            throw new Exception('You already have ' . self::MAX_OPEN_PER_CUSTOMER . ' correction requests waiting for staff review. Please wait for them to be processed.');
        }

        $fieldName = trim($data['field_name']);
        $currentValue = $this->snapshotValue($confirmation->snapshot_data ?? [], $fieldName)
            ?? (isset($data['current_value']) ? trim((string) $data['current_value']) : null);

        $request = CustomerDetailCorrectionRequest::create([
            'loan_application_id'           => $confirmation->loan_application_id,
            'customer_id'                   => $confirmation->customer_id,
            'loan_customer_confirmation_id' => $confirmation->id,
            'review_stage'                  => $stage,
            'field_name'                    => $fieldName,
            'current_value'                 => $currentValue,
            'proposed_value'                => trim($data['proposed_value']),
            'reason'                        => trim($data['reason']),
            'status'                        => CustomerDetailCorrectionRequest::STATUS_PENDING,
        ]);

        $customer = $confirmation->customer;
        $this->notificationService->sendSystemToStaff(
            'customer_correction_requested',
            "Correction request #{$request->id} for loan application {$loanApplication->reference()}: customer {$customer?->customer_id} asks to change " . $this->fieldLabel($fieldName) . ' (' . $this->stageLabel($stage) . ').',
            ['loan_application_id' => $loanApplication->id],
            self::VIEW_PERMISSION
        );

        Log::info('Customer correction request submitted', [
            'correction_request_id' => $request->id,
            'loan_application_id'   => $request->loan_application_id,
            'customer_id'           => $request->customer_id,
        ]);

        return $request;
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return CustomerDetailCorrectionRequest::with([
                'customer:id,customer_id,full_name',
                'loanApplication:id,application_id,status',
                'loanApplication.application:id,application_no',
                'reviewer:id,name',
            ])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['loan_application_id'] ?? null, fn ($q, $v) => $q->where('loan_application_id', $v))
            ->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['review_stage'] ?? null, fn ($q, $v) => $q->where('review_stage', $v))
            ->latest('id')
            ->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    /**
     * A request with everything staff need to decide: the fields it can be
     * applied to, their live values, and which of them this user may change.
     */
    public function show(CustomerDetailCorrectionRequest $request, User $user): array
    {
        $request->loadMissing(['customer', 'loanApplication.application', 'reviewer:id,name']);

        return [
            'correction_request' => $request,
            'loan_reference'     => $request->loanApplication?->reference(),
            'loan_status'        => $request->loanApplication?->status?->value,
            'default_target'     => $this->defaultTarget($request),
            'apply_targets'      => $request->isOpen() && $request->loanApplication
                ? $this->applyTargets($request, $user)
                : [],
        ];
    }

    public function markInReview(CustomerDetailCorrectionRequest $request, User $staffUser): CustomerDetailCorrectionRequest
    {
        return DB::transaction(function () use ($request, $staffUser) {
            $request = CustomerDetailCorrectionRequest::lockForUpdate()->findOrFail($request->id);

            if ($request->status !== CustomerDetailCorrectionRequest::STATUS_PENDING) {
                throw new Exception("Only a pending request can be taken into review. This one is {$request->status}.");
            }

            $request->update(['status' => CustomerDetailCorrectionRequest::STATUS_IN_REVIEW]);

            Log::info('Customer correction request taken into review', [
                'correction_request_id' => $request->id,
                'by'                    => $staffUser->id,
            ]);

            return $request;
        });
    }

    public function reject(CustomerDetailCorrectionRequest $request, string $remarks, User $staffUser): CustomerDetailCorrectionRequest
    {
        $request = DB::transaction(function () use ($request, $remarks, $staffUser) {
            $request = CustomerDetailCorrectionRequest::lockForUpdate()->findOrFail($request->id);

            if (!$request->isOpen()) {
                throw new Exception("This request is already {$request->status}.");
            }

            $request->update([
                'status'         => CustomerDetailCorrectionRequest::STATUS_REJECTED,
                'review_remarks' => trim($remarks),
                'reviewed_by'    => $staffUser->id,
                'reviewed_at'    => now(),
            ]);

            return $request;
        });

        $this->notifyProcessed($request, 'rejected');

        return $request;
    }

    /**
     * Approve and apply: write the value to its target, keep the audit trail
     * on the request, and -- once nothing else is open on the loan -- send the
     * customers a fresh review link.
     *
     * @return array{correction_request: CustomerDetailCorrectionRequest, kyc_reset: bool, review_link: array}
     */
    public function approve(CustomerDetailCorrectionRequest $request, ?string $target, mixed $value, ?string $remarks, User $staffUser): array
    {
        $result = DB::transaction(function () use ($request, $target, $value, $remarks, $staffUser) {
            $request = CustomerDetailCorrectionRequest::lockForUpdate()->findOrFail($request->id);

            if (!$request->isOpen()) {
                throw new Exception("This request is already {$request->status}.");
            }

            $loanApplication = LoanApplication::lockForUpdate()->find($request->loan_application_id);

            if (!$loanApplication || !$this->isCorrectable($loanApplication)) {
                throw new Exception('This loan application can no longer be corrected.');
            }

            $target = $target ?: $this->defaultTarget($request);

            if (!$target) {
                throw new Exception('Choose the field this correction should be applied to.');
            }

            $resolved = $this->resolveTarget($request, $loanApplication, $target);

            foreach ($resolved['permissions'] as $permission) {
                if (!$staffUser->can($permission)) {
                    throw new Exception("You need the '{$permission}' permission to change this field.", 403);
                }
            }

            $value = $value === null || $value === '' ? $request->proposed_value : $value;
            $value = is_string($value) ? trim($value) : $value;

            $validator = Validator::make(['value' => $value], ['value' => $resolved['rules']], [], ['value' => $resolved['label']]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            /** @var Model $model */
            $model = $resolved['model'];
            $field = $resolved['field'];
            $oldValue = $this->readValue($model, $field);

            if ((string) $oldValue === (string) $value) {
                throw new Exception("{$resolved['label']} is already '{$value}'. Reject the request or choose a different value.");
            }

            $model->update([$field => $value]);
            $changes = [];

            if ($model instanceof LoanApplication) {
                $changes = $this->recomputeLoanTerms($model);
            }

            $kycReset = false;
            if ($model instanceof Customer && in_array($field, self::KYC_RESET_FIELDS, true) && $model->is_kyc_verified) {
                $model->update(['is_kyc_verified' => false, 'kyc_verified_at' => null]);
                $changes[] = ['field' => 'is_kyc_verified', 'old' => true, 'new' => false];
                $kycReset = true;
            }

            $request->update([
                'status'          => CustomerDetailCorrectionRequest::STATUS_COMPLETED,
                'applied_target'  => $target,
                'old_value'       => $oldValue === null ? null : (string) $oldValue,
                'applied_value'   => (string) $value,
                'applied_changes' => $changes ?: null,
                'review_remarks'  => $remarks ? trim($remarks) : null,
                'reviewed_by'     => $staffUser->id,
                'reviewed_at'     => now(),
            ]);

            Log::info('Customer correction applied', [
                'correction_request_id' => $request->id,
                'target'                => $target,
                'by'                    => $staffUser->id,
                'kyc_reset'             => $kycReset,
            ]);

            return ['request' => $request, 'kyc_reset' => $kycReset, 'loan' => $loanApplication];
        });

        $this->notifyProcessed($result['request'], 'applied');

        return [
            'correction_request' => $result['request']->fresh(),
            'kyc_reset'          => $result['kyc_reset'],
            'review_link'        => $this->resendLinkIfReady($result['loan']->fresh(), $staffUser),
        ];
    }

    /**
     * Send a fresh review link once no correction is open on the loan. Only
     * customers whose confirmation no longer matches the details get one.
     */
    protected function resendLinkIfReady(LoanApplication $loanApplication, User $staffUser): array
    {
        $open = CustomerDetailCorrectionRequest::open()->where('loan_application_id', $loanApplication->id)->count();

        if ($open > 0) {
            return [
                'sent'    => false,
                'message' => "{$open} more correction request(s) are open on this loan. The new review link will be sent once they are processed.",
            ];
        }

        try {
            $result = $this->confirmationService->sendLinks($loanApplication, $staffUser);

            return [
                'sent'      => collect($result['customers'])->contains('link_sent', true),
                'message'   => 'A new review link was sent to the customer(s) whose details changed.',
                'customers' => $result['customers'],
            ];
        } catch (\Throwable $e) {
            return [
                'sent'    => false,
                'message' => 'The new review link was not sent: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Every field the request can be applied to, with live values and whether
     * this user holds the permissions to change it.
     */
    public function applyTargets(CustomerDetailCorrectionRequest $request, User $user): array
    {
        $loanApplication = $request->loanApplication->loadMissing('loanApplicationGuarantors.guarantor');
        $targets = [];

        $customers = $this->confirmationService->requiredCustomers($loanApplication)
            ->sortBy(fn (Customer $c) => $c->id === $request->customer_id ? 0 : 1);

        foreach ($customers as $customer) {
            $isRequester = $customer->id === $request->customer_id;
            foreach (self::CUSTOMER_FIELDS as $field => $meta) {
                $targets[] = $this->targetRow(
                    "customer:{$customer->id}.{$field}",
                    $isRequester ? CustomerDetailCorrectionRequest::STAGE_1 : CustomerDetailCorrectionRequest::STAGE_2,
                    "{$customer->full_name} — {$meta['label']}",
                    $this->readValue($customer, $field),
                    ['Customer Update'],
                    $user
                );
            }
        }

        $requester = $customers->firstWhere('id', $request->customer_id);
        foreach ($requester?->bankDetails ?? [] as $bankDetail) {
            $targets[] = $this->targetRow(
                "bank_detail:{$bankDetail->id}.account_number",
                CustomerDetailCorrectionRequest::STAGE_1,
                "Bank Account Number ({$bankDetail->bank_name})",
                $bankDetail->account_number,
                ['Customer Bank Detail Update', self::LIVE_LOAN_OVERRIDE_PERMISSION],
                $user
            );
        }

        foreach ($loanApplication->loanApplicationGuarantors as $link) {
            $guarantor = $link->guarantor;
            if (!$guarantor) {
                continue;
            }
            foreach (self::GUARANTOR_FIELDS as $field => $meta) {
                $targets[] = $this->targetRow(
                    "guarantor:{$guarantor->id}.{$field}",
                    CustomerDetailCorrectionRequest::STAGE_2,
                    "Guarantor {$guarantor->full_name} — {$meta['label']}",
                    $this->readValue($guarantor, $field),
                    ['Guarantor Update'],
                    $user
                );
            }
            $targets[] = $this->targetRow(
                "guarantor_type:{$link->id}",
                CustomerDetailCorrectionRequest::STAGE_2,
                "Guarantor {$guarantor->full_name} — Relationship / Role",
                $link->guarantor_type,
                ['Loan Application Guarantor Update'],
                $user
            );
        }

        foreach (self::LOAN_FIELDS as $field => $label) {
            $targets[] = $this->targetRow(
                "loan.{$field}",
                CustomerDetailCorrectionRequest::STAGE_3,
                $label,
                $field === 'approved_amount'
                    ? ($loanApplication->approved_amount ?? $loanApplication->requested_amount)
                    : $loanApplication->{$field},
                ['Loan Application Update'],
                $user
            );
        }

        return $targets;
    }

    /**
     * Turn a target key into the record, field, rules and permissions it
     * stands for, checking the record really belongs to this loan.
     */
    protected function resolveTarget(CustomerDetailCorrectionRequest $request, LoanApplication $loanApplication, string $target): array
    {
        if (preg_match('/^customer:(\d+)\.(\w+)$/', $target, $m) && isset(self::CUSTOMER_FIELDS[$m[2]])) {
            $customer = $this->confirmationService->requiredCustomers($loanApplication)->firstWhere('id', (int) $m[1]);
            if (!$customer) {
                throw new Exception('That customer is not a borrower on this loan application.');
            }
            $rules = self::CUSTOMER_FIELDS[$m[2]]['rules'];
            if ($m[2] === 'id_number') {
                $rules[] = Rule::unique('customers', 'id_number')->ignore($customer->id)->whereNull('deleted_at');
            }

            return [
                'model'       => Customer::lockForUpdate()->find($customer->id),
                'field'       => $m[2],
                'rules'       => $rules,
                'label'       => self::CUSTOMER_FIELDS[$m[2]]['label'],
                'permissions' => ['Customer Update'],
            ];
        }

        if (preg_match('/^guarantor:(\d+)\.(\w+)$/', $target, $m) && isset(self::GUARANTOR_FIELDS[$m[2]])) {
            $linked = LoanApplicationGuarantor::where('loan_application_id', $loanApplication->id)
                ->where('guarantor_id', (int) $m[1])
                ->exists();
            if (!$linked) {
                throw new Exception('That guarantor is not on this loan application.');
            }

            return [
                'model'       => Guarantor::lockForUpdate()->findOrFail((int) $m[1]),
                'field'       => $m[2],
                'rules'       => self::GUARANTOR_FIELDS[$m[2]]['rules'],
                'label'       => 'Guarantor ' . self::GUARANTOR_FIELDS[$m[2]]['label'],
                'permissions' => ['Guarantor Update'],
            ];
        }

        if (preg_match('/^guarantor_type:(\d+)$/', $target, $m)) {
            $link = LoanApplicationGuarantor::where('loan_application_id', $loanApplication->id)
                ->lockForUpdate()
                ->find((int) $m[1]);
            if (!$link) {
                throw new Exception('That guarantor is not on this loan application.');
            }

            return [
                'model'       => $link,
                'field'       => 'guarantor_type',
                'rules'       => ['required', 'string', 'max:255'],
                'label'       => 'Guarantor Relationship',
                'permissions' => ['Loan Application Guarantor Update'],
            ];
        }

        if (preg_match('/^bank_detail:(\d+)\.account_number$/', $target, $m)) {
            $bankDetail = CustomerBankDetail::where('customer_id', $request->customer_id)
                ->lockForUpdate()
                ->find((int) $m[1]);
            if (!$bankDetail) {
                throw new Exception('That bank account does not belong to this customer.');
            }

            return [
                'model'       => $bankDetail,
                'field'       => 'account_number',
                'rules'       => ['required', 'string', 'max:255'],
                'label'       => 'Bank Account Number',
                'permissions' => ['Customer Bank Detail Update', self::LIVE_LOAN_OVERRIDE_PERMISSION],
            ];
        }

        if (preg_match('/^loan\.(\w+)$/', $target, $m) && isset(self::LOAN_FIELDS[$m[1]])) {
            $rules = match ($m[1]) {
                // Correcting the amount is not a way round approval: it can
                // go down, or back up, but never past what was applied for.
                'approved_amount'        => ['required', 'numeric', 'min:0.01', 'max:' . (float) $loanApplication->requested_amount],
                'term_months'            => ['required', 'integer', 'min:1', 'max:600'],
                'monthly_repayment_date' => ['required', 'integer', 'min:1', 'max:31'],
            };

            return [
                'model'       => $loanApplication,
                'field'       => $m[1],
                'rules'       => $rules,
                'label'       => self::LOAN_FIELDS[$m[1]],
                'permissions' => ['Loan Application Update'],
            ];
        }

        throw new Exception("'{$target}' is not a field a correction can be applied to.");
    }

    /**
     * The same arithmetic as LoanApplicationController::approve(), rerun after
     * the amount or term changes. The processing fee stays as approved.
     */
    protected function recomputeLoanTerms(LoanApplication $loanApplication): array
    {
        $amount = (float) ($loanApplication->approved_amount ?? $loanApplication->requested_amount);
        $interest = round($amount * $loanApplication->interest_rate / 100, 2);
        $totalRepayment = round($amount + $interest, 2);

        $new = [
            'monthly_installment'     => round($totalRepayment / max(1, (int) $loanApplication->term_months), 2),
            'net_disbursement_amount' => max(0, round($amount - (float) $loanApplication->processing_fee, 2)),
        ];

        $changes = [];
        foreach ($new as $field => $value) {
            if ((float) $loanApplication->{$field} !== (float) $value) {
                $changes[] = ['field' => $field, 'old' => $loanApplication->{$field}, 'new' => $value];
            }
        }

        if ($changes) {
            $loanApplication->update($new);
        }

        return $changes;
    }

    protected function defaultTarget(CustomerDetailCorrectionRequest $request): ?string
    {
        $field = $request->field_name;

        if ($request->review_stage === CustomerDetailCorrectionRequest::STAGE_1 && isset(self::CUSTOMER_FIELDS[$field])) {
            return "customer:{$request->customer_id}.{$field}";
        }

        if ($request->review_stage === CustomerDetailCorrectionRequest::STAGE_3 && isset(self::LOAN_KEY_TARGETS[$field])) {
            return self::LOAN_KEY_TARGETS[$field];
        }

        return null;
    }

    protected function targetRow(string $key, string $stage, string $label, mixed $current, array $permissions, User $user): array
    {
        return [
            'target'        => $key,
            'stage'         => $stage,
            'label'         => $label,
            'current_value' => $current,
            'can_apply'     => $user->can(self::REVIEW_PERMISSION)
                && collect($permissions)->every(fn ($p) => $user->can($p)),
        ];
    }

    protected function readValue(Model $model, string $field): mixed
    {
        $value = $model->{$field};

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
    }

    protected function snapshotValue(array $snapshot, string $field): ?string
    {
        $path = self::SNAPSHOT_PATHS[$field] ?? null;
        $value = $path ? data_get($snapshot, $path) : null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * Accepts the page's label ("Stage 1 — Personal Details") or the key.
     */
    protected function parseStage(string $stage): ?string
    {
        return preg_match('/stage\s*_?\s*([123])/i', $stage, $m) ? 'stage_' . $m[1] : null;
    }

    protected function stageLabel(string $stage): string
    {
        return match ($stage) {
            CustomerDetailCorrectionRequest::STAGE_1 => 'Stage 1 — Personal Details',
            CustomerDetailCorrectionRequest::STAGE_2 => 'Stage 2 — Participant Details',
            default                                  => 'Stage 3 — Loan Application Details',
        };
    }

    protected function fieldLabel(string $field): string
    {
        return self::CUSTOMER_FIELDS[$field]['label']
            ?? self::LOAN_FIELDS[self::LOAN_KEY_TARGETS[$field] ?? ''] ?? null
            ?? ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Corrections belong before disbursement: the same window review links
     * are sent in.
     */
    protected function isCorrectable(LoanApplication $loanApplication): bool
    {
        return $loanApplication->status === LoanApplicationStatus::Accepted
            || $loanApplication->status->canTransitionTo(LoanApplicationStatus::Disbursed);
    }

    protected function notifyProcessed(CustomerDetailCorrectionRequest $request, string $outcome): void
    {
        $loanApplication = $request->loanApplication;

        $this->notificationService->sendSystemToStaff(
            'customer_correction_processed',
            "Correction request #{$request->id} for loan application {$loanApplication?->reference()} was {$outcome}: " . $this->fieldLabel($request->field_name) . '.',
            ['loan_application_id' => $request->loan_application_id],
            self::VIEW_PERMISSION
        );
    }
}
