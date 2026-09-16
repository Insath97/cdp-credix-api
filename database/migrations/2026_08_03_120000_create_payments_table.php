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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('loan_installment_id')->nullable()->constrained('loan_installments')->nullOnDelete();

           
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->string('receipt_no')->unique();
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'online'])->default('cash');

            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->json('carry_forward_breakdown')->nullable();

            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();

            $table->index(['loan_application_id', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
