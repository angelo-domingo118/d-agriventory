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
        Schema::create('ics_item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ics_number_id')->constrained('ics_number')->onDelete('cascade');
            $table->unsignedInteger('quantity')->comment('Quantity for this batch.');
            $table->text('identification_data')->nullable()->comment('Serial numbers, asset tags.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ics_item_batches');
    }
};
