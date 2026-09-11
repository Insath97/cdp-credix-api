<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Where a legal document stands.
 *
 * Two states, and only two, because the question the legal desk actually asks
 * of a loan file is binary: has the paperwork been drawn up or not. Everything
 * finer -- who signed, when it was printed -- is an audit fact recorded
 * alongside, not a stage of its own.
 */
enum LegalDocumentStatus: string
{
    /** Queued against a loan application; nothing has been drawn up yet. */
    case Pending = 'pending';

    /** The document has been prepared and is on the file. */
    case Created = 'created';

    public function label(): string
    {
        return Str::title($this->value);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
