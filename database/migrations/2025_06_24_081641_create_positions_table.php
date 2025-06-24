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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique()->comment('Position title: Chief Administrative Officer, HRMDS Chief, Rice Coordinator, HVCDP Coordinator, SAAD Operations Officer, etc.');
            $table->string('code')->unique()->nullable()->comment('Position code/abbreviation.');
            $table->enum('position_type', ['DIVISION_CHIEF', 'COORDINATOR', 'FOCAL_PERSON', 'OFFICER', 'SPECIALIST', 'OTHER'])->comment('DIVISION_CHIEF, COORDINATOR, FOCAL_PERSON, OFFICER, SPECIALIST, OTHER.');
            $table->text('description')->nullable()->comment('Position description and responsibilities.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
