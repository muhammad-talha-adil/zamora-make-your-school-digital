<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_paid_one_time_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('fee_head_id')->constrained('fee_heads')->onDelete('cascade');
            $table->decimal('amount_paid', 10, 2);
            $table->date('payment_date');
            $table->foreignId('voucher_id')->nullable()->constrained('fee_vouchers')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Prevent duplicate entries for the same student and fee head
            $table->unique(['student_id', 'fee_head_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_paid_one_time_fees');
    }
};
