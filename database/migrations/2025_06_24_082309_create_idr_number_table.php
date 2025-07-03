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
            $table->foreignId('assigned_employee_id')->comment('The employee responsible for the stock (e.g. Supply Officer).')->constrained('employees')->onDelete('cascade');
            $table->foreignId('approving_employee_id')->comment('The division chief who approves this IDR.')->constrained('employees')->onDelete('cascade');
            $table->foreignId('contract_item_id')->constrained('contract_items')->onDelete('cascade');
            $table->unsignedInteger('quantity')->comment('Total quantity of all items in this batch');
            $table->string('inventory_code')->comment('IDR specific field.');
            $table->string('ors')->comment('IDR specific field.');
            $table->date('date_prepared')->comment('Date when the document was prepared');
            $table->date('date_accepted');
            $table->text('remarks')->nullable();
            $table->foreignId('received_by_id')->constrained('employees')->comment('The employee who received the items');
            $table->foreignId('received_from_id')->constrained('employees')->comment('The employee who issued the items');
            $table->date('date');
            $table->timestamps();

            // Add indexes for frequently queried fields
            $table->index('assigned_employee_id');
            $table->index('approving_employee_id');
            $table->index('contract_item_id');
            $table->index('date_prepared');
            $table->index('date_accepted');
            $table->index('quantity');
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
