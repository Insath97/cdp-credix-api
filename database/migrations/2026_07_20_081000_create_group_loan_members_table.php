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
        Schema::create('group_loan_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_loan_id')->constrained('group_loans')->cascadeOnDelete();
            $table->foreignId('loan_application_id')->unique()->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->string('member_name');
            $table->string('nic');
            $table->string('address');
            $table->string('phone_number');
            $table->string('gn_division');
            $table->string('ds_division');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_loan_members');
    }
};
