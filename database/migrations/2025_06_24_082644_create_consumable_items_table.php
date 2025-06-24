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
        Schema::create('consumable_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_specification_id')->constrained()->onDelete('cascade');
            $table->integer('initial_quantity');
            $table->integer('current_quantity')->comment('Updated by the division inventory manager.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_items');
    }
};
