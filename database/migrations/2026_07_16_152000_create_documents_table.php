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
            
            // A plain string rather than an enum. The set grows (the loan
            // application checklist added division certificates and two
            // confirmation letters), and every addition to a MySQL enum is an
            // ALTER on a table that only gets bigger. The allowed values live
            // in Document::TYPES, which is what the FormRequests validate
            // against -- one list, in PHP, greppable.
            $table->string('document_type', 60)->index();
            
            $table->boolean('is_mandatory')->default(false);
            $table->string('document_name');
            $table->string('file_path');
            
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // What this document was uploaded against.
            //
            // A customer's NIC copy belongs to the customer and follows them
            // across loans; the pay slips backing one application belong to
            // that application, because the next loan needs fresh ones. A
            // guarantor's papers belong to the guarantor. All three are
            // nullable and independent: a row can name a customer AND the loan
            // application it was collected for.
            $table->foreignId('loan_application_id')->nullable()->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('guarantor_id')->nullable()->constrained('guarantors')->cascadeOnDelete();
            
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
