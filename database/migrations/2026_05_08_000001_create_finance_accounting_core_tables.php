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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->enum('account_type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_type', 'is_active']);
            $table->index('parent_id');
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_no', 50)->unique();
            $table->date('entry_date');
            $table->string('source_module', 50)->nullable();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('posted');
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversal_of')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entry_date', 'status']);
            $table->index(['source_module', 'source_type', 'source_id'], 'idx_journal_source');
            $table->index('campus_id');
            $table->index('student_id');
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->string('memo', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['journal_entry_id', 'account_id'], 'idx_journal_line_entry_account');
            $table->index('campus_id');
            $table->index('student_id');
        });

        Schema::create('student_account_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_enrollment_record_id')->nullable()->constrained('student_enrollment_records')->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('source_module', 50);
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('source_item_id')->nullable();
            $table->string('charge_category', 100)->default('other');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('charge_date');
            $table->foreignId('billing_period_month_id')->nullable()->constrained('months')->nullOnDelete();
            $table->unsignedSmallInteger('billing_period_year')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->enum('status', ['open', 'partial', 'paid', 'cancelled', 'written_off', 'adjusted'])->default('open');
            $table->enum('billing_status', ['unbilled', 'billed', 'partially_billed'])->default('unbilled');
            $table->foreignId('voucher_id')->nullable()->constrained('fee_vouchers')->nullOnDelete();
            $table->foreignId('voucher_item_id')->nullable()->constrained('fee_voucher_items')->nullOnDelete();
            $table->boolean('is_recurring')->default(false);
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'status'], 'idx_student_charge_student_status');
            $table->index(['student_id', 'billing_status'], 'idx_student_charge_student_billing');
            $table->index(['source_module', 'source_type', 'source_id'], 'idx_student_charge_source');
            $table->index(['campus_id', 'session_id', 'class_id', 'section_id'], 'idx_student_charge_scope');
            $table->index(['billing_period_month_id', 'billing_period_year'], 'idx_student_charge_period');
            $table->index('voucher_id');
            $table->index('voucher_item_id');
        });

        Schema::create('student_account_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_account_charge_id')->nullable()->constrained('student_account_charges')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('fee_vouchers')->nullOnDelete();
            $table->foreignId('voucher_item_id')->nullable()->constrained('fee_voucher_items')->nullOnDelete();
            $table->string('adjustment_type', 100);
            $table->string('source_module', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('reason', 255)->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'adjustment_type'], 'idx_student_adj_student_type');
            $table->index(['source_module', 'source_id'], 'idx_student_adj_source');
            $table->index('student_account_charge_id');
            $table->index('voucher_id');
        });

        $now = now();
        DB::table('chart_of_accounts')->insert([
            [
                'code' => '1000',
                'name' => 'Cash in Hand',
                'account_type' => 'asset',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Physical cash held by the institution',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '1010',
                'name' => 'Bank Account',
                'account_type' => 'asset',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Institution bank balances',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '1100',
                'name' => 'Student Receivables',
                'account_type' => 'asset',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Amounts due from students across all billable modules',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '1200',
                'name' => 'Inventory Asset',
                'account_type' => 'asset',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Inventory value on hand',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '2000',
                'name' => 'Supplier Payables',
                'account_type' => 'liability',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Outstanding supplier balances',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '2100',
                'name' => 'Salary Payable',
                'account_type' => 'liability',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Accrued salary obligations',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '4000',
                'name' => 'Fee Income',
                'account_type' => 'income',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Core academic fee income',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '4010',
                'name' => 'Fine Income',
                'account_type' => 'income',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Late fee and other penalties',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '4020',
                'name' => 'Inventory Sales Income',
                'account_type' => 'income',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Student-facing inventory sales income',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '4030',
                'name' => 'Transport Income',
                'account_type' => 'income',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Student transport-related income',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '5000',
                'name' => 'Salary Expense',
                'account_type' => 'expense',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Staff payroll expense',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '5010',
                'name' => 'Transport Expense',
                'account_type' => 'expense',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Fuel, route, and vehicle operating costs',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '5020',
                'name' => 'Maintenance Expense',
                'account_type' => 'expense',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Repairs and maintenance costs',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '5030',
                'name' => 'Cost of Goods Sold',
                'account_type' => 'expense',
                'parent_id' => null,
                'is_active' => true,
                'is_system' => true,
                'description' => 'Cost recognized when inventory is sold to students',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_account_adjustments');
        Schema::dropIfExists('student_account_charges');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};
