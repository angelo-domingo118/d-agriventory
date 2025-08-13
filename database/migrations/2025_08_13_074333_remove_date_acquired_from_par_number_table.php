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
            $table->dropIndex(['date_acquired']);
            $table->dropColumn('date_acquired');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('par_number', function (Blueprint $table) {
            $table->date('date_acquired')->comment('Date when the property was acquired');
            $table->index('date_acquired');
        });
    }
};