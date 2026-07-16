<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->application_no)) {
                $branchCode = 'COL'; // Default fallback

                if (!empty($application->branch)) {
                    if (is_numeric($application->branch)) {
                        $branchModel = \App\Models\Branch::find($application->branch);
                        if ($branchModel && !empty($branchModel->code)) {
                            $branchCode = strtoupper($branchModel->code);
                        }
                    } else {
                        $branchModel = \App\Models\Branch::where('code', $application->branch)
                            ->orWhere('name', $application->branch)
                            ->first();
                        if ($branchModel && !empty($branchModel->code)) {
                            $branchCode = strtoupper($branchModel->code);
                        } else {
                            $branchCode = strtoupper(substr($application->branch, 0, 3));
                        }
                    }
                }

                // Keep only alphabetic characters (e.g. COL001 -> COL)
                $branchCode = preg_replace('/[^A-Za-z]/', '', $branchCode);
                if (empty($branchCode)) {
                    $branchCode = 'COL';
                }

                $currentYYMM = date('ym');
                $prefix = 'APP-' . $branchCode . '-' . $currentYYMM;

                // Find the last application number with this prefix
                $lastApplication = self::where('application_no', 'like', $prefix . '%')
                    ->orderBy('application_no', 'desc')
                    ->first();

                if ($lastApplication) {
                    $lastSeq = substr($lastApplication->application_no, -4);
                    $nextSeq = intval($lastSeq) + 1;
                } else {
                    $nextSeq = 1;
                }

                $application->application_no = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    protected $fillable = [
        'application_no',
        'application_type',
        'branch',
        'loan_type',
        'requested_amount',
        'purpose',
        'repayment_period_months',
        'monthly_repayment_date',
        'status',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'repayment_period_months' => 'integer',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'current_application_id');
    }

    public function applicationHistories(): HasMany
    {
        return $this->hasMany(ApplicationHistory::class);
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(Guarantor::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('application_no', 'like', "%$search%")
                ->orWhere('application_type', 'like', "%$search%")
                ->orWhere('branch', 'like', "%$search%")
                ->orWhere('loan_type', 'like', "%$search%")
                ->orWhere('status', 'like', "%$search%");
        });
    }
}
