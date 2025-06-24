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
        Schema::create('par_item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('par_number_id')->constrained('par_number')->onDelete('cascade');
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
        Schema::dropIfExists('par_item_batches');
    }
};
