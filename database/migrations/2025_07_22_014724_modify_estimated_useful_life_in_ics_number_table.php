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
        Schema::table('ics_number', function (Blueprint $table) {
            $table->integer('estimated_useful_life')->nullable()->comment('ICS specific field.')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ics_number', function (Blueprint $table) {
            $table->integer('estimated_useful_life')->nullable(false)->comment('ICS specific field.')->change();
        });
    }
};
