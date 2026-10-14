<?php

namespace App\Services;

use App\Enums\LoanApplicationStatus;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Models\Customer;
use App\Models\CustomerDetailCorrectionRequest;
use App\Models\CustomerVerification;
use App\Models\LoanApplication;
use App\Models\LoanCustomerConfirmation;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The customer's own confirmation of a loan application (OTP 2).
 *
 * Staff send a review link by SMS; the customer opens it, sees the KYC and
 * loan details they are agreeing to, and either asks for a correction or
 * confirms. Confirming takes OTP 2, sent to the customer's registered phone:
 * the customer enters it on the link, or -- when they cannot -- staff send it
 * from the preview, record why, and enter the code the customer reads out.
 * Each customer on the loan (every co-borrower on a Joint Loan) confirms for
 * themselves. Confirmation does not move the loan's status -- it is a
 * precondition for disbursement, checked by assertConfirmedForDisbursement().
 * Errors meant for the public page carry an HTTP code: 404 for an unknown
 * link, 410 for one that has expired or been replaced. Anything else is 422.
 */
class LoanCustomerConfirmationService
{
    public const LINK_TTL_HOURS = 72;
    public const OTP_TTL_MINUTES = 10;
    public const MAX_OTP_ATTEMPTS = 5;
    public const MAX_OTP_REQUESTS = 5;
    public const MIN_STAFF_ASSIST_REASON = 10;

    public const DECLARATION_TEXT = 'I confirm that the personal (KYC) details and the loan application details shown above are true and correct, and that I have applied for this loan of my own free will. I understand that this confirmation does not mean the loan has been approved.';

    public function __construct(
        protected CustomerVerificationService $customerVerificationService,
        protected SmsService $smsService
    ) {
    }

    /**
     * Customers who must confirm this loan: every co-borrower on a Joint Loan,
     * otherwise the primary customer.
     */
    public function requiredCustomers(LoanApplication $loanApplication): \Illuminate\Support\Collection
    {
        return $loanApplication->notifiableCustomers();
    }

    /**
     * What the customer is shown, and what their confirmation is bound to.
     * Deliberately free of timestamps so that the same data always hashes the
     * same: the hash is how a later change to the loan or KYC is detected.
     */
    public function buildSnapshot(LoanApplication $loanApplication, Customer $customer): array
    {
        $loanApplication->loadMissing([
            'application',
            'loanProduct.loanType',
            'loanApplicationGuarantors.guarantor',
            'securities',
        ]);

        $kyc = $this->customerVerificationService->buildCustomerSnapshot($customer);
        unset($kyc['captured_at']);

        $product = $loanApplication->loanProduct;

        return [
            'kyc'  => $kyc['customer'],
            'loan' => [
                'id'                      => $loanApplication->id,
                'reference'               => $loanApplication->reference(),
                'product'                 => $product?->name,
                'loan_type'               => $product?->loanType?->title,
                'requested_amount'        => $loanApplication->requested_amount,
                'interest_rate'           => $loanApplication->interest_rate,
                'interest_type'           => $loanApplication->interest_type,
                'term_months'             => $loanApplication->term_months,
                'monthly_installment'     => $loanApplication->monthly_installment,
                'processing_fee'          => $loanApplication->processing_fee,
                'net_disbursement_amount' => $loanApplication->net_disbursement_amount,
                'monthly_repayment_date'  => $loanApplication->monthly_repayment_date,
                'co_borrowers'            => $loanApplication->isJointLoan()
                    ? $this->requiredCustomers($loanApplication)
                        ->sortBy('id')
                        ->map(fn (Customer $c) => ['customer_id' => $c->customer_id, 'full_name' => $c->full_name])
                        ->values()
                        ->toArray()
                    : [],
                'guarantors' => $loanApplication->loanApplicationGuarantors
                    ->sortBy('id')
                    ->map(fn ($row) => [
                        'full_name' => $row->guarantor?->full_name,
                        'type'      => $row->guarantor_type,
                    ])
                    ->values()
                    ->toArray(),
                'securities' => $loanApplication->securities
                    ->sortBy('id')
                    ->map(fn ($security) => [
                        'type'                => $security->security_type instanceof \BackedEnum
                            ? $security->security_type->value
                            : $security->security_type,
                        'policy_number'       => $security->policy_number,
                        'owner_deed_number'   => $security->owner_deed_number,
                        'registration_number' => $security->registration_number,
                    ])
                    ->values()
                    ->toArray(),
            ],
        ];
    }

    public function hashSnapshot(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Step 4: send a review link to every customer who still has to confirm.
     */
    public function sendLinks(LoanApplication $loanApplication, ?User $staffUser = null): array
    {
        if ($loanApplication->status !== LoanApplicationStatus::Accepted && !$loanApplication->status->canTransitionTo(LoanApplicationStatus::Disbursed)) {
            throw new Exception("Disbursement review links can only be issued once the loan offer has been accepted. Current status: '{$loanApplication->status->value}'.");
        }

        $customers = $this->requiredCustomers($loanApplication);

        if ($customers->isEmpty()) {
            throw new Exception('This loan application has no customer to confirm it.');
        }

        foreach ($customers as $customer) {
            if (!$customer->is_kyc_verified) {
                throw new Exception("Customer {$customer->full_name} ({$customer->customer_id}) KYC details must be verified with OTP before a review link can be sent.");
            }
            if (empty($customer->phone_primary)) {
                throw new Exception("Customer {$customer->full_name} ({$customer->customer_id}) does not have a primary mobile number configured.");
            }
            if ($this->hasOpenCorrections($loanApplication->id, $customer->id)) {
                throw new Exception("Customer {$customer->full_name} ({$customer->customer_id}) has a correction request awaiting staff review. Process it before sending a new link.");
            }
        }

        return DB::transaction(function () use ($loanApplication, $customers, $staffUser) {
            $results = [];

            foreach ($customers as $customer) {
                $snapshot = $this->buildSnapshot($loanApplication, $customer);
                $snapshotHash = $this->hashSnapshot($snapshot);

                $alreadyConfirmed = LoanCustomerConfirmation::where('loan_application_id', $loanApplication->id)
                    ->where('customer_id', $customer->id)
                    ->where('status', LoanCustomerConfirmation::STATUS_CONFIRMED)
                    ->where('snapshot_hash', $snapshotHash)
                    ->latest('id')
                    ->first();

                if ($alreadyConfirmed) {
                    $results[] = [
                        'customer_id'  => $customer->id,
                        'full_name'    => $customer->full_name,
                        'status'       => LoanCustomerConfirmation::STATUS_CONFIRMED,
                        'link_sent'    => false,
                        'confirmed_at' => $alreadyConfirmed->confirmed_at?->toIso8601String(),
                    ];
                    continue;
                }

                LoanCustomerConfirmation::where('loan_application_id', $loanApplication->id)
                    ->where('customer_id', $customer->id)
                    ->whereIn('status', [LoanCustomerConfirmation::STATUS_PENDING, LoanCustomerConfirmation::STATUS_CONFIRMED])
                    ->update(['status' => LoanCustomerConfirmation::STATUS_SUPERSEDED]);

                $token = bin2hex(random_bytes(16));
                $expiresAt = now()->addHours(self::LINK_TTL_HOURS);

                $confirmation = LoanCustomerConfirmation::create([
                    'loan_application_id' => $loanApplication->id,
                    'customer_id'         => $customer->id,
                    'created_by'          => $staffUser?->id,
                    'phone_number'        => $customer->phone_primary,
                    'token_hash'          => hash('sha256', $token),
                    'link_expires_at'     => $expiresAt,
                    'snapshot_data'       => $snapshot,
                    'snapshot_hash'       => $snapshotHash,
                    'status'              => LoanCustomerConfirmation::STATUS_PENDING,
                ]);

                $link = $this->reviewLink($token);
                $smsMessage = "CDP Capital: Please review and confirm your loan application {$loanApplication->reference()}: {$link} Link valid for " . self::LINK_TTL_HOURS . " hours.";
                $smsSent = false;
                try {
                    $smsSent = $this->smsService->sendSms($customer->phone_primary, $smsMessage);
                } catch (\Throwable $e) {
                    Log::error('Failed to send loan review link SMS: ' . $e->getMessage(), [
                        'loan_application_id' => $loanApplication->id,
                        'customer_id'         => $customer->id,
                    ]);
                }

                $confirmation->update(['sms_sent' => $smsSent]);

                $results[] = [
                    'customer_id'           => $customer->id,
                    'full_name'             => $customer->full_name,
                    'status'                => LoanCustomerConfirmation::STATUS_PENDING,
                    'link_sent'             => true,
                    'sms_sent'              => $smsSent,
                    'customer_phone_masked' => Str::mask($customer->phone_primary, '*', 3, -2),
                    'link_expires_at'       => $expiresAt->toIso8601String(),
                    'dev_link'              => config('app.debug') ? $link : null,
                ];
            }

            Log::info('Loan customer confirmation links sent', [
                'loan_application_id' => $loanApplication->id,
                'sent_by'             => $staffUser?->id,
                'customers'           => array_column($results, 'customer_id'),
            ]);

            return [
                'loan_application_id' => $loanApplication->id,
                'customers'           => $results,
            ];
        });
    }

    /**
     * Staff view: where each required customer stands.
     */
    public function status(LoanApplication $loanApplication): array
    {
        $customers = [];
        $allConfirmed = true;

        foreach ($this->requiredCustomers($loanApplication) as $customer) {
            $latest = LoanCustomerConfirmation::where('loan_application_id', $loanApplication->id)
                ->where('customer_id', $customer->id)
                ->latest('id')
                ->first();

            $state = $latest?->status ?? 'not_sent';
            $isCurrent = $latest && $latest->isConfirmed()
                && hash_equals($latest->snapshot_hash, $this->hashSnapshot($this->buildSnapshot($loanApplication, $customer)));

            if ($latest && $latest->isConfirmed() && !$isCurrent) {
                $state = 'stale';
            } elseif ($latest && $latest->isPending() && $latest->isLinkExpired()) {
                $state = LoanCustomerConfirmation::STATUS_EXPIRED;
            }

            $allConfirmed = $allConfirmed && $isCurrent;

            $customers[] = [
                'customer_id'           => $customer->id,
                'customer_code'         => $customer->customer_id,
                'full_name'             => $customer->full_name,
                'status'                => $state,
                'customer_phone_masked' => $latest ? Str::mask($latest->phone_number, '*', 3, -2) : null,
                'sms_sent'              => $latest?->sms_sent,
                'link_sent_at'          => $latest?->created_at?->toIso8601String(),
                'link_expires_at'       => $latest?->link_expires_at?->toIso8601String(),
                'viewed_at'             => $latest?->viewed_at?->toIso8601String(),
                'confirmed_at'          => $latest?->confirmed_at?->toIso8601String(),
                'confirmation_method'   => $latest?->confirmation_method,
                'confirmed_by'          => $latest?->confirmer?->only(['id', 'name']),
                'staff_assist_reason'   => $latest?->staff_assist_reason,
                'open_correction_requests' => CustomerDetailCorrectionRequest::open()
                    ->where('loan_application_id', $loanApplication->id)
                    ->where('customer_id', $customer->id)
                    ->count(),
            ];
        }

        return [
            'loan_application_id' => $loanApplication->id,
            'all_confirmed'       => $allConfirmed && !empty($customers),
            'customers'           => $customers,
        ];
    }

    /**
     * Step 5: the public review page.
     */
    public function showByToken(string $token): array
    {
        $confirmation = $this->findByToken($token);

        if (!$confirmation->isConfirmed() && ($error = $this->usabilityError($confirmation))) {
            throw $error;
        }

        if (!$confirmation->viewed_at) {
            $confirmation->update(['viewed_at' => now()]);
        }

        return [
            'status'                => $confirmation->status,
            'customer_phone_masked' => Str::mask($confirmation->phone_number, '*', 3, -2),
            'link_expires_at'       => $confirmation->link_expires_at->toIso8601String(),
            'confirmed_at'          => $confirmation->confirmed_at?->toIso8601String(),
            'declaration_text'      => $confirmation->declaration_text ?? self::DECLARATION_TEXT,
            'details'               => $confirmation->snapshot_data,
            // The customer's own requests, so a page refresh does not lose them.
            'correction_requests'   => CustomerDetailCorrectionRequest::where('loan_application_id', $confirmation->loan_application_id)
                ->where('customer_id', $confirmation->customer_id)
                ->latest('id')
                ->get()
                ->map(fn (CustomerDetailCorrectionRequest $r) => [
                    'id'             => $r->id,
                    'review_stage'   => $r->review_stage,
                    'field_name'     => $r->field_name,
                    'proposed_value' => $r->proposed_value,
                    'status'         => $r->status,
                    'submitted_at'   => $r->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Step 6a: the customer asks for OTP 2 on their own link.
     */
    public function requestOtp(string $token): array
    {
        return $this->lockedOtpAction(
            fn () => LoanCustomerConfirmation::where('token_hash', hash('sha256', trim($token))),
            fn (LoanCustomerConfirmation $confirmation) => $this->issueOtp($confirmation)
        );
    }

    /**
     * Steps 6b-7: the customer enters OTP 2 on their own link.
     */
    public function confirm(string $token, string $otp, bool $declarationAccepted, ?string $ip = null, ?string $userAgent = null): array
    {
        if (!$declarationAccepted) {
            throw new Exception('You must accept the declaration to confirm.');
        }

        return $this->lockedOtpAction(
            fn () => LoanCustomerConfirmation::where('token_hash', hash('sha256', trim($token))),
            fn (LoanCustomerConfirmation $confirmation) => $this->verifyAndConfirm(
                $confirmation, $otp, $ip, $userAgent, LoanCustomerConfirmation::METHOD_CUSTOMER_LINK
            )
        );
    }

    /**
     * Staff-assisted: send OTP 2 to the customer's registered phone from the
     * staff preview, for a customer who cannot complete it on their link.
     * Only for a link already sent and still open; the reason is kept on the
     * confirmation.
     */
    public function staffSendOtp(LoanApplication $loanApplication, int $customerId, string $reason, User $staffUser): array
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < self::MIN_STAFF_ASSIST_REASON) {
            throw new Exception('Please give the reason the customer\'s OTP has to be entered by staff (at least ' . self::MIN_STAFF_ASSIST_REASON . ' characters).');
        }

        $this->assertRequiredCustomer($loanApplication, $customerId);

        $result = $this->lockedOtpAction(
            fn () => $this->latestConfirmationQuery($loanApplication, $customerId),
            function (LoanCustomerConfirmation $confirmation) use ($reason) {
                $confirmation->update(['staff_assist_reason' => $reason]);

                return $this->issueOtp($confirmation);
            },
            'No review link has been sent to this customer yet. Send the review link first.'
        );

        Log::info('Loan confirmation OTP sent by staff', [
            'loan_application_id' => $loanApplication->id,
            'customer_id'         => $customerId,
            'sent_by'             => $staffUser->id,
        ]);

        return $result + ['staff_assist_reason' => $reason];
    }

    /**
     * Staff-assisted: enter the OTP the customer read out, with the customer's
     * acceptance of the declaration. Only after staffSendOtp(), so the reason
     * staff had to do it is always on record.
     */
    public function staffVerifyOtp(LoanApplication $loanApplication, int $customerId, string $otp, bool $declarationAccepted, User $staffUser, ?string $ip = null, ?string $userAgent = null): array
    {
        if (!$declarationAccepted) {
            throw new Exception('The customer must accept the declaration to confirm.');
        }

        $this->assertRequiredCustomer($loanApplication, $customerId);

        return $this->lockedOtpAction(
            fn () => $this->latestConfirmationQuery($loanApplication, $customerId),
            function (LoanCustomerConfirmation $confirmation) use ($otp, $ip, $userAgent, $staffUser) {
                if (empty($confirmation->staff_assist_reason)) {
                    throw new Exception('Send the OTP from the staff preview, with the reason, before entering it.');
                }

                return $this->verifyAndConfirm(
                    $confirmation, $otp, $ip, $userAgent, LoanCustomerConfirmation::METHOD_STAFF_ASSISTED, $staffUser
                );
            },
            'No review link has been sent to this customer yet. Send the review link first.'
        );
    }

    /**
     * Staff preview: what each customer sees on their link, where they stand,
     * their correction requests, and what the given staff member may do now.
     */
    public function preview(LoanApplication $loanApplication, User $user): array
    {
        $customers = $this->requiredCustomers($loanApplication)->keyBy('id');
        $status = $this->status($loanApplication);

        $canSendOtp = $user->can('Loan Customer Confirmation Send OTP');
        $canVerifyOtp = $user->can('Loan Customer Confirmation Verify OTP');
        $canViewCorrections = $user->can('Loan Customer Correction Index');
        $canReviewCorrections = $user->can('Loan Customer Correction Review');

        $rows = [];

        foreach ($status['customers'] as $row) {
            $customer = $customers->get($row['customer_id']);
            $latest = $this->latestConfirmationQuery($loanApplication, $row['customer_id'])->first();

            $hasOpenCorrections = $row['open_correction_requests'] > 0;
            $linkOpen = $latest && $latest->isPending() && !$latest->isLinkExpired();
            $staffOtpLive = $linkOpen
                && $latest->staff_assist_reason
                && $latest->otp_hash
                && $latest->otp_expires_at?->isFuture();

            $rows[] = $row + [
                'otp_expires_at' => $staffOtpLive ? $latest->otp_expires_at->toIso8601String() : null,
                'details'        => $this->buildSnapshot($loanApplication, $customer),
                'correction_requests' => $canViewCorrections
                    ? CustomerDetailCorrectionRequest::where('loan_application_id', $loanApplication->id)
                        ->where('customer_id', $customer->id)
                        ->latest('id')
                        ->get()
                        ->map(fn (CustomerDetailCorrectionRequest $r) => [
                            'id'             => $r->id,
                            'review_stage'   => $r->review_stage,
                            'field_name'     => $r->field_name,
                            'current_value'  => $r->current_value,
                            'proposed_value' => $r->proposed_value,
                            'reason'         => $r->reason,
                            'status'         => $r->status,
                            'review_remarks' => $r->review_remarks,
                            'reviewed_at'    => $r->reviewed_at?->toIso8601String(),
                            'submitted_at'   => $r->created_at?->toIso8601String(),
                        ])
                        ->values()
                        ->all()
                    : null,
                'abilities' => [
                    'can_send_otp'           => $canSendOtp && $linkOpen && !$hasOpenCorrections,
                    'can_verify_otp'         => $canVerifyOtp && $staffOtpLive && !$hasOpenCorrections,
                    'can_view_corrections'   => $canViewCorrections,
                    'can_review_corrections' => $canReviewCorrections,
                ],
            ];
        }

        return [
            'loan_application_id'            => $loanApplication->id,
            'loan_status'                    => $loanApplication->status?->value,
            'all_confirmed'                  => $status['all_confirmed'],
            'declaration_text'               => self::DECLARATION_TEXT,
            'staff_assist_reason_min_length' => self::MIN_STAFF_ASSIST_REASON,
            'customers'                      => $rows,
        ];
    }

    /**
     * Disbursement gate: funds can only move once every required customer has
     * confirmed the approved terms and completed the final confirmation OTP (OTP 2).
     */
    public function assertConfirmedForDisbursement(LoanApplication $loanApplication): void
    {
        $status = $this->status($loanApplication);

        // A customer who confirmed and then reported a mistake is still waiting on staff.
        $openCorrections = array_sum(array_column($status['customers'], 'open_correction_requests'));
        if ($openCorrections > 0) {
            throw new InvalidLoanApplicationTransitionException(
                "{$openCorrections} customer correction request(s) must be processed before this loan application can be disbursed."
            );
        }

        if ($status['all_confirmed']) {
            return;
        }

        $missing = collect($status['customers'])
            ->reject(fn ($c) => $c['status'] === LoanCustomerConfirmation::STATUS_CONFIRMED)
            ->map(fn ($c) => "{$c['full_name']} ({$c['status']})")
            ->implode(', ');

        throw new InvalidLoanApplicationTransitionException(
            "The customer must confirm the loan details with OTP before this loan application can be disbursed. Awaiting: {$missing}."
        );
    }

    /**
     * @deprecated Kept for backward compatibility. Use assertConfirmedForDisbursement instead.
     */
    public function assertConfirmedForReview(LoanApplication $loanApplication): void
    {
        $this->assertConfirmedForDisbursement($loanApplication);
    }

    protected function findByToken(string $token): LoanCustomerConfirmation
    {
        $confirmation = LoanCustomerConfirmation::where('token_hash', hash('sha256', trim($token)))->first();

        if (!$confirmation) {
            throw new Exception('This review link is not valid.', 404);
        }

        return $confirmation;
    }

    /**
     * A link can be acted on only while it is pending and unexpired. Returns
     * the error rather than throwing it, so callers inside a transaction can
     * let the expiry write commit first.
     */
    protected function usabilityError(LoanCustomerConfirmation $confirmation): ?Exception
    {
        if ($confirmation->isConfirmed()) {
            return new Exception('This loan application has already been confirmed.');
        }

        if (!$confirmation->isPending()) {
            return new Exception('This review link is no longer valid. Please contact your branch for a new link.', 410);
        }

        if ($confirmation->isLinkExpired()) {
            $confirmation->update(['status' => LoanCustomerConfirmation::STATUS_EXPIRED]);
            return new Exception('This review link has expired. Please contact your branch for a new link.', 410);
        }

        return null;
    }

    /**
     * Lock the confirmation the query finds and run an OTP step on it.
     * Failures that write (attempt count, failed/superseded/expired status)
     * are returned by the step rather than thrown, so the transaction commits
     * them; they are thrown here, after commit.
     */
    protected function lockedOtpAction(callable $query, callable $action, string $notFoundMessage = 'This review link is not valid.'): array
    {
        $result = DB::transaction(function () use ($query, $action, $notFoundMessage) {
            $confirmation = $query()->lockForUpdate()->first();

            if (!$confirmation) {
                throw new Exception($notFoundMessage, 404);
            }

            if ($error = $this->usabilityError($confirmation)) {
                return $error;
            }

            if ($this->hasOpenCorrections($confirmation->loan_application_id, $confirmation->customer_id)) {
                throw new Exception('A correction request from this customer is with our staff. A new review link will be sent once it is processed.');
            }

            return $action($confirmation);
        });

        if ($result instanceof Exception) {
            throw $result;
        }

        return $result;
    }

    /**
     * Generate OTP 2 and send it to the phone the link was sent to.
     */
    protected function issueOtp(LoanCustomerConfirmation $confirmation): array
    {
        if ($confirmation->otp_requests >= self::MAX_OTP_REQUESTS) {
            throw new Exception('Too many OTP requests for this link. Please contact your branch for a new link.');
        }

        $otp = sprintf('%06d', random_int(100000, 999999));
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        $confirmation->update([
            'otp_hash'       => hash('sha256', $otp),
            'otp_expires_at' => $expiresAt,
            'otp_attempts'   => 0,
            'otp_requests'   => $confirmation->otp_requests + 1,
        ]);

        $reference = $confirmation->loanApplication?->reference();
        $smsMessage = "CDP Capital: Your OTP to confirm loan application {$reference} is {$otp}. Valid for " . self::OTP_TTL_MINUTES . " minutes. Share it only with CDP Capital staff assisting you in person.";
        $smsSent = false;
        try {
            $smsSent = $this->smsService->sendSms($confirmation->phone_number, $smsMessage);
        } catch (\Throwable $e) {
            Log::error('Failed to send loan confirmation OTP SMS: ' . $e->getMessage(), [
                'confirmation_id' => $confirmation->id,
            ]);
        }

        return [
            'customer_phone_masked' => Str::mask($confirmation->phone_number, '*', 3, -2),
            'otp_expires_at'        => $expiresAt->toIso8601String(),
            'otp_requests_left'     => self::MAX_OTP_REQUESTS - $confirmation->otp_requests,
            'sms_sent'              => $smsSent,
            'dev_otp'               => config('app.debug') ? $otp : null,
        ];
    }

    /**
     * Check OTP 2, make sure the details are still the ones the customer was
     * shown, and record the confirmation with its audit evidence.
     */
    protected function verifyAndConfirm(LoanCustomerConfirmation $confirmation, string $otp, ?string $ip, ?string $userAgent, string $method, ?User $staffUser = null): array|Exception
    {
        if (!$confirmation->otp_hash || !$confirmation->otp_expires_at) {
            throw new Exception('Please request an OTP first.');
        }

        if ($confirmation->otp_expires_at->isPast()) {
            throw new Exception('The OTP has expired. Please request a new OTP.');
        }

        if ($confirmation->otp_attempts >= self::MAX_OTP_ATTEMPTS) {
            $confirmation->update(['status' => LoanCustomerConfirmation::STATUS_FAILED]);
            return new Exception('Maximum OTP attempts exceeded. Please contact your branch for a new link.', 410);
        }

        if (!hash_equals($confirmation->otp_hash, hash('sha256', trim($otp)))) {
            $confirmation->increment('otp_attempts');
            $remaining = self::MAX_OTP_ATTEMPTS - $confirmation->otp_attempts;

            if ($remaining <= 0) {
                $confirmation->update(['status' => LoanCustomerConfirmation::STATUS_FAILED]);
                return new Exception('Maximum OTP attempts exceeded. Please contact your branch for a new link.', 410);
            }

            return new Exception("Invalid OTP. {$remaining} attempts remaining.");
        }

        // The details must still be what the customer was shown.
        $loanApplication = $confirmation->loanApplication;
        $customer = $confirmation->customer;
        $currentHash = $loanApplication && $customer
            ? $this->hashSnapshot($this->buildSnapshot($loanApplication, $customer))
            : null;

        if (!$currentHash || !hash_equals($confirmation->snapshot_hash, $currentHash)) {
            $confirmation->update(['status' => LoanCustomerConfirmation::STATUS_SUPERSEDED]);
            return new Exception('The loan or personal details have changed since this link was sent. Please contact your branch for a new link.', 410);
        }

        $otpHash = $confirmation->otp_hash;

        $confirmation->update([
            'status'               => LoanCustomerConfirmation::STATUS_CONFIRMED,
            'otp_hash'             => null,
            'declaration_text'     => self::DECLARATION_TEXT,
            'confirmed_at'         => now(),
            'confirmed_ip'         => $ip,
            'confirmed_user_agent' => $userAgent ? Str::limit($userAgent, 250, '') : null,
            'confirmation_method'  => $method,
            'confirmed_by'         => $staffUser?->id,
            // A reason only belongs to a confirmation staff completed.
            'staff_assist_reason'  => $method === LoanCustomerConfirmation::METHOD_STAFF_ASSISTED
                ? $confirmation->staff_assist_reason
                : null,
        ]);

        $this->refreshKycBaseline($customer, $confirmation, $otpHash, $staffUser);

        Log::info('Loan application confirmed by customer', [
            'confirmation_id'     => $confirmation->id,
            'loan_application_id' => $confirmation->loan_application_id,
            'customer_id'         => $confirmation->customer_id,
            'method'              => $method,
            'confirmed_by'        => $staffUser?->id,
        ]);

        return [
            'status'              => LoanCustomerConfirmation::STATUS_CONFIRMED,
            'reference'           => $loanApplication->reference(),
            'confirmed_at'        => $confirmation->confirmed_at->toIso8601String(),
            'confirmation_method' => $method,
        ];
    }

    /**
     * OTP 2 confirms the customer's full KYC details too. When those differ
     * from the last verified KYC snapshot -- an ordinary correction applied
     * after the KYC OTP -- record the confirmed details as the new verified
     * baseline, so a later unrelated edit does not reset KYC over a change
     * the customer has already confirmed. A customer whose KYC was reset
     * (phone or NIC changed) is left for OTP 1.
     */
    protected function refreshKycBaseline(Customer $customer, LoanCustomerConfirmation $confirmation, string $otpHash, ?User $staffUser): void
    {
        $customer = $customer->fresh(['currentKycVerification']);

        if (!$customer || !$customer->is_kyc_verified) {
            return;
        }

        $snapshot = $this->customerVerificationService->buildCustomerSnapshot($customer);
        $verified = $customer->currentKycVerification?->snapshot_data ?? [];
        $current = $snapshot;
        unset($verified['captured_at'], $current['captured_at'], $verified['customer']['bank_details']);

        if ($verified == $current) {
            return;
        }

        $verification = CustomerVerification::create([
            'customer_id'      => $customer->id,
            'created_by'       => $staffUser?->id ?? $confirmation->created_by,
            'phone_number'     => $confirmation->phone_number,
            'snapshot_version' => CustomerVerification::where('customer_id', $customer->id)->count() + 1,
            'snapshot_data'    => $snapshot,
            'otp_hash'         => $otpHash,
            'status'           => 'verified',
            'attempts'         => 0,
            'expires_at'       => now(),
            'verified_at'      => now(),
            'verified_by'      => $staffUser?->id,
        ]);

        $customer->update([
            'kyc_verified_at'             => now(),
            'current_kyc_verification_id' => $verification->id,
        ]);

        Log::info('Customer KYC baseline refreshed by loan confirmation OTP', [
            'customer_id'     => $customer->id,
            'verification_id' => $verification->id,
            'confirmation_id' => $confirmation->id,
        ]);
    }

    public function hasOpenCorrections(int $loanApplicationId, int $customerId): bool
    {
        return CustomerDetailCorrectionRequest::open()
            ->where('loan_application_id', $loanApplicationId)
            ->where('customer_id', $customerId)
            ->exists();
    }

    protected function latestConfirmationQuery(LoanApplication $loanApplication, int $customerId)
    {
        return LoanCustomerConfirmation::where('loan_application_id', $loanApplication->id)
            ->where('customer_id', $customerId)
            ->latest('id');
    }

    protected function assertRequiredCustomer(LoanApplication $loanApplication, int $customerId): void
    {
        if (!$this->requiredCustomers($loanApplication)->contains('id', $customerId)) {
            throw new Exception('This customer is not a borrower who confirms this loan application.');
        }
    }

    protected function reviewLink(string $token): string
    {
        return rtrim((string) config('app.frontend_url'), '/') . '/loan-review/' . $token;
    }
}
