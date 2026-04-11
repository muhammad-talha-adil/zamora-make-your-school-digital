<?php

namespace Database\Seeders;

use App\Models\Fee\FeeHead;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeeHeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $feeHeads = [
            // Monthly Fee Heads
            [
                'name' => 'Tuition Fee',
                'code' => 'MONTHLY_TUITION',
                'category' => 'monthly',
                'is_recurring' => true,
                'default_frequency' => 'monthly',
                'is_optional' => false,
                'sort_order' => 1,
                'is_active' => true,
                'description' => 'Monthly tuition fee charged to all students',
            ],
            [
                'name' => 'Computer Lab Fee',
                'code' => 'COMPUTER_LAB',
                'category' => 'monthly',
                'is_recurring' => true,
                'default_frequency' => 'monthly',
                'is_optional' => true,
                'sort_order' => 2,
                'is_active' => true,
                'description' => 'Monthly computer lab usage fee',
            ],
            [
                'name' => 'Science Lab Fee',
                'code' => 'SCIENCE_LAB',
                'category' => 'monthly',
                'is_recurring' => true,
                'default_frequency' => 'monthly',
                'is_optional' => true,
                'sort_order' => 3,
                'is_active' => true,
                'description' => 'Monthly science lab usage fee',
            ],
            [
                'name' => 'Library Fee',
                'code' => 'LIBRARY',
                'category' => 'monthly',
                'is_recurring' => true,
                'default_frequency' => 'monthly',
                'is_optional' => true,
                'sort_order' => 4,
                'is_active' => true,
                'description' => 'Monthly library membership fee',
            ],
            [
                'name' => 'Sports Fee',
                'code' => 'SPORTS',
                'category' => 'monthly',
                'is_recurring' => true,
                'default_frequency' => 'monthly',
                'is_optional' => true,
                'sort_order' => 5,
                'is_active' => true,
                'description' => 'Monthly sports and physical education fee',
            ],
            [
                'name' => 'Exam Fee',
                'code' => 'EXAM_FEE',
                'category' => 'monthly',
                'is_recurring' => true,
                'default_frequency' => 'monthly',
                'is_optional' => false,
                'sort_order' => 6,
                'is_active' => true,
                'description' => 'Monthly exam fee charged to all students',
            ],

            // Annual Fee Heads
            [
                'name' => 'Annual Charges',
                'code' => 'ANNUAL',
                'category' => 'annual',
                'is_recurring' => true,
                'default_frequency' => 'yearly',
                'is_optional' => false,
                'sort_order' => 10,
                'is_active' => true,
                'description' => 'Annual school charges',
            ],
            [
                'name' => 'Registration Fee',
                'code' => 'REGISTRATION',
                'category' => 'annual',
                'is_recurring' => true,
                'default_frequency' => 'yearly',
                'is_optional' => false,
                'sort_order' => 11,
                'is_active' => true,
                'description' => 'Annual registration/enrollment fee',
            ],
            [
                'name' => 'Books & Uniform',
                'code' => 'BOOKS_UNIFORM',
                'category' => 'annual',
                'is_recurring' => false,
                'default_frequency' => 'yearly',
                'is_optional' => true,
                'sort_order' => 12,
                'is_active' => true,
                'description' => 'Annual books and uniform charges',
            ],

            // One-Time Fee Heads
            [
                'name' => 'Admission Fee',
                'code' => 'ADMISSION',
                'category' => 'one_time',
                'is_recurring' => false,
                'default_frequency' => 'once',
                'is_optional' => false,
                'sort_order' => 20,
                'is_active' => true,
                'description' => 'One-time admission fee for new students',
            ],
            [
                'name' => 'Enrollment Fee',
                'code' => 'ENROLLMENT',
                'category' => 'one_time',
                'is_recurring' => false,
                'default_frequency' => 'once',
                'is_optional' => false,
                'sort_order' => 21,
                'is_active' => true,
                'description' => 'One-time enrollment fee',
            ],

            // Transport Fee
            [
                'name' => 'Transport Fee',
                'code' => 'TRANSPORT',
                'category' => 'transport',
                'is_recurring' => true,
                'default_frequency' => 'monthly',
                'is_optional' => true,
                'sort_order' => 30,
                'is_active' => true,
                'description' => 'Monthly transport fee for students using school bus',
            ],

            // Fine/Misc
            [
                'name' => 'Late Payment Fine',
                'code' => 'LATE_FINE',
                'category' => 'fine',
                'is_recurring' => false,
                'default_frequency' => 'once',
                'is_optional' => true,
                'sort_order' => 40,
                'is_active' => true,
                'description' => 'Fine for late fee payment',
            ],
            [
                'name' => 'Miscellaneous',
                'code' => 'MISC',
                'category' => 'misc',
                'is_recurring' => false,
                'default_frequency' => 'once',
                'is_optional' => true,
                'sort_order' => 50,
                'is_active' => true,
                'description' => 'Miscellaneous charges',
            ],
        ];

        foreach ($feeHeads as $feeHead) {
            FeeHead::firstOrCreate(
                ['code' => $feeHead['code']],
                $feeHead
            );
        }

        echo "Fee heads seeded successfully!\n";
    }
}
