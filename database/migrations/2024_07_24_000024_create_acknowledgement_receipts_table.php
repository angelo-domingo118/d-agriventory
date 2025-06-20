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
        Schema::create('acknowledgement_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idr_item_batch_id')->constrained()->onDelete('cascade')->comment('The batch this AR draws from.');
            $table->integer('quantity_reduced')->comment('Quantity taken/reduced in this transaction.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acknowledgement_receipts');
    }
}; 