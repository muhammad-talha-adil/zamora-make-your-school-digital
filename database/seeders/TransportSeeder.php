<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Month;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportStudentAssignment;
use App\Models\TransportVehicle;
use App\Models\TransportVehicleExpense;
use App\Models\User;
use App\Services\Finance\StudentBillingService;
use App\Services\Finance\UnifiedAccountingService;
use Illuminate\Database\Seeder;

class TransportSeeder extends Seeder
{
    public function run(): void
    {
        $campuses = Campus::where('is_active', true)->get();
        $month = Month::where('month_number', now()->month)->first();
        $creator = User::orderBy('id')->first();

        if ($campuses->isEmpty() || ! $month) {
            $this->command?->warn('TransportSeeder skipped because campuses or months are missing.');

            return;
        }

        foreach ($campuses as $index => $campus) {
            $vehicle = TransportVehicle::updateOrCreate(
                ['vehicle_no' => sprintf('BUS-%02d', $index + 1)],
                [
                    'campus_id' => $campus->id,
                    'vehicle_type' => $index === 0 ? 'bus' : 'van',
                    'capacity' => $index === 0 ? 45 : 22,
                    'driver_name' => $index === 0 ? 'Rashid Khan' : 'Nadeem Akhtar',
                    'attendant_name' => $index === 0 ? 'Bilal Ahmed' : 'Noreen Bibi',
                    'status' => 'active',
                    'purchase_date' => now()->subYears(2 + $index)->toDateString(),
                    'is_active' => true,
                    'notes' => 'Seeded transport vehicle',
                ]
            );

            $stopA = TransportStop::updateOrCreate(
                ['campus_id' => $campus->id, 'name' => $campus->name.' Stop A'],
                [
                    'pickup_time' => '07:15:00',
                    'drop_time' => '13:45:00',
                    'is_active' => true,
                ]
            );

            $stopB = TransportStop::updateOrCreate(
                ['campus_id' => $campus->id, 'name' => $campus->name.' Stop B'],
                [
                    'pickup_time' => '07:35:00',
                    'drop_time' => '14:05:00',
                    'is_active' => true,
                ]
            );

            $route = TransportRoute::updateOrCreate(
                ['campus_id' => $campus->id, 'name' => $campus->name.' Main Route'],
                [
                    'transport_vehicle_id' => $vehicle->id,
                    'monthly_fee' => 3500 + ($index * 500),
                    'is_active' => true,
                    'notes' => 'Seeded transport route',
                ]
            );

            $route->stops()->sync([
                $stopA->id => ['sort_order' => 1],
                $stopB->id => ['sort_order' => 2],
            ]);

            $students = Student::with('currentEnrollment')
                ->whereHas('currentEnrollment', fn ($query) => $query->where('campus_id', $campus->id))
                ->take(4)
                ->get();

            foreach ($students as $studentIndex => $student) {
                $assignment = TransportStudentAssignment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'transport_route_id' => $route->id,
                    ],
                    [
                        'student_enrollment_record_id' => $student->currentEnrollment?->id,
                        'campus_id' => $campus->id,
                        'transport_stop_id' => $studentIndex % 2 === 0 ? $stopA->id : $stopB->id,
                        'monthly_fee' => (float) $route->monthly_fee,
                        'effective_from' => now()->startOfMonth()->toDateString(),
                        'effective_to' => null,
                        'status' => 'active',
                        'generate_dues' => true,
                    ]
                );

                $charge = app(StudentBillingService::class)->createTransportCharge(
                    $assignment->fresh(['student', 'enrollmentRecord', 'route', 'stop']),
                    $month->id,
                    (int) now()->year,
                    now()->endOfMonth()->toDateString()
                );

                app(UnifiedAccountingService::class)->postTransportChargeJournal($charge);
            }

            $expense = TransportVehicleExpense::updateOrCreate(
                [
                    'campus_id' => $campus->id,
                    'transport_vehicle_id' => $vehicle->id,
                    'expense_type' => 'fuel',
                    'expense_date' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'amount' => 18000 + ($index * 2500),
                    'payment_method' => 'cash',
                    'reference_no' => sprintf('FUEL-%02d-%s', $index + 1, now()->format('Ym')),
                    'description' => 'Seeded monthly fuel expense',
                    'created_by' => $creator?->id,
                ]
            );

            app(UnifiedAccountingService::class)->postTransportExpenseJournal($expense->fresh());
        }

        $this->command?->info('Transport records seeded successfully.');
    }
}
