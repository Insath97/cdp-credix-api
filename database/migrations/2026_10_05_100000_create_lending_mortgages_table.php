<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lending_mortgages', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['CDP investment', 'Property', 'Vehicle']);
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->decimal('percentage', 5, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        DB::table('lending_mortgages')->insert([
            // CDP Investment plans
            [
                'type'       => 'CDP investment',
                'name'       => 'Investment Plan 1',
                'code'       => 'CDP-INV-001',
                'percentage' => 50.00,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type'       => 'CDP investment',
                'name'       => 'Investment Plan 2',
                'code'       => 'CDP-INV-002',
                'percentage' => 60.00,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type'       => 'CDP investment',
                'name'       => 'Investment Plan 3',
                'code'       => 'CDP-INV-003',
                'percentage' => 70.00,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Property Mortgage plans
            [
                'type'       => 'Property',
                'name'       => 'Property Mortgage Plan 1',
                'code'       => 'CDP-PRO-001',
                'percentage' => 70.00,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type'       => 'Property',
                'name'       => 'Property Mortgage Plan 2',
                'code'       => 'CDP-PRO-002',
                'percentage' => 50.00,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type'       => 'Property',
                'name'       => 'Property Mortgage Plan 3',
                'code'       => 'CDP-PRO-003',
                'percentage' => 60.00,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Vehicle plans
            [
                'type'       => 'Vehicle',
                'name'       => 'Vehicle Plan 1',
                'code'       => 'CDP-VEC-001',
                'percentage' => 70.00,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type'       => 'Vehicle',
                'name'       => 'Vehicle Plan 2',
                'code'       => 'CDP-VEC-002',
                'percentage' => 50.00,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type'       => 'Vehicle',
                'name'       => 'Vehicle Plan 3',
                'code'       => 'CDP-VEC-003',
                'percentage' => 60.00,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lending_mortgages');
    }
};
