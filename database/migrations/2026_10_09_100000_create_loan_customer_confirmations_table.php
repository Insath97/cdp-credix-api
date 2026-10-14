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
        Schema::create('loan_customer_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone_number');

            // Review link: only the sha256 of the token is stored.
            $table->string('token_hash', 64)->unique();
            $table->dateTime('link_expires_at');
            $table->boolean('sms_sent')->default(false);
            $table->dateTime('viewed_at')->nullable();

            // Exactly what the customer was shown, and its hash for tamper/staleness checks.
            $table->json('snapshot_data');
            $table->string('snapshot_hash', 64);

            // OTP 2, independent of the KYC OTP.
            $table->string('otp_hash')->nullable();
            $table->dateTime('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->unsignedTinyInteger('otp_requests')->default(0);

            $table->string('status')->default('pending'); // pending, confirmed, superseded, expired, failed

            // Declaration + audit evidence.
            $table->text('declaration_text')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->string('confirmed_ip', 45)->nullable();
            $table->string('confirmed_user_agent')->nullable();

            // Who completed it: the customer on their link, or staff entering the
            // OTP the customer read out to them (with the reason staff had to).
            $table->string('confirmation_method', 20)->nullable(); // customer_link, staff_assisted
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('staff_assist_reason')->nullable();
            $table->timestamps();

            $table->index(['loan_application_id', 'customer_id', 'status'], 'lcc_loan_customer_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_customer_confirmations');
    }
};
