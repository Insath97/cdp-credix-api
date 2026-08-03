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
        Schema::create('recovery_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recovery_case_id')->constrained('recovery_cases')->cascadeOnDelete();
            $table->enum('activity_type', ['call', 'visit', 'payment_promise', 'collection_attempt'])->index();

            $table->text('notes')->nullable();
            $table->decimal('promised_amount', 15, 2)->nullable();
            $table->date('promised_date')->nullable();

            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovery_activities');
    }
};
