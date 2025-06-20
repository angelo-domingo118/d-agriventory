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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->comment('The user who performed the action.');
            $table->string('table_name')->comment("The table where the action occurred (e.g., 'users', 'items_catalog').");
            $table->unsignedBigInteger('record_id')->comment("The ID of the record in the 'table_name' that was affected.");
            $table->string('action_type')->comment("e.g., 'CREATE', 'UPDATE', 'DELETE'.");
            $table->json('old_values')->nullable()->comment("JSON blob of the record's state before the change (for UPDATE/DELETE).");
            $table->json('new_values')->nullable()->comment("JSON blob of the record's state after the change (for CREATE/UPDATE).");
            $table->text('description')->nullable()->comment('Optional: A brief description or reason for the action.');
            $table->timestamp('created_at')->comment('Timestamp of the log entry.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
}; 