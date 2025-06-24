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
        Schema::create('items_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('Generic item name.');
            $table->string('unit', 50)->comment('unit of measure.');
            $table->foreignId('secondary_category_id')->constrained()->onDelete('cascade');
            $table->string('code', 50)->unique()->comment('Universal item code.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items_catalog');
    }
};
