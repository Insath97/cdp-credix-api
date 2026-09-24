<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The documents -> loan_applications foreign key.
 *
 * create_documents_table (2026_07_16) runs before create_loan_applications_table
 * (2026_07_20), so the constraint cannot be declared where the column is: a
 * fresh migrate would die with "1824 Failed to open the referenced table
 * 'loan_applications'". The column stays in the documents create migration;
 * only the constraint waits here until the referenced table exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('documents', 'loan_application_id')) {
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

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('loan_application_id')
                ->references('id')
                ->on('loan_applications')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if ($this->connectionCannotAlterForeignKeys() || !$this->constraintExists()) {
            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign('documents_loan_application_id_foreign');
        });
    }

    private function connectionCannotAlterForeignKeys(): bool
    {
        return Schema::getConnection()->getDriverName() === 'sqlite';
    }


    private function constraintExists(): bool
    {
        foreach (Schema::getForeignKeys('documents') as $foreignKey) {
            if (in_array('loan_application_id', $foreignKey['columns'], true)) {
                return true;
            }
        }

        return false;
    }
};
