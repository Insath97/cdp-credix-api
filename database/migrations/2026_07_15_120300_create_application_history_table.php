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
        Schema::create('application_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('application_no');
            $table->string('application_type');
            $table->string('role');
            $table->string('status');
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->decimal('fixed_allowances', 15, 2)->nullable();
            $table->decimal('other_allowances', 15, 2)->nullable();
            $table->decimal('other_income', 15, 2)->nullable();
            $table->decimal('total_monthly_income', 15, 2)->nullable();
            $table->decimal('household_expenses', 15, 2)->nullable();
            $table->decimal('rent_expense', 15, 2)->nullable();
            $table->decimal('insurance_premiums', 15, 2)->nullable();
            $table->decimal('other_expenses', 15, 2)->nullable();
            $table->decimal('total_monthly_expenses', 15, 2)->nullable();
            $table->decimal('requested_amount', 15, 2)->nullable();
            $table->text('purpose')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_history');
    }
};
