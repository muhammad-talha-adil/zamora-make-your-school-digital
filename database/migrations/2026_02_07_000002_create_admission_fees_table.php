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
        Schema::create('admission_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('admission_date');
            $table->decimal('admission_fee', 10, 2)->default(0);
            $table->enum('payment_status', ['paid', 'pending', 'waived'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_fees');
    }
};
