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
        Schema::table('recovery_cases', function (Blueprint $table) {
            $table->enum('stage', ['internal', 'external'])->default('internal')->after('status');
            $table->foreignId('external_agent_id')->nullable()->after('assigned_agent_id')->constrained('external_recovery_agents')->nullOnDelete();
            $table->foreignId('parent_case_id')->nullable()->after('external_agent_id')->constrained('recovery_cases')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recovery_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_case_id');
            $table->dropConstrainedForeignId('external_agent_id');
            $table->dropColumn('stage');
        });
    }
};
