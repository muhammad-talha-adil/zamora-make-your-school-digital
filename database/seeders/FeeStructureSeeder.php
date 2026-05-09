<?php

namespace Database\Seeders;

use App\Enums\Fee\FeeFrequency;
use App\Enums\Fee\FeeStructureStatus;
use App\Models\Campus;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Seeder;

class FeeStructureSeeder extends Seeder
{
    public function run(): void
    {
        $session = Session::where('is_active', true)->first();
        $campuses = Campus::where('is_active', true)->get();
        $classes = SchoolClass::where('is_active', true)->get();
        $creator = User::orderBy('id')->first();

        $tuitionHead = FeeHead::where('code', 'TUITION')->first() ?? FeeHead::ordered()->first();
        $examHead = FeeHead::where('code', 'EXAM')->first();
        $computerHead = FeeHead::where('code', 'COMPUTER')->first() ?? FeeHead::where('name', 'like', '%Computer%')->first();
        $admissionHead = FeeHead::where('code', 'ADMISSION')->first();

        if (! $session || $campuses->isEmpty() || $classes->isEmpty() || ! $tuitionHead) {
            $this->command?->warn('FeeStructureSeeder skipped because campuses, session, classes, or fee heads are missing.');

            return;
        }

        foreach ($campuses as $campus) {
            foreach ($classes as $class) {
                $structure = FeeStructure::updateOrCreate(
                    [
                        'session_id' => $session->id,
                        'campus_id' => $campus->id,
                        'class_id' => $class->id,
                        'section_id' => null,
                    ],
                    [
                        'title' => sprintf('%s Fee Plan - %s', $class->name, $campus->name),
                        'is_default' => true,
                        'effective_from' => now()->startOfYear()->toDateString(),
                        'effective_to' => now()->endOfYear()->toDateString(),
                        'status' => FeeStructureStatus::ACTIVE,
                        'notes' => 'Seeded default class-level fee structure.',
                        'created_by' => $creator?->id,
                    ]
                );

                $baseTuition = match (strtolower($class->name)) {
                    'pg', 'kg-i', 'kg-ii' => 3000,
                    'one', 'two', 'three' => 4000,
                    'four', 'five' => 5000,
                    'six', 'seven', 'eight' => 6500,
                    default => 8000,
                };

                $structure->items()->updateOrCreate(
                    ['fee_head_id' => $tuitionHead->id],
                    [
                        'amount' => $baseTuition,
                        'frequency' => FeeFrequency::MONTHLY,
                        'applicable_on_admission' => false,
                        'billing_month_id' => null,
                        'billing_year' => null,
                        'starts_from_month_id' => 4,
                        'ends_at_month_id' => 3,
                        'is_optional' => false,
                        'is_transport_related' => false,
                        'notes' => 'Seeded monthly tuition fee',
                    ]
                );

                if ($examHead) {
                    $structure->items()->updateOrCreate(
                        ['fee_head_id' => $examHead->id],
                        [
                            'amount' => max(1000, (int) round($baseTuition * 0.35)),
                            'frequency' => FeeFrequency::MONTHLY,
                            'applicable_on_admission' => false,
                            'billing_month_id' => null,
                            'billing_year' => null,
                            'starts_from_month_id' => 4,
                            'ends_at_month_id' => 3,
                            'is_optional' => false,
                            'is_transport_related' => false,
                            'notes' => 'Seeded recurring exam support fee',
                        ]
                    );
                }

                if ($computerHead) {
                    $structure->items()->updateOrCreate(
                        ['fee_head_id' => $computerHead->id],
                        [
                            'amount' => max(500, (int) round($baseTuition * 0.2)),
                            'frequency' => FeeFrequency::MONTHLY,
                            'applicable_on_admission' => false,
                            'billing_month_id' => null,
                            'billing_year' => null,
                            'starts_from_month_id' => 4,
                            'ends_at_month_id' => 3,
                            'is_optional' => false,
                            'is_transport_related' => false,
                            'notes' => 'Seeded recurring lab / activity fee',
                        ]
                    );
                }

                if ($admissionHead) {
                    $structure->items()->updateOrCreate(
                        ['fee_head_id' => $admissionHead->id],
                        [
                            'amount' => max(2500, (int) round($baseTuition * 0.75)),
                            'frequency' => FeeFrequency::ONCE,
                            'applicable_on_admission' => true,
                            'billing_month_id' => null,
                            'billing_year' => null,
                            'starts_from_month_id' => null,
                            'ends_at_month_id' => null,
                            'is_optional' => false,
                            'is_transport_related' => false,
                            'notes' => 'Seeded admission fee',
                        ]
                    );
                }
            }
        }

        $this->command?->info('Fee structures seeded successfully.');
    }
}
