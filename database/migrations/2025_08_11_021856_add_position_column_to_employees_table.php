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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('position')->nullable()->comment('Employee position/title');
        });

        // Migrate existing position data to the new single position field
        // Use position_title as the primary value, fallback to position_type if no title
        DB::statement("
            UPDATE employees 
            SET position = COALESCE(
                NULLIF(position_title, ''), 
                NULLIF(position_type, ''),
                NULL
            )
            WHERE position_title IS NOT NULL OR position_type IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
