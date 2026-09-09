<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('customer_credit_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();

            $table->decimal('total_points', 10, 2)->default(0);
            $table->unsignedInteger('installments_counted')->default(0);
            $table->unsignedInteger('on_time_count')->default(0);
            $table->unsignedInteger('late_count')->default(0);
            $table->decimal('average_points', 8, 2)->nullable();
            $table->decimal('final_score', 8, 2)->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['loan_application_id', 'customer_id'], 'customer_credit_scores_loan_customer_unique');
            $table->index('customer_id', 'customer_credit_scores_customer_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credit_scores');
    }
};
