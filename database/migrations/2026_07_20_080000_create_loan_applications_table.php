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
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('loan_product_id')->constrained('loan_products')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('group_loan_id')->nullable()->constrained('group_loans')->nullOnDelete();

            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();

            // Nullable because a Group Loan has no interest rate at all — its
            // repayment is derived purely from the group's service charge
            // percentage (group_loans.service_charge_percentage).
            $table->decimal('interest_rate', 6, 3)->nullable();
            $table->string('interest_type')->nullable()->default('flat');
            
            $table->unsignedInteger('term_months');
            $table->decimal('monthly_installment', 15, 2)->nullable();
            $table->decimal('processing_fee', 10, 2)->nullable();
            $table->decimal('net_disbursement_amount', 15, 2)->nullable();

            $table->string('monthly_repayment_date')->nullable();
            
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->text('approval_remarks')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            
            $table->decimal('outstanding_balance', 15, 2)->nullable();

            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('assigned_reviewer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
