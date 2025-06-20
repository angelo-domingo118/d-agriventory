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
        Schema::create('idr_number', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->unique()->comment('Sequential IDR/RSMI number.');
            $table->foreignId('assigned_employee_id')->constrained('employees')->onDelete('cascade')->comment('The employee responsible for the stock (e.g. Supply Officer).');
            $table->foreignId('approving_employee_id')->constrained('employees')->onDelete('cascade')->comment('The division chief who approves this IDR.');
            $table->foreignId('contract_item_id')->constrained()->onDelete('cascade');
            $table->string('inventory_code')->comment('IDR specific field.');
            $table->string('ors')->comment('IDR specific field.');
            $table->date('date_accepted');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idr_number');
    }
}; 