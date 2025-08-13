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
            $table->dropColumn('inventory_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('par_number', function (Blueprint $table) {
            $table->string('inventory_code')->comment('PAR-specific inventory classification')->after('account_code');
        });
    }
};