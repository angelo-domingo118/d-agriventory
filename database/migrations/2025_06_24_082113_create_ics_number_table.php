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
        Schema::create('ics_number', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assigned_employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('contract_item_id')->constrained('contract_items')->onDelete('cascade');
            $table->enum('ics_type', ['SPLV', 'SPHV']);
            $table->integer('estimated_useful_life')->comment('ICS specific field.');
            $table->date('date_accepted');
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            // Add indexes for frequently queried fields
            $table->index('assigned_employee_id');
            $table->index('contract_item_id');
            $table->index('ics_type');
            $table->index('date_accepted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ics_number');
    }
};
