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
        Schema::create('dashboard_targets', function (Blueprint $table) {
            $table->id();

            $table->string('metric', 30)->index(); // disbursement | recovery | approval_rate
            $table->decimal('target_value', 15, 2);
            $table->date('period_start');
            $table->date('period_end');

            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['metric', 'period_start', 'period_end'], 'dashboard_targets_metric_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_targets');
    }
};
