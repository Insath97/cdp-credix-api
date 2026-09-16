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

          
            $table->decimal('interest_rate', 6, 3)->nullable();
            $table->string('interest_type')->nullable()->default('flat');

            $table->unsignedInteger('term_months');
            $table->decimal('monthly_installment', 15, 2)->nullable();
            $table->decimal('processing_fee', 10, 2)->nullable();
            $table->decimal('net_disbursement_amount', 15, 2)->nullable();

            $table->string('monthly_repayment_date')->nullable();

            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('reviewed_remarks')->nullable();
            $table->text('verified_remarks')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();

            // The borrower's answer to the approved offer.
            //
            // One set of columns, not one per outcome: the status says which
            // answer it was, and these carry the latest one. The full
            // sequence -- put on hold Tuesday, accepted Friday -- lives in
            // loan_application_status_history like every other transition.
            $table->foreignId('offer_responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('offer_responded_at')->nullable();
            // A code from LoanApplication::OFFER_DECLINE_REASONS, not free
            // text: "why do borrowers walk away from what we approve" is a
            // question the analytics module has to be able to group by.
            $table->string('offer_decline_reason', 60)->nullable();
            $table->text('offer_remarks')->nullable();

            $table->decimal('outstanding_balance', 15, 2)->nullable();

            // Minted by ReferenceNumberService the first time this application
            // reaches Approved: CDP-{BRANCH}-{000000001}. Null until then, and
            // never regenerated -- a reference a customer has been given must
            // survive the loan being reverted and re-approved.
            $table->string('approval_reference_no')->nullable()->unique();

            // The CDP employee who recommends this customer. Every loan is
            // introduced by someone, and when one goes bad the business needs
            // to know who put it forward.
            //
            // The link and the four details are both stored on purpose. The
            // link is what lets you list every loan an employee introduced;
            // the snapshot is what the employee actually asserted on the day,
            // and it has to survive them changing their phone number, being
            // renamed, or leaving (the FK nulls out, the record does not).
            // It also lets a recommender who is not yet on the payroll system
            // be recorded at all.
            $table->foreignId('recommended_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('recommender_name')->nullable();
            $table->string('recommender_employee_code')->nullable();
            $table->string('recommender_nic')->nullable();
            $table->string('recommender_phone')->nullable();

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
