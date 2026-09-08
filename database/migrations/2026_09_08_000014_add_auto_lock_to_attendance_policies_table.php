<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closes a register by itself once the month has settled.
 *
 * The lock exists and works, but somebody has to operate it by hand, so in
 * practice registers stay editable for ever — and a register that can still be
 * changed a year later is not a record of anything.
 *
 * Zero, the default, keeps the present behaviour: locked only when a person
 * locks it. A school that sets seven gets a week to correct a day, and after
 * that a named role has to reopen it deliberately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->unsignedSmallInteger('lock_after_days')
                ->default(0)
                ->after('absence_alert_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->dropColumn('lock_after_days');
        });
    }
};
