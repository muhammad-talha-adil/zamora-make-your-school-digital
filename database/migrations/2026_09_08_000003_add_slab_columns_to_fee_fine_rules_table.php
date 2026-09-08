<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives late fines the shape schools here actually charge.
 *
 * The rules could only say one thing at a time: a flat fine, or a daily one, or
 * a percentage. What is actually run is a slab — due date, then a few days'
 * grace, then a fixed fine, then so much per day after that, and the whole
 * thing capped so a forgotten voucher does not grow without limit.
 *
 * The existing single-value types keep working; `fine_value` is untouched. The
 * cap applies to every type, because an uncapped per-day fine was the sharpest
 * edge of the old design.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_fine_rules', function (Blueprint $table) {
            // Charged once, the day the grace period runs out.
            $table->decimal('initial_amount', 12, 2)->default(0)->after('fine_value');

            // Charged for each further day late.
            $table->decimal('daily_amount', 12, 2)->default(0)->after('initial_amount');

            // The most this rule may ever charge on one voucher. Null is no cap.
            $table->decimal('max_fine_amount', 12, 2)->nullable()->after('daily_amount');
        });

        // `slab` is a fourth kind of rule, and a database enum has to be
        // rewritten to gain a value. The column becomes a plain string, which
        // the `FineType` cast already validates on the way in and out — the
        // same shape every other status column in the fee module uses.
        Schema::table('fee_fine_rules', function (Blueprint $table) {
            $table->string('fine_type', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('fee_fine_rules', function (Blueprint $table) {
            $table->dropColumn(['initial_amount', 'daily_amount', 'max_fine_amount']);
        });
    }
};
