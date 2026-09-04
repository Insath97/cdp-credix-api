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
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('loan_revision_id')->nullable()->constrained('loan_revisions')->nullOnDelete();
            $table->unsignedInteger('installment_no');
            $table->date('due_date');
            $table->decimal('amount_due', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('penalty_amount', 15, 2)->default(0);
            $table->foreignId('penalty_waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('penalty_waived_reason')->nullable();
            $table->decimal('balance', 15, 2);
            $table->string('status', 20)->default('upcoming')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['loan_application_id', 'customer_id', 'installment_no'], 'loan_app_customer_installment_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};
