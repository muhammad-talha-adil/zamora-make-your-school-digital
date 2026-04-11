<?php

namespace Database\Seeders;

use App\Models\Fee\FeeFineRule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FineRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get campus and session IDs for seeding
        $campuses = \App\Models\Campus::all();
        $sessions = \App\Models\Session::all();
        $classes = \App\Models\SchoolClass::all();
        $feeHeads = \App\Models\Fee\FeeHead::all();

        if ($campuses->isEmpty() || $sessions->isEmpty()) {
            $this->command->warn('No campuses or sessions found. Please seed campuses and sessions first.');
            return;
        }

        $currentYear = now()->year;
        $fineRules = [
            // Global rule for all campuses - Fixed per day
            [
                'name' => 'Late Payment Fine - Standard',
                'campus_id' => $campuses->first()->id,
                'session_id' => $sessions->first()->id,
                'class_id' => null,
                'section_id' => null,
                'fee_head_id' => null,
                'grace_days' => 5,
                'fine_type' => 'fixed_per_day',
                'fine_value' => 50.00,
                'effective_from' => $currentYear . '-01-01',
                'effective_to' => $currentYear . '-12-31',
                'is_active' => true,
            ],
            // Global rule - Percentage based
            [
                'name' => 'Late Payment Fine - Percentage',
                'campus_id' => $campuses->first()->id,
                'session_id' => $sessions->first()->id,
                'class_id' => null,
                'section_id' => null,
                'fee_head_id' => null,
                'grace_days' => 10,
                'fine_type' => 'percent',
                'fine_value' => 2.00,
                'effective_from' => $currentYear . '-01-01',
                'effective_to' => $currentYear . '-12-31',
                'is_active' => true,
            ],
            // Fixed one-time fine
            [
                'name' => 'Late Payment Fine - Flat Rate',
                'campus_id' => $campuses->first()->id,
                'session_id' => $sessions->first()->id,
                'class_id' => null,
                'section_id' => null,
                'fee_head_id' => null,
                'grace_days' => 3,
                'fine_type' => 'fixed_once',
                'fine_value' => 500.00,
                'effective_from' => $currentYear . '-01-01',
                'effective_to' => $currentYear . '-12-31',
                'is_active' => true,
            ],
        ];

        // If there are more campuses, add campus-specific rules
        if ($campuses->count() > 1) {
            $secondCampus = $campuses->get(1);
            $fineRules[] = [
                'name' => 'Secondary Campus Late Fee',
                'campus_id' => $secondCampus->id,
                'session_id' => $sessions->first()->id,
                'class_id' => null,
                'section_id' => null,
                'fee_head_id' => null,
                'grace_days' => 7,
                'fine_type' => 'fixed_per_day',
                'fine_value' => 25.00,
                'effective_from' => $currentYear . '-01-01',
                'effective_to' => $currentYear . '-12-31',
                'is_active' => true,
            ];
        }

        // Add class-specific rules if classes exist
        if ($classes->isNotEmpty()) {
            $fineRules[] = [
                'name' => 'Pre-Nursery Special Fine',
                'campus_id' => $campuses->first()->id,
                'session_id' => $sessions->first()->id,
                'class_id' => $classes->first()->id,
                'section_id' => null,
                'fee_head_id' => null,
                'grace_days' => 3,
                'fine_type' => 'fixed_per_day',
                'fine_value' => 30.00,
                'effective_from' => $currentYear . '-01-01',
                'effective_to' => $currentYear . '-12-31',
                'is_active' => true,
            ];
        }

        // Add fee-head specific rules if fee heads exist
        if ($feeHeads->isNotEmpty()) {
            $firstFeeHead = $feeHeads->first();
            $fineRules[] = [
                'name' => 'Tuition Fee Late Fine',
                'campus_id' => $campuses->first()->id,
                'session_id' => $sessions->first()->id,
                'class_id' => null,
                'section_id' => null,
                'fee_head_id' => $firstFeeHead->id,
                'grace_days' => 5,
                'fine_type' => 'fixed_per_day',
                'fine_value' => 100.00,
                'effective_from' => $currentYear . '-01-01',
                'effective_to' => $currentYear . '-12-31',
                'is_active' => true,
            ];
        }

        foreach ($fineRules as $fineRule) {
            FeeFineRule::create($fineRule);
        }

        $this->command->info('Fine rules seeded successfully!');
        $this->command->info('Created ' . count($fineRules) . ' fine rules.');
    }
}