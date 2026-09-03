<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_products', function (Blueprint $table) {
            $table->boolean('is_islamic')->default(false)->change();
        });

        DB::table('loan_products')->whereNull('is_islamic')->update(['is_islamic' => false]);
    }

    public function down(): void
    {
        Schema::table('loan_products', function (Blueprint $table) {
            $table->boolean('is_islamic')->default(true)->change();
        });
    }
};
