<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds a human-readable purchase_id column (e.g., PR-2026-0001)
     * and fixes total_amount to properly calculate from items.
     */
    public function up(): void
    {
        // Add purchase_id column
        Schema::table('inventory_purchases', function (Blueprint $table) {
            $table->string('purchase_id')->nullable()->unique()->after('id')->comment('Human-readable purchase ID (e.g., PR-2026-0001)');
        });

        // Generate purchase_id for existing records
        $purchases = DB::table('inventory_purchases')
            ->orderBy('id')
            ->get();

        $counter = 1;
        foreach ($purchases as $purchase) {
            $year = date('Y', strtotime($purchase->created_at));
            $purchaseId = 'PR-' . $year . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
            
            DB::table('inventory_purchases')
                ->where('id', $purchase->id)
                ->update(['purchase_id' => $purchaseId]);
            
            $counter++;
        }

        // Make purchase_id nullable -> required after filling existing data
        Schema::table('inventory_purchases', function (Blueprint $table) {
            $table->string('purchase_id')->nullable(false)->change();
        });

        // Fix total_amount for records where it's 0 or null
        $purchasesWithItems = DB::table('inventory_purchase_items')
            ->select('purchase_id', DB::raw('SUM(total) as calculated_total'))
            ->groupBy('purchase_id')
            ->get();

        foreach ($purchasesWithItems as $item) {
            DB::table('inventory_purchases')
                ->where('id', $item->purchase_id)
                ->update(['total_amount' => $item->calculated_total]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_purchases', function (Blueprint $table) {
            $table->dropColumn('purchase_id');
        });
    }
};
