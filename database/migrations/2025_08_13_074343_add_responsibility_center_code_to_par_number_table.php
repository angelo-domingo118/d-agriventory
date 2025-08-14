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
            $table->string('responsibility_center_code')->nullable()->comment('Responsibility center classification code')->after('inventory_code');
            $table->index('responsibility_center_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('par_number', function (Blueprint $table) {
            $table->dropIndex(['responsibility_center_code']);
            $table->dropColumn('responsibility_center_code');
        });
    }
};
