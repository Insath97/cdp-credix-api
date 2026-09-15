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
        Schema::create('recovery_cases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('case_no')->unique();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'escalated', 'closed'])->default('open')->index();
            $table->enum('stage', ['internal', 'external'])->default('internal');
            $table->decimal('overdue_amount', 15, 2)->nullable();

            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('external_agent_id')->nullable()->constrained('external_recovery_agents')->nullOnDelete();
            $table->foreignId('parent_case_id')->nullable()->constrained('recovery_cases')->nullOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovery_cases');
    }
};
