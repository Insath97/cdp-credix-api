<?php

namespace App\Traits;

trait MasksSensitiveDataTrait
{
    /**
     * Mask all but the last $visible characters of a sensitive string.
     */
    protected function maskTail(?string $value, int $visible = 4): ?string
    {
        if (empty($value)) {
            return $value;
        }

        $length = strlen($value);

        if ($length <= $visible) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - $visible) . substr($value, -$visible);
    }
}
