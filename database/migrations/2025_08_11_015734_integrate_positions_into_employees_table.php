<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add position fields to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->string('position_title')->nullable()->comment('Position title (e.g., Chief Administrative Officer, Rice Coordinator)');
            $table->string('position_code')->nullable()->comment('Position code/abbreviation');
            $table->enum('position_type', ['DIVISION_CHIEF', 'COORDINATOR', 'FOCAL_PERSON', 'OFFICER', 'SPECIALIST', 'OTHER'])->nullable()->comment('Type of position');
            $table->text('position_description')->nullable()->comment('Position description and responsibilities');
        });

        // Step 2: Migrate existing position data from the relationship
        DB::statement('
            UPDATE employees e 
            JOIN positions p ON e.position_id = p.id 
            SET 
                e.position_title = p.title,
                e.position_code = p.code,
                e.position_type = p.position_type,
                e.position_description = p.description
        ');

        // Step 3: Drop the foreign key constraint and position_id column
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropColumn('position_id');
        });

        // Step 4: Drop the positions table
        Schema::dropIfExists('positions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Recreate positions table
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique()->comment('Position title: Chief Administrative Officer, HRMDS Chief, Rice Coordinator, HVCDP Coordinator, SAAD Operations Officer, etc.');
            $table->string('code')->unique()->nullable()->comment('Position code/abbreviation.');
            $table->enum('position_type', ['DIVISION_CHIEF', 'COORDINATOR', 'FOCAL_PERSON', 'OFFICER', 'SPECIALIST', 'OTHER'])->comment('DIVISION_CHIEF, COORDINATOR, FOCAL_PERSON, OFFICER, SPECIALIST, OTHER.');
            $table->text('description')->nullable()->comment('Position description and responsibilities.');
            $table->timestamps();
            $table->softDeletes();
        });

        // Step 2: Recreate unique positions from employees data
        DB::statement('
            INSERT INTO positions (title, code, position_type, description, created_at, updated_at)
            SELECT DISTINCT 
                position_title, 
                position_code, 
                position_type, 
                position_description,
                NOW(),
                NOW()
            FROM employees 
            WHERE position_title IS NOT NULL
        ');

        // Step 3: Add back position_id to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('position_id')->nullable()->comment('The specific position/role.')->constrained()->onDelete('set null');
        });

        // Step 4: Update employees with position_id from recreated positions
        DB::statement('
            UPDATE employees e 
            JOIN positions p ON e.position_title = p.title 
            SET e.position_id = p.id
            WHERE e.position_title IS NOT NULL
        ');

        // Step 5: Remove position fields from employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['position_title', 'position_code', 'position_type', 'position_description']);
        });
    }
};
