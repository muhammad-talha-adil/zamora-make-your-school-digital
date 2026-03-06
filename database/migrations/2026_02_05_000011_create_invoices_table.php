<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade')->comment('FK to students table');
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');
            $table->string('invoice_number')->comment('unique per campus/session');
            $table->date('invoice_date');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            // Unique constraint for invoice_number per campus/session
            $table->unique(['invoice_number', 'campus_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
