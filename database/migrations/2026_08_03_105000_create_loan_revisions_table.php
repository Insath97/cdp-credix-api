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
        Schema::create('loan_revisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->unsignedInteger('revision_no');
            $table->string('revision_type', 30)->index();
            $table->string('status', 30)->default('pending_approval')->index();

            $table->decimal('previous_outstanding_amount', 15, 2);
            $table->unsignedInteger('previous_term');
            $table->decimal('previous_installment_amount', 15, 2);

            $table->decimal('revised_outstanding_amount', 15, 2);
            $table->unsignedInteger('revised_term');
            $table->decimal('revised_installment_amount', 15, 2);

            $table->text('reason');
            $table->string('document')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('effective_date')->nullable();

            $table->timestamps();

            $table->unique(['loan_application_id', 'revision_no'], 'loan_app_revision_unique');
            $table->index(['loan_application_id', 'status'], 'loan_app_revision_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_revisions');
    }
};
