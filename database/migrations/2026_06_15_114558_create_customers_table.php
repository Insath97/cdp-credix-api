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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id')->nullable()->unique();
            $table->string('customer_code')->unique()->nullable();
            $table->string('full_name');
            $table->string('name_with_initials')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable()->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('landmark')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone_primary')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();
            $table->boolean('have_whatsapp')->default(false);
            $table->string('whatsapp_number')->nullable();
            $table->string('preferred_language')->nullable();

            // employment (merged)
            $table->string('employment_status')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('employer_address_line1')->nullable();
            $table->string('employer_address_line2')->nullable();
            $table->string('employer_city')->nullable();
            $table->string('employer_state')->nullable();
            $table->string('employer_country')->nullable();
            $table->string('employer_postal_code')->nullable();
            $table->string('employer_phone')->nullable();
            $table->string('employer_email')->nullable();
            $table->decimal('monthly_income', 15, 2)->nullable();

            // business (self-employed)
            $table->string('business_name')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->string('business_nature')->nullable();
            $table->string('business_address_line1')->nullable();
            $table->string('business_address_line2')->nullable();
            $table->string('business_city')->nullable();
            $table->string('business_state')->nullable();
            $table->string('business_country')->nullable();
            $table->string('business_postal_code')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_email')->nullable();

            $table->string('applicant_role')->nullable();
            $table->foreignId('current_application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('fixed_allowances', 15, 2)->nullable();
            $table->decimal('other_allowances', 15, 2)->nullable();
            $table->decimal('other_income', 15, 2)->nullable();
            $table->decimal('total_monthly_income', 15, 2)->nullable();
            $table->decimal('other_expenses', 15, 2)->nullable();
            $table->decimal('total_monthly_expenses', 15, 2)->nullable();


            $table->foreignId('recommended_by_employee_id')->nullable()->index();
            $table->string('recommender_name')->nullable();
            $table->string('recommender_employee_code')->nullable();
            $table->string('recommender_nic')->nullable();
            $table->string('recommender_phone')->nullable();


            $table->decimal('credit_score', 8, 2)->nullable()->index();

          
            $table->decimal('credit_score_on_time_rate', 5, 2)->nullable();
            $table->timestamp('credit_score_updated_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
