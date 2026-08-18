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
        Schema::create('loan_term_loan_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_term_id')->constrained('loan_terms')->cascadeOnDelete();
            $table->foreignId('loan_type_id')->constrained('loan_types')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['loan_term_id', 'loan_type_id'], 'loan_term_loan_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_term_loan_type');
    }
};
