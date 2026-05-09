<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_departments')) {
            Schema::create('staff_departments', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('staff_designations')) {
            Schema::create('staff_designations', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('staff_profiles')) {
            Schema::create('staff_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('employee_no')->unique();
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('staff_departments')->nullOnDelete();
                $table->foreignId('designation_id')->nullable()->constrained('staff_designations')->nullOnDelete();
                $table->string('employment_type')->default('permanent');
                $table->date('hire_date')->nullable();
                $table->date('confirmation_date')->nullable();
                $table->decimal('basic_salary', 12, 2)->default(0);
                $table->decimal('allowance_amount', 12, 2)->default(0);
                $table->decimal('deduction_amount', 12, 2)->default(0);
                $table->string('payment_method')->default('bank');
                $table->string('bank_name')->nullable();
                $table->string('account_no')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('staff_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('staff_profiles', 'employee_no')) {
                    $table->string('employee_no')->nullable()->unique()->after('user_id');
                }
                if (! Schema::hasColumn('staff_profiles', 'campus_id')) {
                    $table->foreignId('campus_id')->nullable()->after('employee_no')->constrained('campuses')->nullOnDelete();
                }
                if (! Schema::hasColumn('staff_profiles', 'department_id')) {
                    $table->foreignId('department_id')->nullable()->after('campus_id')->constrained('staff_departments')->nullOnDelete();
                }
                if (! Schema::hasColumn('staff_profiles', 'designation_id')) {
                    $table->foreignId('designation_id')->nullable()->after('department_id')->constrained('staff_designations')->nullOnDelete();
                }
                if (! Schema::hasColumn('staff_profiles', 'employment_type')) {
                    $table->string('employment_type')->default('permanent')->after('designation_id');
                }
                if (! Schema::hasColumn('staff_profiles', 'hire_date')) {
                    $table->date('hire_date')->nullable()->after('employment_type');
                }
                if (! Schema::hasColumn('staff_profiles', 'confirmation_date')) {
                    $table->date('confirmation_date')->nullable()->after('hire_date');
                }
                if (! Schema::hasColumn('staff_profiles', 'basic_salary')) {
                    $table->decimal('basic_salary', 12, 2)->default(0)->after('confirmation_date');
                }
                if (! Schema::hasColumn('staff_profiles', 'allowance_amount')) {
                    $table->decimal('allowance_amount', 12, 2)->default(0)->after('basic_salary');
                }
                if (! Schema::hasColumn('staff_profiles', 'deduction_amount')) {
                    $table->decimal('deduction_amount', 12, 2)->default(0)->after('allowance_amount');
                }
                if (! Schema::hasColumn('staff_profiles', 'payment_method')) {
                    $table->string('payment_method')->default('bank')->after('deduction_amount');
                }
                if (! Schema::hasColumn('staff_profiles', 'bank_name')) {
                    $table->string('bank_name')->nullable()->after('payment_method');
                }
                if (! Schema::hasColumn('staff_profiles', 'account_no')) {
                    $table->string('account_no')->nullable()->after('bank_name');
                }
                if (! Schema::hasColumn('staff_profiles', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('account_no');
                }
                if (! Schema::hasColumn('staff_profiles', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        if (! Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
                $table->foreignId('payroll_month_id')->constrained('months');
                $table->unsignedSmallInteger('payroll_year');
                $table->string('status')->default('draft');
                $table->decimal('total_gross', 14, 2)->default(0);
                $table->decimal('total_deductions', 14, 2)->default(0);
                $table->decimal('total_net', 14, 2)->default(0);
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['campus_id', 'payroll_month_id', 'payroll_year'], 'payroll_runs_period_unique');
            });
        }

        if (! Schema::hasTable('payroll_run_items')) {
            Schema::create('payroll_run_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
                $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
                $table->decimal('gross_salary', 12, 2)->default(0);
                $table->decimal('allowance_amount', 12, 2)->default(0);
                $table->decimal('deduction_amount', 12, 2)->default(0);
                $table->decimal('net_salary', 12, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('payment_method')->nullable();
                $table->string('reference_no')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['payroll_run_id', 'staff_profile_id'], 'payroll_run_staff_unique');
            });
        }

        if (! Schema::hasTable('transport_vehicles')) {
            Schema::create('transport_vehicles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
                $table->string('vehicle_no')->unique();
                $table->string('vehicle_type')->default('van');
                $table->unsignedInteger('capacity')->default(0);
                $table->string('driver_name')->nullable();
                $table->string('attendant_name')->nullable();
                $table->string('status')->default('active');
                $table->date('purchase_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('transport_routes')) {
            Schema::create('transport_routes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
                $table->foreignId('transport_vehicle_id')->nullable()->constrained('transport_vehicles')->nullOnDelete();
                $table->string('name');
                $table->decimal('monthly_fee', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('transport_stops')) {
            Schema::create('transport_stops', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
                $table->string('name');
                $table->time('pickup_time')->nullable();
                $table->time('drop_time')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('transport_route_stops')) {
            Schema::create('transport_route_stops', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transport_route_id')->constrained('transport_routes')->cascadeOnDelete();
                $table->foreignId('transport_stop_id')->constrained('transport_stops')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(1);
                $table->timestamps();
                $table->unique(['transport_route_id', 'transport_stop_id'], 'route_stop_unique');
            });
        }

        if (! Schema::hasTable('transport_student_assignments')) {
            Schema::create('transport_student_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('student_enrollment_record_id')->nullable();
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
                $table->foreignId('transport_route_id')->constrained('transport_routes')->cascadeOnDelete();
                $table->foreignId('transport_stop_id')->nullable()->constrained('transport_stops')->nullOnDelete();
                $table->decimal('monthly_fee', 12, 2)->default(0);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->string('status')->default('active');
                $table->boolean('generate_dues')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->foreign('student_enrollment_record_id', 'tsa_enrollment_fk')
                    ->references('id')
                    ->on('student_enrollment_records')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('transport_vehicle_expenses')) {
            Schema::create('transport_vehicle_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
                $table->foreignId('transport_vehicle_id')->nullable()->constrained('transport_vehicles')->nullOnDelete();
                $table->string('expense_type');
                $table->date('expense_date');
                $table->decimal('amount', 12, 2);
                $table->string('payment_method')->default('cash');
                $table->string('reference_no')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_vehicle_expenses');
        Schema::dropIfExists('transport_student_assignments');
        Schema::dropIfExists('transport_route_stops');
        Schema::dropIfExists('transport_stops');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('transport_vehicles');
        Schema::dropIfExists('payroll_run_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('staff_designations');
        Schema::dropIfExists('staff_departments');
    }
};
