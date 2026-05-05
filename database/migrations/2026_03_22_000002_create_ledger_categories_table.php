<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the ledger categories table for income/expense categorization.
     */
    public function up(): void
    {
        Schema::create('ledger_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['INCOME', 'EXPENSE']);
            $table->foreignId('parent_id')->nullable()->constrained('ledger_categories')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed default income categories
        $incomeCategories = [
            ['name' => 'Tuition Fee', 'type' => 'INCOME', 'parent_id' => null],
            ['name' => 'Admission Fee', 'type' => 'INCOME', 'parent_id' => null],
            ['name' => 'Transport Fee', 'type' => 'INCOME', 'parent_id' => null],
            ['name' => 'Book Sales', 'type' => 'INCOME', 'parent_id' => null],
            ['name' => 'Uniform Fee', 'type' => 'INCOME', 'parent_id' => null],
            ['name' => 'Exam Fee', 'type' => 'INCOME', 'parent_id' => null],
            ['name' => 'Registration Fee', 'type' => 'INCOME', 'parent_id' => null],
            ['name' => 'Late Fine', 'type' => 'INCOME', 'parent_id' => null],
            ['name' => 'Other Income', 'type' => 'INCOME', 'parent_id' => null],
        ];

        foreach ($incomeCategories as $category) {
            DB::table('ledger_categories')->insert($category);
        }

        // Seed default expense categories
        $expenseCategories = [
            ['name' => 'Supplier Payment (Books)', 'type' => 'EXPENSE', 'parent_id' => null],
            ['name' => 'Supplier Payment (Stationery)', 'type' => 'EXPENSE', 'parent_id' => null],
            ['name' => 'Supplier Payment (Uniform)', 'type' => 'EXPENSE', 'parent_id' => null],
            ['name' => 'Salary Expense', 'type' => 'EXPENSE', 'parent_id' => null],
            ['name' => 'Rent Expense', 'type' => 'EXPENSE', 'parent_id' => null],
            ['name' => 'Electricity Expense', 'type' => 'EXPENSE', 'parent_id' => null],
            ['name' => 'Internet Expense', 'type' => 'EXPENSE', 'parent_id' => null],
            ['name' => 'Transport Expense', 'type' => 'EXPENSE', 'parent_id' => null],
            ['name' => 'Maintenance Expense', 'type' => 'EXPENSE', 'parent_id' => null],
            ['name' => 'Other Expense', 'type' => 'EXPENSE', 'parent_id' => null],
        ];

        foreach ($expenseCategories as $category) {
            DB::table('ledger_categories')->insert($category);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_categories');
    }
};
