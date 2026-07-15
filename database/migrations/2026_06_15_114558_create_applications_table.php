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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();
            $table->string('application_type'); // e.g. loan / lease
            $table->string('branch')->nullable();
            $table->string('loan_type')->nullable();
            $table->decimal('requested_amount', 15, 2);
            $table->text('purpose')->nullable();
            $table->integer('repayment_period_months')->nullable();
            $table->string('monthly_repayment_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
