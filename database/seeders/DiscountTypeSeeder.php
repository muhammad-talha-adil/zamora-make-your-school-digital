<?php

namespace Database\Seeders;

use App\Models\Fee\DiscountType;
use Illuminate\Database\Seeder;

class DiscountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discountTypes = [
            [
                'name' => 'Sibling Discount',
                'code' => 'SIBLING',
                'value_type' => 'percent',
                'default_value' => 10,
                'description' => 'Discount for siblings studying in the same institution',
                'requires_approval' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Staff Concession',
                'code' => 'STAFF',
                'value_type' => 'percent',
                'default_value' => 50,
                'description' => 'Concession for children of school staff',
                'requires_approval' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Merit Scholarship',
                'code' => 'MERIT',
                'value_type' => 'percent',
                'default_value' => 100,
                'description' => 'Merit-based scholarship for top performers',
                'requires_approval' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Early Bird Discount',
                'code' => 'EARLY_BIRD',
                'value_type' => 'percent',
                'default_value' => 5,
                'description' => 'Discount for early fee payment',
                'requires_approval' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Annual Payment Discount',
                'code' => 'ANNUAL_PAY',
                'value_type' => 'percent',
                'default_value' => 10,
                'description' => 'Discount for paying full year fee in advance',
                'requires_approval' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Orphan Discount',
                'code' => 'ORPHAN',
                'value_type' => 'percent',
                'default_value' => 100,
                'description' => 'Full fee waiver for orphans',
                'requires_approval' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Need-based Financial Aid',
                'code' => 'NEED_BASED',
                'value_type' => 'percent',
                'default_value' => 50,
                'description' => 'Financial aid for needy families',
                'requires_approval' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Uniform Bundle Discount',
                'code' => 'UNIFORM_BUNDLE',
                'value_type' => 'fixed',
                'default_value' => 500,
                'description' => 'Fixed discount on uniform bundle purchase',
                'requires_approval' => false,
                'is_active' => true,
            ],
        ];

        foreach ($discountTypes as $discountType) {
            DiscountType::create($discountType);
        }

        $this->command->info('Discount types seeded successfully!');
        $this->command->info('Created '.count($discountTypes).' discount types.');
    }
}
