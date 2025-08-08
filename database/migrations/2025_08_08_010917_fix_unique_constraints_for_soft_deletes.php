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
        // Fix primary_categories table
        Schema::table('primary_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropUnique(['code']);
            // Create composite unique indexes that include deleted_at
            // This allows multiple deleted records with same name/code since deleted_at will be different timestamps
            $table->unique(['name', 'deleted_at'], 'primary_categories_name_deleted_at_unique');
            $table->unique(['code', 'deleted_at'], 'primary_categories_code_deleted_at_unique');
        });

        // Fix secondary_categories table
        Schema::table('secondary_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropUnique(['code']);
            // Create composite unique indexes that include deleted_at
            $table->unique(['name', 'deleted_at'], 'secondary_categories_name_deleted_at_unique');
            $table->unique(['code', 'deleted_at'], 'secondary_categories_code_deleted_at_unique');
        });

        // Fix items_catalog table
        Schema::table('items_catalog', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropUnique(['code']);
            // Create composite unique indexes that include deleted_at
            $table->unique(['name', 'deleted_at'], 'items_catalog_name_deleted_at_unique');
            $table->unique(['code', 'deleted_at'], 'items_catalog_code_deleted_at_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the composite unique indexes and restore original simple unique constraints
        Schema::table('primary_categories', function (Blueprint $table) {
            $table->dropUnique('primary_categories_name_deleted_at_unique');
            $table->dropUnique('primary_categories_code_deleted_at_unique');
            $table->unique('name');
            $table->unique('code');
        });

        Schema::table('secondary_categories', function (Blueprint $table) {
            $table->dropUnique('secondary_categories_name_deleted_at_unique');
            $table->dropUnique('secondary_categories_code_deleted_at_unique');
            $table->unique('name');
            $table->unique('code');
        });

        Schema::table('items_catalog', function (Blueprint $table) {
            $table->dropUnique('items_catalog_name_deleted_at_unique');
            $table->dropUnique('items_catalog_code_deleted_at_unique');
            $table->unique('name');
            $table->unique('code');
        });
    }
};
