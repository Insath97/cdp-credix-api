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
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->decimal('interest_rate', 6, 3);
            $table->string('interest_type')->nullable()->default('flat');
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2);
            $table->unsignedInteger('min_term_months');
            $table->unsignedInteger('max_term_months');
            $table->enum('processing_fee_type', ['fixed', 'percentage']);
            $table->decimal('processing_fee_value', 10, 2);
            $table->decimal('penalty_value', 10, 2)->nullable();
            $table->unsignedInteger('grace_period_days')->default(0);
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
        Schema::dropIfExists('loan_products');
    }
};
