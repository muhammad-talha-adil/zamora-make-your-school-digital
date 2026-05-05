<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Reason;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseReturnSeeder extends Seeder
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

        $users = User::all();
        $reasons = Reason::all();

        foreach ($campuses as $campus) {
            $purchases = Purchase::where('campus_id', $campus->id)->get();

            if ($purchases->isEmpty()) {
                continue;
            }

            // Create returns for some purchases (not all)
            $numReturns = rand(1, 3);

            for ($i = 0; $i < $numReturns; $i++) {
                $purchase = $purchases->random();
                $supplier = Supplier::find($purchase->supplier_id);
                $returnDate = $purchase->purchase_date->addDays(rand(1, 30));

                $purchaseReturn = PurchaseReturn::create([
                    'campus_id' => $campus->id,
                    'purchase_id' => $purchase->id,
                    'supplier_id' => $purchase->supplier_id,
                    'user_id' => $users->isNotEmpty() ? $users->random()->id : null,
                    'return_date' => $returnDate,
                    'total_amount' => 0,
                    'note' => 'Purchase return for defective/damaged items',
                    'return_number' => 'RET-'.$campus->id.'-'.str_pad(($i + 1), 4, '0', STR_PAD_LEFT),
                ]);

                // Get some items from the original purchase to return
                $purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)
                    ->inRandomOrder()
                    ->limit(rand(1, 2))
                    ->get();

                $totalAmount = 0;

                foreach ($purchaseItems as $purchaseItem) {
                    $returnQuantity = rand(1, min(3, $purchaseItem->quantity));
                    $unitPrice = $purchaseItem->purchase_rate;
                    $total = $returnQuantity * $unitPrice;
                    $totalAmount += $total;

                    $reasonId = $reasons->isNotEmpty() ? $reasons->random()->id : null;

                    PurchaseReturnItem::create([
                        'purchase_return_id' => $purchaseReturn->id,
                        'inventory_item_id' => $purchaseItem->inventory_item_id,
                        'purchase_item_id' => $purchaseItem->id,
                        'reason_id' => $reasonId,
                        'quantity' => $returnQuantity,
                        'unit_price' => $unitPrice,
                        'total' => $total,
                        'item_snapshot' => [
                            'item_name' => $purchaseItem->inventoryItem?->name ?? 'Unknown Item',
                            'purchase_rate' => $unitPrice,
                            'captured_at' => now()->toISOString(),
                        ],
                        'reason' => $reasonId ? null : 'Defective items',
                    ]);
                }

                $purchaseReturn->total_amount = $totalAmount;
                $purchaseReturn->save();
            }
        }
    }
}
