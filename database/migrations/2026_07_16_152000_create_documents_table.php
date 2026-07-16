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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            
            $table->enum('document_type', [
                'nic_copy', 
                'passport_copy', 
                'driving_license', 
                'salary_slip', 
                'bank_statement', 
                'billing_proof', 
                'salary_assignment_letter', 
                'employer_letter', 
                'photo', 
                'other'
            ]);
            
            $table->boolean('is_mandatory')->default(false);
            $table->string('document_name');
            $table->string('file_path');
            
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            
            $table->enum('status', ['active', 'rejected', 'expired'])->default('active');
            $table->text('remarks')->nullable();
            
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
        Schema::dropIfExists('documents');
    }
};
