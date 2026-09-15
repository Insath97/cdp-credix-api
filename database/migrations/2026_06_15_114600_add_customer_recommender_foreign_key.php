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

        // SQLite cannot bolt a constraint onto an existing table at all, and
        // the test suite runs on an in-memory SQLite database. The column is
        // what the application reads; the constraint is a production integrity
        // guarantee, so skipping it here costs the tests nothing.
        if ($this->connectionCannotAlterForeignKeys()) {
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
        if ($this->connectionCannotAlterForeignKeys() || !$this->constraintExists()) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign('customers_recommended_by_employee_id_foreign');
        });
    }

    /**
     * SQLite has no ALTER TABLE ... ADD CONSTRAINT, so a foreign key can only
     * be declared when the table is created.
     */
    private function connectionCannotAlterForeignKeys(): bool
    {
        return Schema::getConnection()->getDriverName() === 'sqlite';
    }

    /**
     * Asked through Laravel's own schema introspection rather than a raw
     * information_schema query: that table only exists on MySQL, so the raw
     * form made this migration -- and therefore the whole test suite -- fail
     * on every other driver.
     */
    private function constraintExists(): bool
    {
        foreach (Schema::getForeignKeys('customers') as $foreignKey) {
            if (in_array('recommended_by_employee_id', $foreignKey['columns'], true)) {
                return true;
            }
        }

        return false;
    }
};
