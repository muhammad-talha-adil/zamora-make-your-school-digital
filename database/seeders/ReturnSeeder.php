<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\ReturnModel;
use App\Models\StudentInventory;
use App\Models\StudentInventoryItem;
use Illuminate\Database\Seeder;

class ReturnSeeder extends Seeder
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
            // Get student inventories with assigned status
            $assignedItems = StudentInventory::where('campus_id', $campus->id)
                ->where('status', 'assigned')
                ->orWhere('status', 'partial_return')
                ->get();

            // Create returns for some items (not all)
            foreach ($assignedItems as $studentInventory) {
                if (rand(0, 1)) { // 50% chance of return
                    // Get the first item from the inventory
                    $firstItem = $studentInventory->items()->first();
                    
                    if (!$firstItem) {
                        continue;
                    }

                    $returnQuantity = rand(1, $firstItem->quantity - $firstItem->returned_quantity);
                    
                    if ($returnQuantity > 0) {
                        // Create the return record
                        $return = ReturnModel::create([
                            'campus_id' => $campus->id,
                            'student_inventory_id' => $studentInventory->id,
                            'quantity' => $returnQuantity,
                            'return_date' => now()->subDays(rand(1, 15)),
                            'note' => 'Student returned items',
                            'item_name_snapshot' => $firstItem->item_name_snapshot,
                            'description_snapshot' => $firstItem->description_snapshot,
                            'unit_price_snapshot' => $firstItem->unit_price_snapshot,
                            'discount_snapshot' => [
                                'has_discount' => $firstItem->hasDiscount(),
                                'discount_amount' => $firstItem->discount_amount,
                                'discount_percentage' => $firstItem->discount_percentage,
                            ],
                            'item_snapshot' => [
                                'item_name' => $firstItem->item_name_snapshot,
                                'description' => $firstItem->description_snapshot,
                                'unit_price' => $firstItem->unit_price_snapshot,
                                'discount' => [
                                    'has_discount' => $firstItem->hasDiscount(),
                                    'discount_amount' => $firstItem->discount_amount,
                                    'discount_percentage' => $firstItem->discount_percentage,
                                ],
                                'captured_at' => now()->toISOString(),
                            ],
                        ]);

                        // Update the returned quantity in the student inventory item
                        $firstItem->returned_quantity = $firstItem->returned_quantity + $returnQuantity;
                        
                        // Update status based on return quantity
                        if ($firstItem->returned_quantity >= $firstItem->quantity) {
                            $firstItem->status = 'returned';
                            $studentInventory->status = 'returned';
                        } else {
                            $firstItem->status = 'partial_return';
                            $studentInventory->status = 'partial_return';
                        }
                        
                        $firstItem->save();
                        $studentInventory->save();
                    }
                }
            }
        }
    }
}
