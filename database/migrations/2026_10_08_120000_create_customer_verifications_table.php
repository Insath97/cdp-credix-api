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
        Schema::create('customer_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone_number');
            $table->unsignedInteger('snapshot_version')->default(1);
            $table->json('snapshot_data');
            $table->string('otp_hash');
            $table->string('status')->default('pending'); // pending, verified, expired, failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('expires_at');
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['expires_at']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_kyc_verified')->default(false)->after('is_active');
            $table->dateTime('kyc_verified_at')->nullable()->after('is_kyc_verified');
            $table->foreignId('current_kyc_verification_id')
                ->nullable()
                ->after('kyc_verified_at')
                ->constrained('customer_verifications')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['current_kyc_verification_id']);
            $table->dropColumn(['current_kyc_verification_id', 'kyc_verified_at', 'is_kyc_verified']);
        });

        Schema::dropIfExists('customer_verifications');
    }
};
