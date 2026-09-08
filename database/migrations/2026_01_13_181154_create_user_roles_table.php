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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            // No constraint: `campuses` is not created until a later migration,
            // so the reference could never be satisfied. It went unnoticed while
            // the tables were MyISAM, which ignores foreign keys outright.
            // The table itself is dropped once roles move to spatie/permission.
            $table->foreignId('campus_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'role_id', 'campus_id']);
            $table->index('user_id');
            $table->index('role_id');
            $table->index('campus_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
