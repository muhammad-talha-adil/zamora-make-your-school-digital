<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Month;
use App\Models\PayrollRun;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Finance\UnifiedAccountingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $campus = Campus::where('is_active', true)->first();
        $month = Month::where('month_number', now()->month)->first();
        $creator = User::orderBy('id')->first();

        if (! $campus || ! $month) {
            $this->command?->warn('PayrollSeeder skipped because campus or month data is missing.');

            return;
        }

        $staffProfiles = StaffProfile::with('user')
            ->where('is_active', true)
            ->where('campus_id', $campus->id)
            ->get();

        if ($staffProfiles->isEmpty()) {
            $this->command?->warn('PayrollSeeder skipped because no active staff profiles were found.');

            return;
        }

        $run = DB::transaction(function () use ($campus, $month, $creator, $staffProfiles) {
            $run = PayrollRun::updateOrCreate(
                [
                    'campus_id' => $campus->id,
                    'payroll_month_id' => $month->id,
                    'payroll_year' => (int) now()->year,
                ],
                [
                    'title' => sprintf('%s Payroll %s', $campus->name, now()->format('F Y')),
                    'status' => 'processed',
                    'processed_at' => now(),
                    'created_by' => $creator?->id,
                ]
            );

            $run->items()->delete();

            $gross = 0;
            $deductions = 0;
            $net = 0;

            foreach ($staffProfiles as $index => $profile) {
                $item = $run->items()->create([
                    'staff_profile_id' => $profile->id,
                    'gross_salary' => (float) $profile->basic_salary,
                    'allowance_amount' => (float) $profile->allowance_amount,
                    'deduction_amount' => (float) $profile->deduction_amount,
                    'net_salary' => (float) $profile->net_salary,
                    'status' => $index === 0 ? 'paid' : 'pending',
                    'payment_method' => $profile->payment_method,
                    'reference_no' => $index === 0 ? 'SAL-'.now()->format('Ym').'-001' : null,
                    'paid_at' => $index === 0 ? now() : null,
                ]);

                $gross += (float) $profile->gross_salary;
                $deductions += (float) $profile->deduction_amount;
                $net += (float) $profile->net_salary;
            }

            $run->update([
                'total_gross' => $gross,
                'total_deductions' => $deductions,
                'total_net' => $net,
            ]);

            return $run->fresh(['items.staffProfile.user']);
        });

        $accountingService = app(UnifiedAccountingService::class);
        $accountingService->postPayrollAccrualJournal($run);

        $paidItem = $run->items->firstWhere('status', 'paid');
        if ($paidItem) {
            $accountingService->postPayrollPaymentJournal($paidItem);
        }

        $this->command?->info('Payroll seeded successfully.');
    }
}
