<?php

namespace Database\Seeders;

use App\Enums\Fee\ValueType;
use App\Models\Campus;
use App\Models\Fee\FeeSiblingDiscountRule;
use Illuminate\Database\Seeder;

class FeeSiblingDiscountRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * "Ten percent off the second child, twenty off the third" — a school-wide
     * rule (no session, every fee head) for the campuses that already exist.
     */
    public function run(): void
    {
        $campuses = Campus::all();

        if ($campuses->isEmpty()) {
            $this->command->warn('No campuses found. Please seed campuses first.');

            return;
        }

        $rules = [
            ['child_rank' => 2, 'value_type' => ValueType::PERCENT->value, 'value' => 10.00],
            ['child_rank' => 3, 'value_type' => ValueType::PERCENT->value, 'value' => 20.00],
        ];

        $count = 0;

        foreach ($campuses as $campus) {
            foreach ($rules as $rule) {
                FeeSiblingDiscountRule::updateOrCreate(
                    [
                        'campus_id' => $campus->id,
                        'session_id' => null,
                        'child_rank' => $rule['child_rank'],
                        'fee_head_id' => null,
                    ],
                    [
                        'value_type' => $rule['value_type'],
                        'value' => $rule['value'],
                        'is_active' => true,
                    ]
                );

                $count++;
            }
        }

        $this->command->info('Fee sibling discount rules seeded successfully!');
        $this->command->info('Created '.$count.' sibling discount rules.');
    }
}
