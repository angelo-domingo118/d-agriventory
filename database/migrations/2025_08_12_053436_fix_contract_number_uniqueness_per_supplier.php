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
        // Fix contracts table to allow same contract number for different suppliers
        Schema::table('contracts', function (Blueprint $table) {
            // Drop the current global unique constraint
            $table->dropUnique(['contract_po_ib_number']);

            // Create composite unique index that includes supplier_id and deleted_at
            // This allows same contract number for different suppliers and handles soft deletes
            $table->unique(['supplier_id', 'contract_po_ib_number', 'deleted_at'], 'contracts_supplier_contract_deleted_at_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original global unique constraint
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropUnique('contracts_supplier_contract_deleted_at_unique');
            $table->unique('contract_po_ib_number');
        });
    }
};
