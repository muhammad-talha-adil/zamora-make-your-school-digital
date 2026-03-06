<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campuses = Campus::all();

        if ($campuses->isEmpty()) {
            return;
        }

        foreach ($campuses as $campus) {
            $suppliers = Supplier::where('campus_id', $campus->id)->get();

            if ($suppliers->isEmpty()) {
                continue;
            }

            $numPurchases = rand(3, 5);

            for ($i = 0; $i < $numPurchases; $i++) {
                $supplier = $suppliers->random();
                $purchaseDate = now()->subDays(rand(1, 60));

                $purchase = Purchase::create([
                    'campus_id' => $campus->id,
                    'supplier_id' => $supplier->id,
                    'purchase_date' => $purchaseDate,
                    'total_amount' => 0,
                    'note' => 'Bulk purchase for campus inventory',
                ]);

                $items = InventoryItem::where('campus_id', $campus->id)
                    ->inRandomOrder()
                    ->limit(rand(2, 5))
                    ->get();

                $totalAmount = 0;

                foreach ($items as $item) {
                    $quantity = rand(5, 50);
                    $purchaseRate = rand(50, 500); // Random purchase rate
                    $saleRate = $purchaseRate * 1.5; // 50% markup
                    $total = $quantity * $purchaseRate;
                    $totalAmount += $total;

                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'inventory_item_id' => $item->id,
                        'quantity' => $quantity,
                        'purchase_rate' => $purchaseRate,
                        'sale_rate' => $saleRate,
                        'total' => $total,
                        'item_snapshot' => [
                            'item_name' => $item->name,
                            'purchase_rate' => $purchaseRate,
                            'captured_at' => now()->toISOString(),
                        ],
                    ]);
                }

                $purchase->total_amount = $totalAmount;
                $purchase->save();
            }
        }
    }
}
