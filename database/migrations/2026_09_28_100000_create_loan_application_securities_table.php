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
        Schema::create('loan_application_securities', function (Blueprint $table) {
            $table->id();

            // Securities live and die with the application. Multiple securities may secure one application.
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();

            // cdp_investment | property_mortgage | vehicle (App\Enums\LoanSecurityType).
            // Only the chosen type's columns below are filled; the rest stay null.
            $table->string('security_type', 30)->index();
            $table->string('security_plan', 50)->nullable();
            $table->foreignId('lending_mortgage_id')
                ->nullable()
                ->after('security_plan')
                ->constrained('lending_mortgages')
                ->nullOnDelete();

            // CDP Investment. The policy is checked against CDP Core on the way in;
            // investment_details keeps what Core reported at that moment.
            $table->string('investment_nic', 20)->nullable();
            $table->string('policy_number', 100)->nullable()->index();
            $table->json('investment_details')->nullable();

            // Property Mortgage, plus the optional evaluation.
            $table->string('owner_name')->nullable();
            $table->string('property_type', 40)->nullable();
            $table->string('owner_deed_number', 100)->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->date('evaluation_date')->nullable();
            $table->string('evaluated_by')->nullable();
            $table->text('evaluation_remarks')->nullable();

            // Vehicle. The CR and the valuation report are uploaded as documents.
            $table->string('vehicle_make', 100)->nullable();
            $table->string('vehicle_model', 100)->nullable();
            $table->unsignedSmallInteger('year_of_manufacture')->nullable();
            $table->unsignedSmallInteger('year_of_registration')->nullable();
            $table->string('registration_number', 50)->nullable()->index();
            $table->string('chassis_engine_number', 100)->nullable();
            $table->decimal('vehicle_value', 15, 2)->nullable();

            // Values snapshotted when the security is attached/measured.
            $table->decimal('pledged_value', 15, 2)->nullable();
            $table->decimal('max_loan_percentage', 5, 2)->nullable();
            $table->decimal('max_loan_amount', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_application_securities');
    }
};
