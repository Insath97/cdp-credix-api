<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The legal paperwork a loan product is sold on.
     *
     * Per product, because the agreement a borrower signs is not the same
     * document for every scheme -- a direct loan carries two guarantors and a
     * collateral schedule, an investment-backed one surrenders a certificate.
     * Which document belongs to which product is therefore DATA an officer
     * configures, never a mapping hardcoded in the app: nobody outside the
     * legal desk knows it, and it changes when a product does.
     */
    public function up(): void
    {
        Schema::create('legal_document_templates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_product_id')->constrained('loan_products')->cascadeOnDelete();
            $table->string('document_type', 60)->index();

            $table->string('language', 5)->default('en')->index();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('file_path', 1000)->nullable();
            $table->string('source_file_name')->nullable();
            $table->longText('content')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(
                ['loan_product_id', 'document_type', 'language'],
                'legal_templates_product_type_language_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_templates');
    }
};
