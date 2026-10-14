<?php

namespace App\Models;

use App\Models\LoanApplicationSecurity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LendingMortgage extends Model
{
    use HasFactory;

    protected $table = 'lending_mortgages';

    protected $fillable = [
        'type',
        'name',
        'code',
        'percentage',
        'status',
    ];

    protected $casts = [
        'percentage' => 'float',
    ];

    public const TYPE_CDP_INVESTMENT = 'CDP investment';
    public const TYPE_PROPERTY = 'Property';
    public const TYPE_VEHICLE = 'Vehicle';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * Map type to code prefix
     */
    public static function prefixForType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'cdp investment', 'cdp_investment' => 'CDP-INV-',
            'property', 'property_mortgage'     => 'CDP-PRO-',
            'vehicle', 'vechile'                => 'CDP-VEC-',
            default                             => 'CDP-MORT-',
        };
    }

    /**
     * Normalize type to canonical string format for database storage
     */
    public static function canonicalType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'cdp investment', 'cdp_investment' => self::TYPE_CDP_INVESTMENT,
            'property', 'property_mortgage'     => self::TYPE_PROPERTY,
            'vehicle', 'vechile'                => self::TYPE_VEHICLE,
            default                             => $type,
        };
    }

    /**
     * Auto generate unique code for type (e.g. CDP-INV-001, CDP-PRO-001, CDP-VEC-001)
     */
    public static function generateCode(string $type): string
    {
        $canonicalType = self::canonicalType($type);
        $prefix = self::prefixForType($canonicalType);

        $last = static::where('type', $canonicalType)
            ->where('code', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$last) {
            return $prefix . '001';
        }

        $num = (int) str_replace($prefix, '', $last->code);
        return $prefix . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->type) {
                $model->type = static::canonicalType($model->type);
            }
            if (empty($model->code) && $model->type) {
                $model->code = static::generateCode($model->type);
            }
        });
    }

    public function loanApplicationSecurities()
    {
        return $this->hasMany(
            LoanApplicationSecurity::class,
            'lending_mortgage_id'
        );
    }
}
