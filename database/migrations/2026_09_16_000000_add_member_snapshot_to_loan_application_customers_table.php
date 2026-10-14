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
        // Group loan members carry a snapshot of their own details on the pivot,
        // so editing a member from the loan never rewrites the shared customer
        // record — that is what makes editing allowed even when the customer is
        // on other loans. Filled from the customer record when the member is
        // attached, then editable independently.
        Schema::table('loan_application_customers', function (Blueprint $table) {
            $table->string('member_name')->nullable()->after('customer_id');
            $table->string('nic')->nullable()->after('member_name');
            $table->string('address')->nullable()->after('nic');
            $table->string('phone_number')->nullable()->after('address');
            $table->string('gn_division')->nullable()->after('phone_number');
            $table->string('ds_division')->nullable()->after('gn_division');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_application_customers', function (Blueprint $table) {
            $table->dropColumn(['member_name', 'nic', 'address', 'phone_number', 'gn_division', 'ds_division']);
        });
    }
};