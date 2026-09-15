<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Branch;
use App\Models\LoanApplication;
use Illuminate\Support\Facades\DB;

/**
 * The two human-readable references a loan application carries.
 *
 *   apply    APP-{BRANCH}-{yyyymmdd}{00000001}   e.g. APP-COL-2026090900000001
 *   approval CDP-{BRANCH}-{000000001}            e.g. CDP-COL-000000001
 *
 * They are deliberately different shapes: the application reference carries the
 * day it was taken and restarts its counter each day, while the approval
 * reference is one continuous series per branch. Different prefixes plus the
 * date mean the two can never produce the same string, which matters because
 * both appear side by side on the review screen and in conversation with a
 * customer.
 *
 * ## Branch code
 *
 * Taken from `branches.code`, uppercased with every non-alphanumeric character
 * removed, so `BR-COL` reads `BRCOL`. The separators have to go: a reference is
 * split on its hyphens to be read, and a branch code carrying one of its own
 * turns a three-part number into a four-part one that no longer parses. Digits
 * are kept (a code of `NG2` stays `NG2`); only punctuation and spaces are
 * dropped.
 */
class ReferenceNumberService
{
    public const APPLICATION_PREFIX = 'APP';
    public const APPROVAL_PREFIX = 'CDP';

    /** Digits in the per-day counter of an application reference. */
    public const APPLICATION_DIGITS = 8;

    /** Digits in the per-branch counter of an approval reference. */
    public const APPROVAL_DIGITS = 9;

    /** Used when a record has no branch to name. */
    public const FALLBACK_BRANCH_CODE = 'COL';

    /**
     * The application reference for a new Application row.
     *
     * `applications.branch` stores the branch *name*, not an id and not the
     * code, so the branch has to be looked up before its code can be read --
     * that indirection predates this class and is why the lookup accepts an
     * id, a code or a name.
     */
    public function forApplication(?string $branch): string
    {
        $prefix = self::APPLICATION_PREFIX
            . '-' . $this->branchCodeFrom($branch)
            . '-' . now()->format('Ymd');

        return $prefix . $this->nextSequence(
            (new Application())->getTable(),
            'application_no',
            $prefix,
            self::APPLICATION_DIGITS
        );
    }

    /**
     * The approval reference stamped when a loan application is approved.
     *
     * No date in the string, so the counter is one unbroken series per branch
     * rather than restarting daily.
     */
    public function forApproval(LoanApplication $loanApplication): string
    {
        $prefix = self::APPROVAL_PREFIX
            . '-' . $this->branchCodeFrom($loanApplication->branch_id)
            . '-';

        return $prefix . $this->nextSequence(
            (new LoanApplication())->getTable(),
            'approval_reference_no',
            $prefix,
            self::APPROVAL_DIGITS
        );
    }

    /**
     * Resolve a branch code from an id, a code, or a name.
     *
     * Returns the fallback rather than throwing: a reference number that reads
     * COL is recoverable, a loan application that could not be submitted
     * because its branch was mistyped is not.
     */
    public function branchCodeFrom(int|string|null $branch): string
    {
        if ($branch === null || $branch === '') {
            return self::FALLBACK_BRANCH_CODE;
        }

        $branchModel = is_numeric($branch)
            ? Branch::find($branch)
            : Branch::where('code', $branch)->orWhere('name', $branch)->first();

        $code = $branchModel?->code;

        if (empty($code)) {
            // A free-text branch that matches no row at all: keep the first
            // three characters, which is what the previous generator did and
            // what the older APP-... numbers in the data were built from.
            $code = is_numeric($branch) ? '' : substr((string) $branch, 0, 3);
        }

        // Strip everything that is not a letter or a digit, so a branch code of
        // BR-COL cannot smuggle its own hyphen into a hyphen-delimited
        // reference. Falls back rather than returning an empty segment for a
        // code made entirely of punctuation.
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));

        return $code !== '' ? $code : self::FALLBACK_BRANCH_CODE;
    }

    /**
     * The next zero-padded counter for everything already sharing this prefix.
     *
     * Locked for the duration of the surrounding transaction. Every other
     * generator in this codebase reads the current maximum with no lock at all,
     * which lets two concurrent submissions mint the same number and one of
     * them die on the unique index; `lockForUpdate` closes that window wherever
     * the caller is already inside a transaction, and costs nothing where it
     * is not.
     *
     * Ordered by LENGTH first because these are strings: without it '9' sorts
     * above '10' and the counter silently stops advancing once it gains a
     * digit. The old APP-... numbers are not matched by any prefix this class
     * builds, so they cannot interfere with the new series.
     */
    protected function nextSequence(string $table, string $column, string $prefix, int $digits): string
    {
        $last = DB::table($table)
            ->where($column, 'like', $prefix . '%')
            ->orderByRaw("LENGTH({$column}) DESC")
            ->orderBy($column, 'desc')
            ->lockForUpdate()
            ->value($column);

        $next = $last
            ? ((int) substr($last, strlen($prefix))) + 1
            : 1;

        return str_pad((string) $next, $digits, '0', STR_PAD_LEFT);
    }
}
