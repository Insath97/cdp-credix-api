<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Branch;
use App\Models\LoanApplication;
use Illuminate\Support\Facades\DB;

/**
 * The two human-readable references a loan application carries.
 *
 *   apply    APP-{BRANCH}-{yymmdd}{0001}   e.g. APP-COL-2609220001
 *   approval CDP-{BRANCH}-{yymmdd}{0001}   e.g. CDP-COL-2609220001
 *
 * One shape for both. Each carries the day it was minted and restarts its
 * counter each morning, per branch. The prefix is what tells them apart: APP is
 * stamped when the file is taken, CDP when it is approved, so one branch can
 * issue APP-COL-2609220001 and CDP-COL-2609220001 on the same day without
 * either treading on the other. They live in different columns of different
 * tables, each with its own unique index, and are never compared to each other.
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

    /**
     * The date segment both references carry: two-digit year, month, day.
     */
    public const DATE_FORMAT = 'ymd';

    /**
     * Digits in the counter that follows the date.
     *
     * Four, so a branch can take 9,999 files in one day. Past that the number
     * simply grows a digit rather than wrapping -- see nextSequence(), which
     * reads back a longer counter correctly.
     */
    public const SEQUENCE_DIGITS = 4;

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
            . '-' . now()->format(self::DATE_FORMAT);

        return $prefix . $this->nextSequence(
            (new Application())->getTable(),
            'application_no',
            $prefix,
            self::SEQUENCE_DIGITS
        );
    }

    /**
     * The approval reference stamped when a loan application is approved.
     *
     * Carries the date of the approval, not of the application: the two events
     * can be days apart, and this number answers "when was this lent", which is
     * the day it was approved.
     */
    public function forApproval(LoanApplication $loanApplication): string
    {
        $prefix = self::APPROVAL_PREFIX
            . '-' . $this->branchCodeFrom($loanApplication->branch_id)
            . '-' . now()->format(self::DATE_FORMAT);

        return $prefix . $this->nextSequence(
            (new LoanApplication())->getTable(),
            'approval_reference_no',
            $prefix,
            self::SEQUENCE_DIGITS
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
     * digit.
     *
     * The length floor is what keeps older, shorter numbers out. References
     * issued before this format read APP-{BRANCH}-{yymm}{0001}, and the first
     * six characters after the branch are yymm plus the first two counter
     * digits -- so APP-COL-26092134 really is matched by LIKE 'APP-COL-260921%'
     * and would be read as a counter of 34. Requiring at least the full
     * date-plus-counter width excludes them, while staying a floor rather than
     * an equality so a counter that has grown past 9,999 is still found.
     */
    protected function nextSequence(string $table, string $column, string $prefix, int $digits): string
    {
        $last = DB::table($table)
            ->where($column, 'like', $prefix . '%')
            ->whereRaw("LENGTH({$column}) >= ?", [strlen($prefix) + $digits])
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
