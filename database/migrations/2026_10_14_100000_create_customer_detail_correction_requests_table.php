<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A correction the customer asks for from their loan review link. The
        // customer and loan records are left alone until staff apply it.
        Schema::create('customer_detail_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('loan_customer_confirmation_id')->nullable()
                ->constrained('loan_customer_confirmations', 'id', 'cdcr_confirmation_fk')->nullOnDelete();

            $table->string('review_stage', 20); // stage_1, stage_2, stage_3
            $table->string('field_name');

            // Encrypted at rest (model casts).
            $table->text('current_value')->nullable();
            $table->text('proposed_value');
            $table->text('reason');

            $table->string('status', 20)->default('pending'); // pending, in_review, rejected, completed
    
            $table->string('applied_target')->nullable();
            $table->text('old_value')->nullable();
            $table->text('applied_value')->nullable();
            $table->text('applied_changes')->nullable();
            $table->text('review_remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['loan_application_id', 'customer_id', 'status'], 'cdcr_loan_customer_status_idx');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_detail_correction_requests');
    }
};
