<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The legal documents a loan product's loans are drawn up with. The
     * product decides which documents; the loan type decides each one's
     * template.
     */
    public function up(): void
    {
        Schema::create('loan_product_legal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_product_id')->constrained('loan_products')->cascadeOnDelete();

            // App\Enums\LegalDocumentType
            $table->enum('document_type', [
                'direct_loan_agreement',
                'loan_agreement_investment',
                'loan_application_acknowledgement',
            ]);

            $table->timestamps();

            $table->unique(['loan_product_id', 'document_type'], 'loan_product_legal_documents_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_product_legal_documents');
    }
};
