<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\InventoryAdjustment;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryAdjustmentSeeder extends Seeder
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

        $adjustmentTypes = [
            InventoryAdjustment::TYPE_ADD,
            InventoryAdjustment::TYPE_SUBTRACT,
            InventoryAdjustment::TYPE_SET,
        ];

        $reasons = [
            'Stock count correction',
            'Damaged items removed',
            'New shipment received',
            'Inventory audit adjustment',
            'Items returned to stock',
            'Lost items written off',
            'Found items added',
            'Transfer between locations',
        ];

        foreach ($campuses as $campus) {
            $items = InventoryItem::where('campus_id', $campus->id)->get();

            if ($items->isEmpty()) {
                continue;
            }

            $numAdjustments = rand(3, 6);

            for ($i = 0; $i < $numAdjustments; $i++) {
                $item = $items->random();
                $type = $adjustmentTypes[array_rand($adjustmentTypes)];
                $quantity = rand(1, 20);
                $previousQuantity = rand(50, 200);

                // Calculate new quantity based on type
                $newQuantity = match ($type) {
                    InventoryAdjustment::TYPE_ADD => $previousQuantity + $quantity,
                    InventoryAdjustment::TYPE_SUBTRACT => max(0, $previousQuantity - $quantity),
                    InventoryAdjustment::TYPE_SET => $quantity,
                    default => $previousQuantity,
                };

                $userId = $users->isNotEmpty() ? $users->random()->id : null;

                InventoryAdjustment::create([
                    'campus_id' => $campus->id,
                    'inventory_item_id' => $item->id,
                    'user_id' => $userId,
                    'type' => $type,
                    'quantity' => $quantity,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'reason' => $reasons[array_rand($reasons)],
                    'reference_number' => 'ADJ-'.date('Y').'-'.str_pad(($i + 1), 4, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }
}
