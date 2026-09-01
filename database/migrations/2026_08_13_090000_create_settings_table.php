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
                'value' => 'Entrepreneurship',
                'type' => 'string',
                'group' => 'group_loan',
                'description' => "Required competency confirmation value that a Group Loan application's competency field must match.",
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
