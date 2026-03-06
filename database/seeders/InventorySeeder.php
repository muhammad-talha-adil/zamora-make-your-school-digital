<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Order matters due to foreign key dependencies!
     */
    public function run(): void
    {
        $this->call([
            // 1. Inventory Types (base reference)
            InventoryTypeSeeder::class,
            
            // 2. Inventory Items (depends on types)
            InventoryItemSeeder::class,
            
            // 3. Suppliers (independent)
            SupplierSeeder::class,
            
            // 4. Inventory Stock (depends on items)
            InventoryStockSeeder::class,
            
            // 5. Purchases (depends on suppliers and items)
            PurchaseSeeder::class,
            
            // 6. Purchase Returns (depends on purchases and suppliers)
            PurchaseReturnSeeder::class,
            
            // 7. Inventory Adjustments (depends on items)
            InventoryAdjustmentSeeder::class,
            
            // 8. Student Inventory (depends on items and students)
            StudentInventorySeeder::class,
            
            // 9. Returns (depends on student inventory)
            ReturnSeeder::class,
        ]);
    }
}
