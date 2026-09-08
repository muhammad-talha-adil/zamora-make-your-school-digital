<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * The full ability list, grouped by module.
 *
 * `name` is what code checks (`fee.voucher.generate`); `module` groups the
 * abilities for a permissions screen; `label` is what a person reads.
 *
 * Naming is `<module>.<subject>.<action>`, so a whole area can be granted with
 * a wildcard-style filter in RolesSeeder without listing each ability.
 */
class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions() as $module => $abilities) {
            foreach ($abilities as $name => $label) {
                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['module' => $module, 'label' => $label]
                );
            }
        }

        // Anything seeded by an earlier revision that no longer appears above
        // is removed, so a stale ability cannot keep granting access.
        $current = collect($this->permissions())->flatMap(fn ($a) => array_keys($a))->all();
        Permission::whereNotIn('name', $current)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Permissions seeded: '.count($current));
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function permissions(): array
    {
        return [
            'system' => [
                'system.subscription.manage' => 'Manage subscription & renewal',
                'system.tools.run' => 'Run system tools (cache, migrations)',
                'system.audit.view' => 'View audit log',
            ],

            'school' => [
                'school.profile.manage' => 'Manage school profile & branding',
                'school.theme.manage' => 'Manage theme & appearance',
                'school.menu.manage' => 'Manage navigation menus',
                'school.campus.view' => 'View campuses',
                'school.campus.manage' => 'Create & edit campuses',
                'school.campus.delete' => 'Delete campuses',
            ],

            'users' => [
                'users.view' => 'View users',
                'users.manage' => 'Create & edit users',
                'users.delete' => 'Delete users',
                'users.role.assign' => 'Assign roles to users',
                'users.role.manage' => 'Create & edit roles and permissions',
            ],

            'academics' => [
                'academics.session.manage' => 'Manage academic sessions',
                'academics.class.manage' => 'Manage classes & sections',
                'academics.subject.manage' => 'Manage subjects',
                'academics.timetable.view' => 'View timetable',
                'academics.timetable.manage' => 'Manage timetable & periods',
            ],

            'students' => [
                'students.view' => 'View students',
                'students.view.own' => 'View own student record',
                'students.create' => 'Admit students',
                'students.edit' => 'Edit students',
                'students.edit.own' => 'Edit own student record',
                'students.delete' => 'Delete students',
                'students.restore' => 'Restore deleted students',
                'students.force.delete' => 'Permanently delete students',
                'students.status.change' => 'Change student status',
                'students.readmit' => 'Readmit students',
                'students.promote' => 'Promote students to next session',
                'students.export' => 'Export students',
                'students.import' => 'Import students',
            ],

            'attendance' => [
                'attendance.view' => 'View attendance',
                'attendance.view.own' => 'View own attendance',
                'attendance.mark' => 'Mark attendance',
                'attendance.edit' => 'Edit attendance',
                'attendance.delete' => 'Delete attendance',
                'attendance.lock' => 'Lock attendance',
                'attendance.unlock' => 'Unlock attendance',
                'attendance.reports' => 'View attendance reports',
                'attendance.export' => 'Export attendance',
                'attendance.settings' => 'Manage attendance settings, holidays & leaves',
            ],

            'exam' => [
                'exam.view' => 'View exams',
                'exam.manage' => 'Create & edit exams',
                'exam.delete' => 'Delete exams',
                'exam.paper.view' => 'View exam papers & date sheet',
                'exam.paper.manage' => 'Create & schedule exam papers',
                'exam.registration.manage' => 'Manage exam registrations',
                'exam.marks.enter' => 'Enter marks',
                'exam.marks.verify' => 'Verify & finalise marks',
                'exam.result.view' => 'View results',
                'exam.result.view.own' => 'View own results',
                'exam.result.publish' => 'Publish results',
                'exam.revaluation.manage' => 'Manage revaluation requests',
                'exam.settings' => 'Manage exam types & grade systems',
            ],

            'fee' => [
                'fee.view' => 'View fee records',
                'fee.view.own' => 'View own fee records',
                'fee.head.manage' => 'Manage fee heads',
                'fee.structure.manage' => 'Manage fee structures',
                'fee.voucher.view' => 'View vouchers',
                'fee.voucher.generate' => 'Generate vouchers',
                'fee.voucher.edit' => 'Edit vouchers',
                'fee.voucher.delete' => 'Delete vouchers',
                'fee.voucher.print' => 'Print vouchers',
                'fee.payment.collect' => 'Collect payments',
                'fee.payment.refund' => 'Refund payments',
                'fee.discount.manage' => 'Manage discounts & concessions',
                'fee.discount.approve' => 'Approve discounts',
                'fee.fine.manage' => 'Manage fine rules',
                'fee.reports' => 'View fee reports',
            ],

            'finance' => [
                'finance.view' => 'View finance dashboard',
                'finance.transaction.view' => 'View transactions',
                'finance.transaction.manage' => 'Record payments & receipts',
                'finance.ledger.manage' => 'Manage ledgers & categories',
                'finance.account.manage' => 'Manage chart of accounts',
                'finance.reports' => 'View finance reports',
                'finance.reports.owner' => 'View profit & loss and owner reports',
            ],

            'inventory' => [
                'inventory.view' => 'View inventory',
                'inventory.item.manage' => 'Manage items & types',
                'inventory.stock.manage' => 'Manage stock & adjustments',
                'inventory.purchase.view' => 'View purchases',
                'inventory.purchase.manage' => 'Create & edit purchases',
                'inventory.purchase.delete' => 'Delete purchases',
                'inventory.supplier.manage' => 'Manage suppliers',
                'inventory.return.manage' => 'Manage returns',
                'inventory.student.issue' => 'Issue items to students',
                'inventory.reports' => 'View inventory reports & valuation',
            ],

            'staff' => [
                'staff.view' => 'View staff',
                'staff.view.own' => 'View own staff record',
                'staff.manage' => 'Create & edit staff',
                'staff.delete' => 'Delete staff',
                'staff.department.manage' => 'Manage departments & designations',
                'staff.salary.manage' => 'Set staff salary',
                'staff.payroll.run' => 'Generate payroll',
                'staff.payroll.approve' => 'Approve & pay payroll',
                'staff.attendance.view' => 'View staff attendance',
                'staff.attendance.mark' => 'Mark staff attendance',
            ],

            'transport' => [
                'transport.view' => 'View transport',
                'transport.view.own' => 'View own route & duty',
                'transport.vehicle.manage' => 'Manage vehicles',
                'transport.route.manage' => 'Manage routes & stops',
                'transport.assignment.manage' => 'Assign students to routes',
                'transport.expense.manage' => 'Manage vehicle expenses',
            ],

            'portal' => [
                'portal.student.access' => 'Access student portal',
                'portal.teacher.access' => 'Access teacher portal',
                'portal.staff.access' => 'Access staff portal',
            ],
        ];
    }
}
