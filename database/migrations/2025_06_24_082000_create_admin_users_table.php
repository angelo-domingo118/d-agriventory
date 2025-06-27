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
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role', 50)->default('admin')->comment('Role identifier: admin.')->index();
            $table->json('permissions')->nullable()->comment('Custom permission sets for this admin.');
            $table->boolean('is_active')->default(true)->comment('Whether this admin account is active.')->index();
            $table->timestamp('last_login_at')->nullable()->comment('Timestamp of last admin login.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
