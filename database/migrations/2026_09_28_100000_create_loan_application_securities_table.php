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

            // One security per application; it lives and dies with the application.
            $table->foreignId('loan_application_id')->unique()->constrained('loan_applications')->cascadeOnDelete();

            // cdp_investment | property_mortgage | vehicle (App\Enums\LoanSecurityType).
            // Only the chosen type's columns below are filled; the rest stay null.
            $table->string('security_type', 30)->index();

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

            // Vehicle. The CR itself is uploaded as a document (document_type vehicle_cr).
            $table->string('vehicle_make', 100)->nullable();
            $table->string('vehicle_model', 100)->nullable();
            $table->unsignedSmallInteger('year_of_manufacture')->nullable();
            $table->unsignedSmallInteger('year_of_registration')->nullable();

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
