<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One legal document drawn up for one loan application.
     *
     * The template says what the agreement is; this row says that it was
     * produced for a particular borrower, when, and by whom.
     */
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('legal_created_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('legal_updated_at')->nullable();

            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();

            $table->foreignId('legal_document_template_id')->nullable()
                ->constrained('legal_document_templates')->nullOnDelete();

            $table->string('reference_no')->unique();
            $table->string('document_type', 60)->index();
            $table->string('language', 5)->default('en');

            $table->string('status', 20)->default('pending')->index();

            // Stamped when the status first reaches Created, not on every save,
            // so "when was this drawn up" survives later edits.
            $table->timestamp('legal_document_created_date')->nullable();

            // What the officer typed that the loan record cannot supply:
            // agreement place and date, investment and surrendered-certificate
            // details, guarantor addresses, witnesses, the signing officer.
            //
            // One JSON column rather than twenty-five nullable ones. These are
            // the body of a printed page -- read back whole, never filtered or
            // aggregated on -- and the set differs per document type, so
            // columns would leave most of them null on most rows.
            $table->json('details')->nullable();

            // How often this agreement has actually been taken off the
            // printer, and when it last was. A printed original is a physical
            // object the legal desk has to account for -- "we issued three
            // copies" is a question that gets asked -- so it is counted here
            // rather than left to whatever the browser happened to remember.
            $table->unsignedInteger('printed_count')->default(0);
            $table->timestamp('last_printed_at')->nullable();

            $table->text('remarks')->nullable();

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['loan_application_id', 'document_type'], 'legal_documents_application_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
