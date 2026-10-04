<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_advances')) {
            Schema::create('staff_advances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->date('disbursed_date');
                $table->decimal('monthly_deduction_amount', 12, 2)->nullable();
                $table->decimal('balance_remaining', 12, 2);
                $table->string('status')->default('active');
                $table->text('notes')->nullable();
                $table->foreignId('given_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('settled_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('staff_advance_deductions')) {
            Schema::create('staff_advance_deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_advance_id')->constrained('staff_advances')->cascadeOnDelete();
                $table->foreignId('payroll_run_item_id')->nullable()->constrained('payroll_run_items')->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->date('deducted_on');
                $table->string('type')->default('auto');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('payroll_run_items') && ! Schema::hasColumn('payroll_run_items', 'amount_paid')) {
            Schema::table('payroll_run_items', function (Blueprint $table) {
                $table->decimal('amount_paid', 12, 2)->default(0)->after('net_salary');
                $table->decimal('advance_deduction_amount', 12, 2)->default(0)->after('amount_paid');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_advance_deductions');
        Schema::dropIfExists('staff_advances');

        if (Schema::hasTable('payroll_run_items') && Schema::hasColumn('payroll_run_items', 'amount_paid')) {
            Schema::table('payroll_run_items', function (Blueprint $table) {
                $table->dropColumn(['amount_paid', 'advance_deduction_amount']);
            });
        }
    }
};
