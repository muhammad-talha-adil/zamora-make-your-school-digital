<?php

namespace App\Console\Commands;

use App\Models\StaffDesignation;
use Illuminate\Console\Command;

/**
 * Idempotent: adds the standard school designations with their default
 * permission template. Existing designations (matched by name) keep their
 * name and description, and keep a template someone already set.
 */
class SeedStaffDesignations extends Command
{
    protected $signature = 'staff:seed-designations';

    protected $description = 'Add the standard school designations with default permission templates';

    /**
     * @return array<string, array<int, string>>
     */
    public static function templates(): array
    {
        $teaching = ['students.view', 'attendance.view', 'attendance.mark', 'exam.view', 'exam.paper.view', 'exam.marks.enter', 'exam.result.view', 'academics.timetable.view', 'portal.teacher.access'];
        $office = ['students.view', 'students.create', 'students.edit', 'attendance.view', 'staff.view'];

        return [
            'Principal' => [...$teaching, 'students.edit', 'students.promote', 'students.export', 'attendance.edit', 'attendance.lock', 'attendance.reports', 'exam.manage', 'exam.paper.manage', 'exam.marks.verify', 'exam.result.publish', 'fee.view', 'fee.voucher.view', 'fee.reports', 'staff.view', 'staff.manage', 'staff.attendance.view', 'staff.attendance.mark', 'academics.timetable.manage', 'transport.view', 'inventory.view', 'finance.view'],
            'Vice Principal' => [...$teaching, 'students.edit', 'attendance.edit', 'attendance.reports', 'exam.manage', 'exam.paper.manage', 'exam.marks.verify', 'fee.view', 'fee.reports', 'staff.view', 'staff.attendance.view', 'academics.timetable.manage'],
            'Head Teacher' => [...$teaching, 'attendance.view.campus-wide', 'attendance.reports', 'exam.marks.verify', 'academics.timetable.manage'],
            'Teacher' => $teaching,
            'Accountant' => ['students.view', 'fee.view', 'fee.head.manage', 'fee.structure.manage', 'fee.voucher.view', 'fee.voucher.generate', 'fee.voucher.edit', 'fee.voucher.print', 'fee.payment.collect', 'fee.payment.refund', 'fee.discount.manage', 'fee.fine.manage', 'fee.reports', 'finance.view', 'finance.transaction.view', 'finance.transaction.manage', 'finance.reports'],
            'Admission Officer' => [...$office, 'students.import', 'students.readmit', 'fee.view', 'fee.voucher.view'],
            'Clerk' => $office,
            'Librarian' => ['students.view', 'inventory.view', 'inventory.item.manage', 'inventory.stock.manage', 'inventory.student.issue'],
            'Lab Assistant' => ['students.view', 'inventory.view', 'inventory.stock.manage'],
            'IT Administrator' => ['users.view', 'users.manage', 'students.view', 'staff.view', 'inventory.view'],
            'Transport Incharge' => ['transport.view', 'transport.vehicle.manage', 'transport.route.manage', 'transport.assignment.manage', 'transport.expense.manage', 'students.view'],
            'Driver' => ['transport.view'],
            'Attendant / Peon' => ['portal.staff.access'],
            'Security Guard' => ['portal.staff.access'],
            'Coordinator' => [...$teaching, 'attendance.view.campus-wide', 'attendance.reports', 'academics.timetable.manage', 'exam.paper.manage'],
        ];
    }

    public function handle(): int
    {
        $created = 0;

        foreach (self::templates() as $name => $permissions) {
            $designation = StaffDesignation::firstOrNew(['name' => $name]);

            if (! $designation->exists) {
                $designation->is_active = true;
                $created++;
            }

            if (empty($designation->permission_template)) {
                $designation->permission_template = array_values(array_unique($permissions));
            }

            $designation->save();
        }

        $this->info("Designations ready ({$created} added).");

        return self::SUCCESS;
    }
}
