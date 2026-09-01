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
        Schema::create('group_loans', function (Blueprint $table) {
            $table->id();

            $table->string('group_loan_no')->unique();

            $table->foreignId('loan_product_id')->constrained('loan_products')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->string('group_name');
            $table->unsignedInteger('number_of_members');
            $table->string('competency');

            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();

            $table->decimal('interest_rate', 6, 3);
            $table->string('interest_type')->default('flat');
            $table->unsignedInteger('term_months');

            $table->decimal('interest_amount', 15, 2)->nullable();
            $table->decimal('total_repayment_amount', 15, 2)->nullable();
            $table->decimal('amount_per_member', 15, 2)->nullable();

            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_reviewer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('approval_remarks')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();

            $table->string('status', 30)->default('submitted')->index();
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
        Schema::dropIfExists('group_loans');
    }
};
