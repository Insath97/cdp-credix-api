<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * A signature can't be captured or cleared, or a signed legal document can't
 * be changed. Rendered in the FormRequest error shape.
 */
class LegalSignatureException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $field = 'signer',
        public readonly int $status = 422
    ) {
        parent::__construct($message);
    }

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $this->getMessage(),
            'errors'  => [
                ['field' => $this->field, 'messages' => [$this->getMessage()]],
            ],
        ], $this->status);
    }
}
