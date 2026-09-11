<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The per-installment credit score ledger.
     *
     * One row per installment that has been judged: '+n' for a month settled
     * on time, '-n' for one settled late or already past its grace period with
     * money still owed. Installments that are still inside their window, or
     * that were waived or superseded by a revision, produce no row at all.
     *
     * The ledger is DERIVED, never incremented: CreditScoreService recomputes
     * a (loan, customer) pair from the installment rows and replaces its events
     * wholesale. That is what makes a deleted payment, a waived penalty or a
     * loan revision correct themselves instead of leaving the score poisoned.
     */
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
