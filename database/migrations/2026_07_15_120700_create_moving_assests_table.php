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
        Schema::create('moving_assests', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->nullable()->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('assest_category'); // e.g. vehicle, shares_bonds
            $table->string('owner_name');
            $table->string('make_model')->nullable();
            $table->string('company_name')->nullable();
            $table->integer('no_of_shares')->nullable();
            $table->decimal('par_value', 15, 2)->nullable();
            $table->string('registation_no')->nullable();
            $table->decimal('market_value', 15, 2)->nullable();
            $table->string('mortgage_lease_hire_status')->nullable();
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
        Schema::dropIfExists('moving_assests');
    }
};
