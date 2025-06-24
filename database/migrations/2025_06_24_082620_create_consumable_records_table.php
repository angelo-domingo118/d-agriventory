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
        Schema::create('consumable_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_number')->unique()->comment('Unique record number for this batch.');
            $table->foreignId('division_id')->comment('The division that owns this stock.')->constrained()->onDelete('cascade');
            $table->date('date_received');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_records');
    }
};
