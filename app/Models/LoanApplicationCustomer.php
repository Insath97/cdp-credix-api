<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApplicationCustomer extends Model
{
    use HasFactory;

    protected $table = 'loan_application_customers';

    protected $fillable = [
        'loan_application_id',
        'customer_id',
        // Per-group member snapshot. Editing one never rewrites the shared
        // customer record, so a member may be edited from the loan even when
        // their customer is on other loans.
        'member_name',
        'nic',
        'address',
        'phone_number',
        'gn_division',
        'ds_division',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'customer_id'         => 'integer',
    ];

    /**
     * Relationship with LoanApplication
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * Relationship with Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Fill the per-group member snapshot from the attached customer's current
     * record. Called when a member is first attached, so the loan holds its
     * own copy that can be edited freely afterwards.
     */
    public function snapshotFromCustomer(): static
    {
        $customer = $this->customer;
        $detail = $customer?->customerDetail;

        if (!$customer) {
            return $this;
        }

        $this->update([
            'member_name'   => $customer->full_name ?: ($customer->name_with_initials ?? null),
            'nic'           => $customer->id_number,
            'address'       => trim(implode(', ', array_filter([
                $customer->address_line_1,
                $customer->address_line_2,
                $customer->city,
            ]))),
            'phone_number'  => $customer->phone_primary,
            'gn_division'   => $detail?->gn_division,
            'ds_division'   => $detail?->ds_division,
        ]);

        return $this;
    }
}
