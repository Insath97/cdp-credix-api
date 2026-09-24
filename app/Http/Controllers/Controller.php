<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * The largest page any listing endpoint will serve.
     */
    public const MAX_PER_PAGE = 100;

    /**
     * The page size to use when the caller does not ask for one.
     */
    public const DEFAULT_PER_PAGE = 15;

    /**
     * Rows per page, clamped to something a listing can actually serve.
     *
     * List endpoints used to hand `per_page` straight to paginate(). Two values
     * broke that: `per_page=0` reaches LengthAwarePaginator, which divides by
     * the page size and raises DivisionByZeroError, surfacing as an opaque 500
     * through each controller's catch-all; and an enormous `per_page` hydrates
     * a whole table into memory on request.
     *
     * The `?: DEFAULT_PER_PAGE` step matters as much as the bounds: a cleared
     * filter sends `per_page=`, which casts to 0, and 0 is the value that blows
     * up rather than one that falls back.
     */
    protected function perPage(Request $request, int $max = self::MAX_PER_PAGE): int
    {
        $requested = (int) $request->get('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($requested ?: self::DEFAULT_PER_PAGE, $max));
    }
}
