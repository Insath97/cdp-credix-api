<?php

namespace Database\Seeders;

use App\Models\LoanProduct;
use App\Models\LoanTerm;
use App\Models\LoanType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoanCatalogSeeder extends Seeder
{
    /**
     * Seed a single loan term → loan type → loan product chain for local
     * development. Every record uses firstOrCreate keyed on its unique code,
     * so re-running the seeder never duplicates or aborts on a code that
     * already exists.
     */
    public function run(): void
    {
        $term = LoanTerm::firstOrCreate(
            ['code' => 'GENERAL'],
            [
                'title' => 'General',
                'description' => 'General purpose loan tenure.',
                'is_active' => true,
            ]
        );

        $type = LoanType::firstOrCreate(
            ['code' => 'STANDARD_BORROWING'],
            [
                'title' => 'Standard Borrowing',
                'description' => 'Standard borrowing facility.',
                'loan_term_id' => $term->id,
                'is_active' => true,
            ]
        );

        // The term ↔ type many-to-many pivot mirrors loan_types.loan_term_id
        // for anything reading the legacy relationship.
        $type->loanTerms()->syncWithoutDetaching([$term->id]);

        LoanProduct::firstOrCreate(
            ['code' => 'PLN-001'],
            [
                'name' => 'Personal Loan',
                'loan_type_id' => $type->id,
                'loan_term_id' => $term->id,
                'description' => 'Personal loan for individual borrowers.',
                'interest_rate' => 10.000,
                'interest_type' => 'flat',
                'min_amount' => 100000.00,
                'max_amount' => 50000000.00,
                'min_term_months' => 6,
                'max_term_months' => 60,
                'processing_fee_type' => 'fixed',
                'processing_fee_value' => 1500.00,
                'penalty_value' => null,
                'grace_period_days' => 0,
                'is_active' => true,
                'is_islamic' => false,
                'is_group_loan' => false,
            ]
        );

        $this->command->info('Loan catalog seeded: General term → Standard Borrowing type → Personal Loan product.');
    }
}