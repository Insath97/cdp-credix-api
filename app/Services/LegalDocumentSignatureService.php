<?php

namespace App\Services;

use App\Enums\LegalDocumentStatus;
use App\Exceptions\LegalSignatureException;
use App\Models\LegalDocument;
use App\Models\LegalDocumentSignature;
use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * E-signatures on legal documents, drawn on screen at the branch.
 *
 * The lines to sign follow the printed document: every borrower on the loan,
 * the CDP Capital officer, the guarantors and the witnesses. Each signature
 * keeps a hash of what was signed, and once anyone has signed, the document
 * is locked until the signatures are cleared.
 */
class LegalDocumentSignatureService
{
    public const MAX_IMAGE_BYTES = 1048576;

    private const IMAGE_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

    /** The document fields a signature covers. */
    private const SIGNED_FIELDS = ['loan_application_id', 'legal_document_template_id', 'document_type', 'language', 'status', 'details'];

    /**
     * The signature lines of a document, in print order. A line whose
     * `missing` is set can't be signed until the document is completed.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lines(LegalDocument $document, ?LoanApplication $loan = null): array
    {
        $loan ??= $this->loan($document);
        $details = $document->details ?? [];
        $applicant = $document->document_type === 'loan_application_acknowledgement';

        $lines = [];
        foreach ($this->borrowers($loan) as $i => $customer) {
            $lines[] = $this->line(
                $i === 0 ? 'borrower' : "co_borrower_{$customer->id}",
                $i === 0 ? 'borrower' : 'co_borrower',
                $i === 0 ? ($applicant ? 'Applicant' : 'Borrower') : ($applicant ? 'Co-applicant' : 'Co-borrower'),
                $customer->full_name ?: $customer->name_with_initials,
                $customer->id_number,
                customerId: $customer->id,
            );
        }

        if ($applicant) {
            $name = $this->detail($details, 'receivedByOfficerName') ?? $this->detail($details, 'authorizedOfficerName');
            $lines[] = $this->line(
                'received_by_officer', 'officer', 'Documents Received By', $name, null,
                $this->detail($details, 'receivedByOfficerDesignation') ?? $this->detail($details, 'authorizedOfficerDesignation'),
                missing: $name ? null : "Enter the receiving officer's name on the document before they sign."
            );

            return $lines;
        }

        $name = $this->detail($details, 'authorizedOfficerName');
        $lines[] = $this->line(
            'authorized_officer', 'officer', 'For CDP Capital (Pvt) Ltd', $name, null,
            $this->detail($details, 'authorizedOfficerDesignation'),
            missing: $name ? null : "Enter the authorised officer's name on the document before they sign."
        );

        if ($document->document_type === 'direct_loan_agreement' && $loan) {
            $guarantors = $loan->loanApplicationGuarantors->sortBy('id')->pluck('guarantor')->filter()->take(2)->values();
            foreach ($guarantors as $i => $guarantor) {
                $lines[] = $this->line(
                    'guarantor_' . ($i + 1), 'guarantor', $i === 0 ? 'First Guarantor' : 'Second Guarantor',
                    $guarantor->full_name, $guarantor->id_number, guarantorId: $guarantor->id
                );
            }
        }

        foreach ([1, 2] as $n) {
            $name = $this->detail($details, "witness{$n}Name");
            $nic  = $this->detail($details, "witness{$n}Nic");
            $lines[] = $this->line(
                "witness_{$n}", 'witness', "Witness {$n}", $name, $nic,
                missing: $name && $nic ? null : "Enter Witness {$n}'s name and NIC on the document before they sign."
            );
        }

        return $lines;
    }

    /**
     * SHA-256 of what a signer puts their name to: the document as typed, the
     * loan terms it prints, and who signs it.
     */
    public function contentHash(LegalDocument $document, ?LoanApplication $loan = null, ?array $lines = null): string
    {
        $loan ??= $this->loan($document);
        $lines ??= $this->lines($document, $loan);

        $terms = [];
        foreach (['requested_amount', 'approved_amount', 'interest_rate', 'interest_type', 'term_months'] as $column) {
            $terms[$column] = $loan?->getRawOriginal($column);
        }

        return hash('sha256', json_encode($this->canonical([
            'loan_application_id' => $document->loan_application_id,
            'reference_no'        => $document->reference_no,
            'document_type'       => $document->document_type,
            'language'            => $document->language,
            'details'             => $document->details ?? [],
            'terms'               => $terms,
            'signers'             => array_map(fn (array $l) => [$l['key'], $l['name'], $l['nic']], $lines),
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
    }

    /** The lines, who has signed them (with the images to print), and whether the document is complete. */
    public function status(LegalDocument $document): array
    {
        $loan    = $this->loan($document);
        $lines   = $this->lines($document, $loan);
        $hash    = $this->contentHash($document, $loan, $lines);
        $signed  = $document->signatures()->with('capturer:' . User::SUMMARY_COLUMNS)->get()->keyBy('signer_key');
        $changed = $signed->contains(fn (LegalDocumentSignature $s) => $s->document_hash !== $hash);

        $lines = array_map(function (array $line) use ($signed) {
            $signature = $signed->get($line['key']);
            $bytes = $signature?->imageBytes();

            return $line + [
                'signed'    => $signature !== null,
                'signature' => $signature ? [
                    'id'           => $signature->id,
                    'signer_name'  => $signature->signer_name,
                    'signer_nic'   => $signature->signer_nic,
                    'signed_at'    => $signature->signed_at,
                    'captured_by'  => $signature->capturer,
                    'image'        => $bytes !== null ? "data:{$signature->signature_mime};base64," . base64_encode($bytes) : null,
                    // False when the stored file is missing or not the one captured.
                    'image_intact' => $bytes !== null && hash('sha256', $bytes) === $signature->signature_sha256,
                ] : null,
            ];
        }, $lines);

        $signedCount = count(array_filter($lines, fn (array $l) => $l['signed']));

        return [
            'legal_document_id'   => $document->id,
            'reference_no'        => $document->reference_no,
            'document_type'       => $document->document_type,
            'document_type_label' => $document->document_type_label,
            'status'              => $document->status?->value,
            'required_count'      => count($lines),
            'signed_count'        => $signedCount,
            'is_fully_signed'     => $document->signed_at !== null,
            'signed_at'           => $document->signed_at,
            // Someone signed content that has since changed: the document, the loan terms or the signers.
            'content_changed'     => $changed,
            'can_sign'            => $document->status === LegalDocumentStatus::Created
                && $document->is_active && !$changed && $signedCount < count($lines),
            'lines'               => $lines,
        ];
    }

    /**
     * Put one signature on one line.
     *
     * @param UploadedFile|string $image a PNG/JPEG upload, or a data:image/...;base64 URI from a canvas
     */
    public function capture(LegalDocument $document, string $key, UploadedFile|string $image): LegalDocumentSignature
    {
        [$bytes, $mime] = $this->readImage($image);

        return DB::transaction(function () use ($document, $key, $bytes, $mime) {
            // Locked so two screens can't sign the same line at once.
            $document = LegalDocument::lockForUpdate()->findOrFail($document->id);

            if ($document->status !== LegalDocumentStatus::Created) {
                throw new LegalSignatureException('Finish preparing the document (status Created) before anyone signs it.', 'status');
            }
            if (!$document->is_active) {
                throw new LegalSignatureException("This document is inactive, so it can't be signed.", 'status');
            }

            $loan  = $this->loan($document);
            $lines = collect($this->lines($document, $loan))->keyBy('key');
            $line  = $lines->get($key);

            if (!$line) {
                throw new LegalSignatureException(sprintf(
                    '"%s" is not a signature line on this document. Its lines are: %s.', $key, $lines->keys()->implode(', ')
                ));
            }
            if ($line['missing']) {
                throw new LegalSignatureException($line['missing']);
            }

            $signed = $document->signatures()->get();
            if ($signed->contains('signer_key', $key)) {
                throw new LegalSignatureException("{$line['label']} has already signed. Clear that signature to sign again.");
            }

            $hash = $this->contentHash($document, $loan, $lines->values()->all());
            if ($signed->contains(fn (LegalDocumentSignature $s) => $s->document_hash !== $hash)) {
                throw new LegalSignatureException(
                    'The document or its loan has changed since the first signature. Clear the signatures and sign again.',
                    'signatures'
                );
            }

            $path = sprintf('legal-signatures/%d/%s-%s.%s', $document->id, $key, Str::uuid(), self::IMAGE_TYPES[$mime]);
            $disk = Storage::disk(LegalDocumentSignature::DISK);
            $disk->put($path, $bytes);

            try {
                $signature = $document->signatures()->create([
                    'signer_key'         => $key,
                    'signer_role'        => $line['role'],
                    'customer_id'        => $line['customer_id'],
                    'guarantor_id'       => $line['guarantor_id'],
                    'signer_name'        => mb_substr($line['name'], 0, 255),
                    'signer_nic'         => $line['nic'] !== null ? mb_substr($line['nic'], 0, 30) : null,
                    'signer_designation' => $line['designation'] !== null ? mb_substr($line['designation'], 0, 255) : null,
                    'signature_path'     => $path,
                    'signature_mime'     => $mime,
                    'signature_sha256'   => hash('sha256', $bytes),
                    'document_hash'      => $hash,
                    'signed_at'          => now(),
                    'captured_by'        => Auth::id(),
                    'ip_address'         => request()->ip(),
                    'user_agent'         => Str::limit((string) request()->userAgent(), 497),
                ]);
            } catch (\Throwable $e) {
                $disk->delete($path);
                throw $e;
            }

            if ($lines->keys()->diff($signed->pluck('signer_key')->push($key))->isEmpty()) {
                $document->forceFill(['signed_at' => now()])->save();
            }

            return $signature;
        });
    }

    /**
     * Clear the signatures, all of them or one line's. They are voided, not
     * deleted, so the audit trail keeps them.
     */
    public function clear(LegalDocument $document, ?string $key, string $reason): int
    {
        return DB::transaction(function () use ($document, $key, $reason) {
            $document = LegalDocument::lockForUpdate()->findOrFail($document->id);

            $count = $document->signatures()
                ->when($key !== null, fn ($q) => $q->where('signer_key', $key))
                ->update(['voided_at' => now(), 'voided_by' => Auth::id(), 'void_reason' => $reason]);

            if ($count === 0) {
                throw new LegalSignatureException(
                    $key !== null ? "There is no signature on the \"{$key}\" line to clear." : 'This document has no signatures to clear.',
                    $key !== null ? 'signer' : 'signatures'
                );
            }

            $document->forceFill(['signed_at' => null])->save();

            return $count;
        });
    }

    /**
     * Refuse an edit that would change what has been signed. Re-sending the
     * same values is fine; anything else needs the signatures cleared first.
     */
    public function assertEditable(LegalDocument $document, array $data): void
    {
        $count = $document->signatures()->count();
        if ($count === 0) {
            return;
        }

        foreach (self::SIGNED_FIELDS as $field) {
            // A null leaves a field as it is, except details, which it empties.
            if (!array_key_exists($field, $data) || ($data[$field] === null && $field !== 'details')) {
                continue;
            }

            $normalise = fn ($value) => $this->canonical($field === 'details' ? ($value ?? []) : $value);
            if ($normalise($data[$field]) !== $normalise($document->getAttribute($field))) {
                throw new LegalSignatureException(
                    "This document has {$count} signature(s). Clear the signatures before changing it.",
                    $field
                );
            }
        }
    }

    public function assertDeletable(LegalDocument $document): void
    {
        if ($document->signatures()->exists()) {
            throw new LegalSignatureException('This document has been signed. Clear the signatures before deleting it.', 'signatures');
        }
    }

    private function loan(LegalDocument $document): ?LoanApplication
    {
        return LoanApplication::with([
            'customer',
            'loanApplicationCustomers.customer',
            'loanApplicationGuarantors.guarantor',
        ])->find($document->loan_application_id);
    }

    /** Every borrower on the loan, the primary first: the joint or group set, else the one customer. */
    private function borrowers(?LoanApplication $loan): Collection
    {
        if (!$loan) {
            return collect();
        }

        $customers = $loan->loanApplicationCustomers->sortBy('id')->pluck('customer')->filter();
        if ($customers->isEmpty()) {
            $customers = collect([$loan->customer])->filter();
        }

        return $customers->sortBy(fn ($customer) => $customer->id === (int) $loan->customer_id ? 0 : 1)->values();
    }

    /** A typed detail, falling back to its Tamil/Sinhala entry; null when blank. */
    private function detail(array $details, string $key): ?string
    {
        foreach ([$key, "{$key}Localized"] as $k) {
            $value = $details[$k] ?? null;
            $value = is_scalar($value) ? trim((string) $value) : '';
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function line(
        string $key,
        string $role,
        string $label,
        ?string $name,
        ?string $nic,
        ?string $designation = null,
        ?int $customerId = null,
        ?int $guarantorId = null,
        ?string $missing = null
    ): array {
        return [
            'key'          => $key,
            'role'         => $role,
            'label'        => $label,
            'name'         => $name,
            'nic'          => $nic,
            'designation'  => $designation,
            'customer_id'  => $customerId,
            'guarantor_id' => $guarantorId,
            'missing'      => $missing ?? ($name ? null : "{$label} has no name on record."),
        ];
    }

    /** Sorted keys and string scalars, so equal content always compares and hashes the same. */
    private function canonical(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }
        if (!is_array($value)) {
            return $value === null ? '' : (string) $value;
        }
        if (!array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($v) => $this->canonical($v), $value);
    }

    /**
     * The image's bytes and type, checked: PNG or JPEG, at most 1 MB, a
     * sensible size, and not a blank pad.
     *
     * @return array{0: string, 1: string}
     */
    private function readImage(UploadedFile|string $image): array
    {
        if ($image instanceof UploadedFile) {
            $bytes = (string) file_get_contents($image->getRealPath());
        } elseif (preg_match('#^data:image/(?:png|jpeg);base64,(.+)$#s', trim($image), $m)) {
            $bytes = (string) base64_decode($m[1], true);
        } else {
            throw new LegalSignatureException('Send the signature as a PNG or JPEG image, or as a data:image/png;base64 URI.', 'signature');
        }

        if (strlen($bytes) > self::MAX_IMAGE_BYTES) {
            throw new LegalSignatureException('The signature image is larger than 1 MB.', 'signature');
        }

        $info = $bytes !== '' ? @getimagesizefromstring($bytes) : false;
        if (!$info || !isset(self::IMAGE_TYPES[$info['mime']])) {
            throw new LegalSignatureException('The signature is not a readable PNG or JPEG image.', 'signature');
        }
        if ($info[0] < 50 || $info[1] < 20 || $info[0] > 3000 || $info[1] > 3000) {
            throw new LegalSignatureException('The signature image must be between 50×20 and 3000×3000 pixels.', 'signature');
        }
        if (!$this->hasInk($bytes)) {
            throw new LegalSignatureException('The signature is blank. Ask the signer to sign again.', 'signature');
        }

        return [$bytes, $info['mime']];
    }

    /** Whether the pad has any ink: a pixel that is visible and not near-white. */
    private function hasInk(string $bytes): bool
    {
        if (!function_exists('imagecreatefromstring')) {
            return true;
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return false;
        }
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $width  = imagesx($image);
        $height = imagesy($image);
        // Up to about a million samples; any real stroke crosses many of them.
        $step = max(1, (int) ceil(sqrt($width * $height / 1000000)));

        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                $rgba  = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                $light = (($rgba >> 16) & 0xFF) + (($rgba >> 8) & 0xFF) + ($rgba & 0xFF);
                if ($alpha < 100 && $light < 600) {
                    return true;
                }
            }
        }

        return false;
    }
}
