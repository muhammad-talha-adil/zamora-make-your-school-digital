<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a yearly charge be collected in instalments.
 *
 * A `yearly` charge drops its whole amount into the first month of the session,
 * so a Rs 24,000 annual charge lands on top of August's tuition and the parent
 * is handed a bill they cannot pay. Most schools here split it in two or three
 * — August and January is the common pair.
 *
 * Left at one instalment, nothing changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structure_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('instalment_count')->default(1)->after('frequency');

            // The months the instalments fall in, as month ids. Empty means the
            // session's own first month, which is where a yearly charge went
            // before instalments existed.
            $table->json('instalment_month_ids')->nullable()->after('instalment_count');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structure_items', function (Blueprint $table) {
            $table->dropColumn(['instalment_count', 'instalment_month_ids']);
        });
    }
};
