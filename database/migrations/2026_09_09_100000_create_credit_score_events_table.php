<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('credit_score_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('loan_installment_id')->constrained('loan_installments')->cascadeOnDelete();
            $table->string('event_type', 20)->index();
            // Signed: +credit_score_on_time_points, or the penalty negated.
            $table->decimal('points', 8, 2);
            $table->unsignedInteger('days_late')->default(0);
            $table->date('occurred_on')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->unique('loan_installment_id', 'credit_score_events_installment_unique');
            $table->index(['customer_id', 'loan_application_id'], 'credit_score_events_customer_loan_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_score_events');
    }
};
