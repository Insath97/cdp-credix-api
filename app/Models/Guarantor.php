<?php

namespace App\Models;

use App\Enums\LoanApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Guarantor extends Model
{
    use HasFactory;

    /**
     * Columns to select when a guarantor is loaded as a nested reference.
     *
     * Excludes the guarantor's NIC, ID image, date of birth, salary, allowances,
     * other income, declared liabilities and bank account details — none of which
     * a loan or application screen needs in order to list who is guaranteeing.
     *
     * @see Customer::SUMMARY_COLUMNS
     */
    public const SUMMARY_COLUMNS = 'id,customer_id,full_name,type,phone_primary,occupation';

    /**
     * Loan application states in which a guarantor is released again.
     *
     * A rejected, cancelled or closed loan has no one left on the hook for it,
     * so it neither marks a guarantor as used nor counts towards the limit.
     */
    public const RELEASED_STATUSES = [
        LoanApplicationStatus::Rejected,
        LoanApplicationStatus::Cancelled,
        LoanApplicationStatus::Closed,
    ];

    protected $appends = ['used_for_loan', 'live_loan_count', 'at_loan_limit'];

    /** Memoised per instance: the accessors below are read together. */
    private ?int $liveLoanCountCache = null;

    protected $fillable = [
        'customer_id',
        'full_name',
        'type',
        'id_type',
        'id_number',
        'id_image',
        'date_of_birth',
        'phone_primary',
        'employment_status',
        'occupation',
        'employer_name',
        'business_name',
        'business_registration_number',
        'business_phone',
        'date_joined',
        'salary',
        'allowance',
        'other_income',
        'liabilities',
        'bank_name_of_guarantor',
        'bank_account_no_of_guarantor',
        'bank_branch_of_guarantor',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_joined' => 'date',
        'salary' => 'decimal:2',
        'allowance' => 'decimal:2',
        'other_income' => 'decimal:2',
        'liabilities' => 'decimal:2',
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('full_name', 'like', "%$search%")
                ->orWhere('type', 'like', "%$search%")
                ->orWhere('id_type', 'like', "%$search%")
                ->orWhere('id_number', 'like', "%$search%")
                ->orWhere('phone_primary', 'like', "%$search%")
                ->orWhere('occupation', 'like', "%$search%")
                ->orWhere('employer_name', 'like', "%$search%")
                ->orWhere('bank_name_of_guarantor', 'like', "%$search%")
                ->orWhere('bank_account_no_of_guarantor', 'like', "%$search%")
                ->orWhere('bank_branch_of_guarantor', 'like', "%$search%");
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Loan applications this guarantor has been pledged against.
     */
    public function loanApplications(): BelongsToMany
    {
        return $this->belongsToMany(LoanApplication::class, 'loan_application_guarantors', 'guarantor_id', 'loan_application_id');
    }

    /**
     * Whether this guarantor is currently pledged to a loan application that
     * hasn't reached a terminal (released) status.
     */
    public function getUsedForLoanAttribute(): bool
    {
        return $this->liveLoanCount() > 0;
    }

    /**
     * How many live loans this PERSON is already standing guarantor for.
     *
     * Counted by ID number, not by row. A guarantor record belongs to one
     * customer and the loan wizard writes a fresh one for every application,
     * so one person is spread across many rows -- row 1 and row 3 in the
     * seeded data are both NIC 200012345678. A per-row count would therefore
     * read 1 forever, however many loans that person had actually pledged
     * themselves to, and any ceiling built on it would never bind.
     *
     * Rows with no ID number recorded cannot be matched to a person at all, so
     * they stand only for themselves.
     *
     * @param int|null $excludeLoanApplicationId Ignore this application, so a
     *        limit check can ask "how many OTHER loans is this person on".
     */
    public function liveLoanCount(?int $excludeLoanApplicationId = null): int
    {
        if ($excludeLoanApplicationId === null && $this->liveLoanCountCache !== null) {
            return $this->liveLoanCountCache;
        }

        $idNumber = mb_strtolower(trim((string) $this->id_number));

        $query = DB::table('loan_application_guarantors as lag')
            ->join('loan_applications as la', 'la.id', '=', 'lag.loan_application_id')
            ->join('guarantors as g', 'g.id', '=', 'lag.guarantor_id')
            ->whereNotIn('la.status', array_map(fn ($s) => $s->value, self::RELEASED_STATUSES));

        if ($idNumber === '') {
            $query->where('g.id', $this->id);
        } else {
            $query->whereRaw('LOWER(TRIM(g.id_number)) = ?', [$idNumber]);
        }

        if ($excludeLoanApplicationId !== null) {
            $query->where('la.id', '!=', $excludeLoanApplicationId);
        }

        // Distinct on the application: one person entered twice against the
        // same loan is still that one loan's guarantor.
        $count = $query->distinct()->count('la.id');

        if ($excludeLoanApplicationId === null) {
            $this->liveLoanCountCache = $count;
        }

        return $count;
    }

    public function getLiveLoanCountAttribute(): int
    {
        return $this->liveLoanCount();
    }

    /**
     * Whether this person has used up their allowance of guaranteed loans.
     */
    public function getAtLoanLimitAttribute(): bool
    {
        $limit = self::maxLoansPerGuarantor();

        return $limit > 0 && $this->liveLoanCount() >= $limit;
    }

    /**
     * The configured ceiling on live loans per guarantor; 0 means no limit.
     */
    public static function maxLoansPerGuarantor(): int
    {
        return max(0, (int) Setting::get('max_loans_per_guarantor', 1));
    }

}
