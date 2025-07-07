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
        Schema::table('par_number', function (Blueprint $table) {
            $table->index(['assigned_employee_id', 'date_prepared']);
            $table->fullText('remarks');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index('contract_po_ib_number');
        });

        Schema::table('items_catalog', function (Blueprint $table) {
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('par_number', function (Blueprint $table) {
            $table->dropIndex(['assigned_employee_id', 'date_prepared']);
            $table->dropFullText('remarks');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['contract_po_ib_number']);
        });

        Schema::table('items_catalog', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
