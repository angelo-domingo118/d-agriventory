<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes the direct link between an employee and a user account.
     * The 'employees' table will now represent all personnel, regardless of
     * whether they have system access.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop the foreign key constraint before dropping the column.
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * This will restore the link between an employee and a user account.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->after('id');
        });
    }
};
