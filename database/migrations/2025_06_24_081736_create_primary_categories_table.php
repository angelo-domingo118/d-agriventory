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
        Schema::create('primary_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Primary category name, unique across the system.');
            $table->string('code')->unique()->comment('Unique code identifier for the primary category.');
            $table->text('description')->nullable()->comment('Optional detailed description of the primary category.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('primary_categories');
    }
};
