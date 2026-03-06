<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->integer('due_day_of_month')->nullable()->comment('Optional due date day of month (1-31)');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
