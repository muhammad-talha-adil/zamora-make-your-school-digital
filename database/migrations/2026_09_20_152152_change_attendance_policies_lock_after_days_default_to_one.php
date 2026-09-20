<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner asked that a register close itself the day after it was taken,
 * not stay editable forever until someone locks it by hand (#106).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->unsignedSmallInteger('lock_after_days')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->unsignedSmallInteger('lock_after_days')->default(0)->change();
        });
    }
};
