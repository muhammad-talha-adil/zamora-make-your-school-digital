<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Charging for repeated late arrival — where a school does that.
 *
 * Some schools here charge for it and most do not, so this is off by default
 * and stays off until a campus turns it on. That is the whole point of it being
 * a policy: a fine nobody asked for, appearing on a parent's voucher, is worse
 * than no feature at all.
 *
 * The grace count matters as much as the amount. Nobody is fined for being late
 * once; the charge is for a habit, so a school sets how many are forgiven each
 * month before any are charged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->boolean('late_fine_enabled')->default(false)->after('lock_after_days');

            // Late arrivals forgiven each month before any are charged.
            $table->unsignedSmallInteger('late_fine_grace_count')->default(3)->after('late_fine_enabled');

            // Charged for each late arrival past the grace count.
            $table->decimal('late_fine_amount', 10, 2)->default(0)->after('late_fine_grace_count');

            // The most that may be charged in one month, so a difficult month
            // does not produce a bill out of all proportion to it.
            $table->decimal('late_fine_monthly_cap', 10, 2)->nullable()->after('late_fine_amount');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->dropColumn([
                'late_fine_enabled',
                'late_fine_grace_count',
                'late_fine_amount',
                'late_fine_monthly_cap',
            ]);
        });
    }
};
