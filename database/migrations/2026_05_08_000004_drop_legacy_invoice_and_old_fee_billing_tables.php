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
        Schema::table('student_inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('student_inventory_items', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
        });

        Schema::table('student_inventory_records', function (Blueprint $table) {
            if (Schema::hasColumn('student_inventory_records', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
        });

        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('student_fees');
        Schema::dropIfExists('fee_types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('sessions')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('sessions')->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained('fee_types')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('pending');
            $table->date('assigned_date');
            $table->date('due_date')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->json('discount_snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->date('payment_date');
            $table->string('payment_mode');
            $table->decimal('amount', 10, 2);
            $table->string('reference_number')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('student_inventory_records', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('billing_date')->constrained('invoices')->nullOnDelete();
        });

        Schema::table('student_inventory_items', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('status')->constrained('invoices')->nullOnDelete();
        });
    }
};
