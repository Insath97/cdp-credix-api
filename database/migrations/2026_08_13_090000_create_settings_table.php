<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            [
                'key' => 'installment_due_period_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'loan_recovery',
                'description' => 'Days after an installment due date before it is marked overdue (used when the loan product has no grace_period_days set).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'overdue_sms_frequency_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'loan_recovery',
                'description' => 'How often (in days) to send an SMS reminder to a customer with an overdue installment.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'overdue_sms_duration_weeks',
                'value' => '3',
                'type' => 'integer',
                'group' => 'loan_recovery',
                'description' => 'How many weeks after becoming overdue to keep sending overdue SMS reminders.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'internal_recovery_threshold_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'loan_recovery',
                'description' => 'Days overdue at which a loan application is automatically escalated to internal recovery.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'external_recovery_threshold_days',
                'value' => '45',
                'type' => 'integer',
                'group' => 'loan_recovery',
                'description' => 'Days overdue at which an internal recovery case is automatically escalated to external recovery.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sms_notifications_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'loan_recovery',
                'description' => 'Master switch for overdue SMS notifications.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'recovery_escalation_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'loan_recovery',
                'description' => 'Master switch for automatic recovery case escalation (internal/external).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'customer_bank_list',
                'value' => json_encode([
                    'Commercial Bank',
                    'DFCC Bank',
                    'HNB',
                    'HDFC Bank',
                    'NSB',
                    'NTB',
                    'NDB',
                    'Pan Asia Bank',
                    "People's Bank",
                    'Sampath Bank',
                ]),
                'type' => 'json',
                'group' => 'customer',
                'description' => 'Bank names offered in the customer bank details dropdown.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'loan_revision_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'loan_revision',
                'description' => 'Master switch for the loan revision (restructuring) feature.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'loan_revision_allowed_types',
                'value' => json_encode([
                    'reduce_installment',
                    'extend_term',
                    'principal_only',
                ]),
                'type' => 'json',
                'group' => 'loan_revision',
                'description' => 'Revision types an officer may create for a loan application.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'group_loan_competency',
                'value' => json_encode([
                    'Entrepreneurship',
                ]),
                'type' => 'json',
                'group' => 'group_loan',
                'description' => "Competency options offered for a Group Loan application's competency field — the submitted value must match one of these.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'group_loan_service_charge_percentage',
                'value' => '10',
                // 'decimal', not 'string'. The type is what UpdateSettingRequest
                // validates against, and it had no case for 'string', so this
                // percentage accepted any text at all -- then reached
                // GroupLoanController as (float), where 'ten percent' is 0.0.
                'type' => 'decimal',
                'group' => 'group_loan',
                'description' => 'Service charge percentage applied to a Group Loan\'s principal in place of interest, snapshotted onto the application at submission time.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'loan_approval_segregation_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'loan_approval',
                'description' => 'Require review, verify and approve to be performed by three different users. Turn off only where one officer legitimately handles the whole file (a very small branch), since it removes the maker-checker control.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'credit_score_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'credit_score',
                'description' => 'Master switch for repayment credit scoring. Turning it off stops all recomputation; scores already stored are left untouched rather than wiped, so switching it back on resumes from where it stopped.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'credit_score_on_time_points',
                'value' => '10',
                'type' => 'integer',
                'group' => 'credit_score',
                'description' => 'Points added to the score for each installment settled on or before its due date (plus the credit score grace days).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'credit_score_late_penalty_points',
                'value' => '10',
                'type' => 'integer',
                'group' => 'credit_score',
                'description' => 'Points taken off the score for each installment settled late, or still unpaid past its grace period. Enter a positive number; it is subtracted. Two days late and ninety days late cost the same.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'credit_score_grace_days',
                'value' => '0',
                'type' => 'integer',
                'group' => 'credit_score',
                'description' => 'Days after the due date a payment may still arrive and count as on time. Separate from the recovery grace period, which decides when a loan turns overdue and is charged a penalty.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_loans_per_guarantor',
                'value' => '1',
                'type' => 'integer',
                'group' => 'guarantor',
                'description' => 'How many live loans one person may stand guarantor for. Counted by ID number across every customer, so the same person is one guarantor no matter how many times they have been entered. Loans that are rejected, cancelled or closed release their guarantors and stop counting. Set 0 for no limit.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'credit_score_count_unpaid_overdue',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'credit_score',
                'description' => 'Deduct points as soon as an installment passes its grace period unpaid, instead of waiting until it is eventually paid. Turn this off and a borrower who simply never pays would never lose a point.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
