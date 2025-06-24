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
        Schema::create('ics_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ics_number_id')->constrained('ics_number')->onDelete('cascade');
            $table->foreignId('from_employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('to_employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('transfer_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ics_transfers');
    }
};
