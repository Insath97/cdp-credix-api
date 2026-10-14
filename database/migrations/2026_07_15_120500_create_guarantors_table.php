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
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('type')->nullable(); // e.g. guarantor_1 / guarantor_2
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->string('id_image')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone_primary')->nullable();

            $table->string('employment_status', 50)->nullable();

            // Employed
            $table->string('occupation')->nullable();
            $table->string('employer_name')->nullable();

            // Self-Employed
            $table->string('business_name')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->string('business_phone', 20)->nullable();

            $table->date('date_joined')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->decimal('allowance', 15, 2)->nullable();
            $table->decimal('other_income', 15, 2)->nullable();
            $table->decimal('liabilities', 15, 2)->nullable();
            $table->string('bank_name_of_guarantor')->nullable();
            $table->string('bank_account_no_of_guarantor')->nullable();
            $table->string('bank_branch_of_guarantor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};
