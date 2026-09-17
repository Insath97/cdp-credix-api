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
        Schema::create('loan_application_status_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->string('from_status', 30);
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->constrained('users');
            $table->text('remarks');
            $table->date('change_date');
            $table->timestamp('changed_at');

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['loan_application_id', 'created_at'], 'loan_app_status_history_app_id_created_at_idx');
            $table->index(['application_id', 'changed_at'], 'loan_app_status_history_application_id_idx');
            $table->index(['change_date', 'to_status'], 'loan_app_status_history_date_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_application_status_history');
    }
};
