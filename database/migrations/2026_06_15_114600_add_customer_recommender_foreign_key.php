<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The customers -> employees foreign key, declared here rather than in
 * create_customers_table.
 *
 * Both create migrations carry the timestamp 2026_06_15_114558 and Laravel
 * breaks the tie on filename, so "create_customers_table" runs before
 * "create_employees_table". A constraint declared in the customers migration
 * therefore points at a table that does not exist yet, and a fresh migrate
 * dies with "1824 Failed to open the referenced table 'employees'".
 *
 * This runs two seconds later, once employees exists. It is one of the very
 * few permanent standalone migrations in this schema -- the convention is to
 * merge new columns into the original create migration (see ARCHITECTURE.md
 * §11), and the column itself still lives there. Only the constraint moved.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'recommended_by_employee_id')) {
            return;
        }

        // Databases created before this migration existed already carry the
        // constraint, added by the patch migration that introduced the column.
        if ($this->constraintExists()) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('recommended_by_employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!$this->constraintExists()) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign('customers_recommended_by_employee_id_foreign');
        });
    }

    private function constraintExists(): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'customers')
            ->where('CONSTRAINT_NAME', 'customers_recommended_by_employee_id_foreign')
            ->exists();
    }
};
