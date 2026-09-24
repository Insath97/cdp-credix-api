<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'recommended_by_employee_id')) {
            return;
        }

        if ($this->connectionCannotAlterForeignKeys()) {
            return;
        }

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
