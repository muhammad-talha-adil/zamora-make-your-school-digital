<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// This seeder is handled within PurchaseSeeder for data consistency
// Purchase items are created along with their parent purchase
// Dev/demo data only — not part of the default production seed list.
class PurchaseItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Purchase items are created in PurchaseSeeder to maintain
        // the relationship between purchase and its items
    }
}
