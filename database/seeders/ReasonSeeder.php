<?php

namespace Database\Seeders;

use App\Models\Reason;
use Illuminate\Database\Seeder;

class ReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            'Damaged goods',
            'Expired items',
            'Wrong item delivered',
            'Quality issues',
            'Not as described',
            'Surplus inventory',
            'Customer return',
            'Overstock',
            'Seasonal return',
            'Defective product',
            'Packaging damage',
            'Shelf expiration',
            'Recalled product',
            'Incorrect quantity received',
            'Price discrepancy',
            'Late delivery',
            'Supplier cancellation',
            'Order error',
            'Storage issues',
            'Other',
        ];

        foreach ($reasons as $reason) {
            Reason::updateOrCreate(
                ['name' => $reason],
                [
                    'name' => $reason,
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
