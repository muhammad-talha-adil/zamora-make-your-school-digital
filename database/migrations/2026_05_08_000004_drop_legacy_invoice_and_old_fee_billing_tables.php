<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('student_fees');
        Schema::dropIfExists('fee_types');
    }

    /**
     * Not reversible: these tables are gone for good. Their schema already
     * lives (once, forward-only) in their original create migrations
     * (2026_02_05_000008/9/10/11/12) - rebuilding them here would keep a
     * second Schema::create('<table>') around for every one of them just to
     * support a rollback nobody runs on a 5-table legacy-billing cleanup.
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};
