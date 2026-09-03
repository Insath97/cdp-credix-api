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

            $table->decimal('service_charge_percentage', 6, 3);
            $table->unsignedInteger('term_months');

            $table->decimal('service_charge_amount', 15, 2)->nullable();
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

            // available | locked | disbursed | closed (+ rejected | cancelled).
            // Coarser than loan_applications.status on purpose: the granular
            // submitted/verified/approved/active timeline lives on each
            // member's own application row.
            $table->string('status', 30)->default('available')->index();
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
