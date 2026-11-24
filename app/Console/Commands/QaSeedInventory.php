<?php

namespace App\Console\Commands;

use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryType;
use App\Models\InventoryValuation;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Local QA helper: one inventory type, supplier, item and a purchase that puts
 * stock on the shelf, so a student can be charged from the Inventory screens.
 *
 * Safe to run any number of times - everything is looked up by name first, and
 * the purchase is only made when the item has no stock yet. Refuses to run
 * outside the local environment.
 */
class QaSeedInventory extends Command
{
    protected $signature = 'qa:seed-inventory {--campus= : Campus id (defaults to the first campus)}';

    protected $description = 'Create a QA inventory type, supplier, item and stocked purchase (local only, idempotent)';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('This command only runs in the local environment.');

            return self::FAILURE;
        }

        $campus = $this->option('campus') ? Campus::find($this->option('campus')) : Campus::query()->orderBy('id')->first();

        if (! $campus) {
            $this->error('No campus found.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($campus) {
            $type = InventoryType::withoutGlobalScopes()->firstOrCreate(
                ['name' => 'QA Uniform', 'campus_id' => $campus->id],
                ['is_active' => true]
            );

            $supplier = Supplier::firstOrCreate(
                ['name' => 'QA Supplier', 'campus_id' => $campus->id],
                ['contact_person' => 'QA Contact', 'phone' => '0300-1234567', 'is_active' => true, 'opening_balance' => 0]
            );

            $item = InventoryItem::withoutGlobalScopes()->firstOrCreate(
                ['name' => 'QA School Shirt', 'campus_id' => $campus->id, 'inventory_type_id' => $type->id],
                ['description' => 'QA test item', 'is_active' => true]
            );

            $stock = InventoryStock::firstOrCreate(
                ['campus_id' => $campus->id, 'inventory_item_id' => $item->id],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );

            if ($stock->quantity > 0) {
                return;
            }

            $purchase = Purchase::create([
                'campus_id' => $campus->id,
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->toDateString(),
                'total_amount' => 50000,
                'note' => 'QA seed purchase',
            ]);

            $purchaseItem = PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'inventory_item_id' => $item->id,
                'quantity' => 100,
                'purchase_rate' => 500,
                'sale_rate' => 800,
                'total' => 50000,
                'item_snapshot' => ['item_name' => $item->name, 'purchase_rate' => 500, 'sale_rate' => 800],
            ]);

            InventoryValuation::createAfterPurchase($purchaseItem);

            $stock->update(['quantity' => 100]);
        });

        $this->info("QA inventory ready for {$campus->name}: type 'QA Uniform', supplier 'QA Supplier', item 'QA School Shirt' (100 in stock, sale rate 800).");

        return self::SUCCESS;
    }
}
