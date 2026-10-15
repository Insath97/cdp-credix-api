<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catch-up for databases created before two changes were merged into their
 * create migrations: the 'system' notification channel, and the
 * staff-assisted confirmation columns. Those databases never re-run a create
 * migration, so they got the new code without the new columns ("Data
 * truncated for column 'channel'"). Every step checks first, so on a fresh or
 * already-updated database this does nothing.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $channel = DB::selectOne("SHOW COLUMNS FROM notifications LIKE 'channel'");

            if ($channel && !str_contains($channel->Type, "'system'")) {
                DB::statement("ALTER TABLE notifications MODIFY channel ENUM('email','sms','system') NOT NULL DEFAULT 'email'");
            }
        }

        Schema::table('loan_customer_confirmations', function (Blueprint $table) {
            if (!Schema::hasColumn('loan_customer_confirmations', 'confirmation_method')) {
                $table->string('confirmation_method', 20)->nullable()->after('confirmed_user_agent');
            }
            if (!Schema::hasColumn('loan_customer_confirmations', 'confirmed_by')) {
                $table->foreignId('confirmed_by')->nullable()->after('confirmation_method')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('loan_customer_confirmations', 'staff_assist_reason')) {
                $table->text('staff_assist_reason')->nullable()->after('confirmed_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Nothing to undo: on a fresh database these columns belong to the create
     * migrations, and dropping them here would break those databases.
     */
    public function down(): void
    {
    }
};
