<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use Illuminate\Database\Seeder;

class InventoryStockSeeder extends Seeder
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
            $items = InventoryItem::where('campus_id', $campus->id)->get();
            
            foreach ($items as $item) {
                // Create stock with random quantities for variety
                $quantity = rand(10, 500);
                $reserved = rand(0, 20);
                
                InventoryStock::firstOrCreate(
                    [
                        'campus_id' => $campus->id,
                        'inventory_item_id' => $item->id,
                    ],
                    [
                        'quantity' => $quantity,
                        'reserved_quantity' => $reserved,
                        'low_stock_threshold' => 10,
                    ]
                );
            }
        }
    }
}
