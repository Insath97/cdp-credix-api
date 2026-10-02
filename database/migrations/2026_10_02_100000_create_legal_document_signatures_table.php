<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One signature on one signature line of a legal document, drawn on
     * screen at the branch. Cleared signatures are voided, not deleted, so
     * the audit trail keeps every signature ever captured.
     */
    public function up(): void
    {
        Schema::create('legal_document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_document_id')->constrained('legal_documents')->cascadeOnDelete();

            // The line signed: borrower, co_borrower_{customer id}, guarantor_1/2,
            // witness_1/2, authorized_officer or received_by_officer.
            $table->string('signer_key', 40);
            $table->string('signer_role', 30);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('guarantor_id')->nullable()->constrained('guarantors')->nullOnDelete();
            $table->string('signer_name');
            $table->string('signer_nic', 30)->nullable();
            $table->string('signer_designation')->nullable();

            $table->string('signature_path', 500);
            $table->string('signature_mime', 30);
            $table->char('signature_sha256', 64);

            // SHA-256 of the document and loan terms as they stood when signed.
            $table->char('document_hash', 64);

            $table->timestamp('signed_at')->useCurrent();
            $table->foreignId('captured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 500)->nullable();

            $table->timestamps();

            $table->index(['legal_document_id', 'signer_key', 'voided_at'], 'legal_signatures_document_line_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_signatures');
    }
};
