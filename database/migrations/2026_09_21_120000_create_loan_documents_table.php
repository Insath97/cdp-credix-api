<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which documents were used for which loan application, and who checked them
 * on that application.
 *
 * A customer's papers live on the customer, not on a loan -- an NIC copy
 * uploaded at registration follows them across every loan they ever take. That
 * made the review stamp on documents unworkable: reviewed_by is a single
 * column on a single shared row, so the same NIC copy could only ever record
 * one loan's reviewer, and reviewing the customer's third loan overwrote (and,
 * for anything left out of the submitted list, erased) the stamp the second
 * loan's reviewer had left.
 *
 * The stamp belongs to the pair, so it lives here. documents keeps the file;
 * this table keeps the fact that a named officer checked that file while
 * looking at one particular application.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // One row per document per application. The link is established
            // idempotently on every review and verify, so without this a file
            // looked at three times would carry three rows.
            $table->unique(['loan_application_id', 'document_id'], 'loan_document_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_documents');
    }
};
