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
        Schema::create('idr_item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idr_number_id')->constrained('idr_number')->onDelete('cascade');
            $table->integer('quantity')->comment('The initial total quantity for this batch/card.');
            $table->text('identification_data')->nullable()->comment('Serial numbers, asset tags.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idr_item_batches');
    }
};
