<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add new fields for recurring holidays and attendance override.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('holidays', 'recurrence_type')) {
            Schema::table('holidays', function (Blueprint $table) {
                // Recurring holiday support
                $table->enum('recurrence_type', ['none', 'yearly', 'monthly', 'weekly'])
                    ->default('none')
                    ->after('description');
                $table->date('recurrence_end_date')
                    ->nullable()
                    ->after('recurrence_type');

                // Allow attendance on holiday
                $table->boolean('is_attendance_allowed')
                    ->default(false)
                    ->after('recurrence_end_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('holidays', 'recurrence_type')) {
            Schema::table('holidays', function (Blueprint $table) {
                $table->dropColumn([
                    'recurrence_type',
                    'recurrence_end_date',
                    'is_attendance_allowed',
                ]);
            });
        }
    }
};
