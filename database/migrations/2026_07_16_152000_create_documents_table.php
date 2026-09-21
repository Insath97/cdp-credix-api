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
            $table->string('document_type', 60)->index();

            $table->boolean('is_mandatory')->default(false);
            $table->string('document_name');
            $table->string('file_path');

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();


            $table->foreignId('loan_application_id')->nullable()->index();
            $table->foreignId('guarantor_id')->nullable()->constrained('guarantors')->cascadeOnDelete();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();

            // No reviewed_by / verified_by here. A customer's document is
            // shared by every loan they take, so a single stamp on this row
            // cannot say who checked it on which application -- see
            // create_loan_documents_table, which owns that per-application fact.

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
