<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and the abilities each one holds.
 *
 * The hierarchy, briefly:
 *
 *   developer     everything, and the only role that owns subscription and
 *                 system tooling
 *   owner         everything across every campus except the above
 *   super_admin   runs day-to-day operations across campuses, but cannot
 *                 change the business structure: no campus create/delete, no
 *                 branding, no salary changes or payroll approval, no user
 *                 deletion, and finance reports are read-only
 *   campus_admin  the same operational reach, limited to their own campus
 *   teacher       their own classes: attendance, marks, papers, timetable
 *   head_teacher  a teacher, plus verifying marks and seeing the whole class
 *   accountant    everything financial
 *   student       their own record only
 *   guardian      the same view as the student they belong to
 *   driver /
 *   clerk / maid /
 *   receptionist  staff portal, plus whatever their job needs
 *
 * Campus limits are not expressed here. A role says *what* someone may do;
 * *whose* records they may do it to is enforced by policies and query scopes.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->roles() as $name => $definition) {
            $role = Role::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'label' => $definition['label'],
                    'scope_level' => $definition['scope'],
                    'is_active' => true,
                ]
            );

            $role->syncPermissions($this->expand($definition['permissions']));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Roles seeded: '.count($this->roles()));
    }

    /**
     * Expands the shorthand used below into concrete permission names.
     *
     *   '*'          every ability
     *   'module.*'   every ability in that module
     *   'a.b.c'      that one ability
     *
     * Entries prefixed with '-' subtract, and are applied last, which is how
     * owner and super_admin are described as "everything except ...".
     *
     * @param  list<string>  $patterns
     * @return list<string>
     */
    private function expand(array $patterns): array
    {
        $all = Permission::pluck('name');

        $granted = collect();
        $denied = collect();

        foreach ($patterns as $pattern) {
            $subtract = str_starts_with($pattern, '-');
            $needle = ltrim($pattern, '-');

            $matched = match (true) {
                $needle === '*' => $all,
                str_ends_with($needle, '.*') => $all->filter(
                    fn ($n) => str_starts_with($n, substr($needle, 0, -1))
                ),
                default => $all->filter(fn ($n) => $n === $needle),
            };

            $subtract ? $denied = $denied->merge($matched) : $granted = $granted->merge($matched);
        }

        return $granted->unique()->diff($denied)->values()->all();
    }

    /**
     * @return array<string, array{label: string, scope: string, permissions: list<string>}>
     */
    private function roles(): array
    {
        // Everything a teacher does for the classes assigned to them.
        $teacher = [
            'portal.teacher.access',
            'students.view',
            'attendance.view', 'attendance.mark', 'attendance.edit', 'attendance.reports',
            'exam.view', 'exam.paper.view', 'exam.paper.manage',
            'exam.marks.enter', 'exam.result.view',
            'academics.timetable.view',
            'staff.view.own',
        ];

        // Portal access for non-teaching staff, who all see their own record.
        $staffBasics = [
            'portal.staff.access',
            'staff.view.own',
        ];

        return [
            'developer' => [
                'label' => 'Developer',
                'scope' => Role::SCOPE_SYSTEM,
                'permissions' => ['*'],
            ],

            'owner' => [
                'label' => 'School Owner',
                'scope' => Role::SCOPE_SCHOOL,
                // Everything except the subscription and system tooling that
                // stays with the developer.
                'permissions' => ['*', '-system.*'],
            ],

            'super_admin' => [
                'label' => 'Super Admin',
                'scope' => Role::SCOPE_SCHOOL,
                'permissions' => [
                    '*',
                    '-system.*',
                    // structure and branding belong to the owner
                    '-school.campus.manage', '-school.campus.delete',
                    '-school.profile.manage', '-school.theme.manage',
                    // may manage staff and students, not owner-level accounts
                    '-users.delete', '-users.role.manage',
                    // may run payroll, not set pay or release it
                    '-staff.salary.manage', '-staff.payroll.approve',
                    // owner-only financial view
                    '-finance.reports.owner',
                    // destructive actions stay above this level
                    '-students.delete', '-exam.delete', '-fee.voucher.delete',
                    '-inventory.purchase.delete', '-staff.delete',
                ],
            ],

            'campus_admin' => [
                'label' => 'Campus Admin',
                'scope' => Role::SCOPE_CAMPUS,
                // Full operational reach, scoped to their campus by policy.
                'permissions' => [
                    'academics.*', 'students.*', 'attendance.*', 'exam.*',
                    'fee.*', 'inventory.*', 'transport.*',
                    'staff.view', 'staff.manage', 'staff.attendance.view', 'staff.attendance.mark',
                    'finance.view', 'finance.transaction.view', 'finance.reports',
                    'users.view', 'users.manage', 'users.role.assign',
                    'school.campus.view',
                    'portal.staff.access',
                    '-students.delete', '-exam.delete', '-fee.voucher.delete',
                    '-inventory.purchase.delete',
                ],
            ],

            'head_teacher' => [
                'label' => 'Head Teacher',
                'scope' => Role::SCOPE_CAMPUS,
                // A teacher who also signs off marks and sees the full class.
                'permissions' => array_merge($teacher, [
                    'exam.marks.verify',
                    'exam.registration.manage',
                    'attendance.lock',
                    'students.view',
                    'academics.timetable.manage',
                ]),
            ],

            'teacher' => [
                'label' => 'Teacher',
                'scope' => Role::SCOPE_CAMPUS,
                'permissions' => $teacher,
            ],

            'accountant' => [
                'label' => 'Accountant',
                'scope' => Role::SCOPE_CAMPUS,
                'permissions' => array_merge($staffBasics, [
                    'fee.*', 'finance.*',
                    'students.view',
                    'inventory.view', 'inventory.reports',
                    'staff.payroll.run',
                    '-fee.voucher.delete', '-finance.reports.owner',
                ]),
            ],

            'student' => [
                'label' => 'Student',
                'scope' => Role::SCOPE_SELF,
                'permissions' => [
                    'portal.student.access',
                    'students.view.own',
                    'attendance.view.own',
                    'exam.result.view.own', 'exam.paper.view',
                    'fee.view.own',
                    'academics.timetable.view',
                    'transport.view.own',
                ],
            ],

            'guardian' => [
                'label' => 'Guardian',
                'scope' => Role::SCOPE_SELF,
                // Guardians are not a separate portal: they see exactly what
                // their student sees.
                'permissions' => [
                    'portal.student.access',
                    'students.view.own',
                    'attendance.view.own',
                    'exam.result.view.own', 'exam.paper.view',
                    'fee.view.own',
                    'academics.timetable.view',
                    'transport.view.own',
                ],
            ],

            'driver' => [
                'label' => 'Driver',
                'scope' => Role::SCOPE_CAMPUS,
                'permissions' => array_merge($staffBasics, [
                    'transport.view.own',
                ]),
            ],

            'clerk' => [
                'label' => 'Clerk',
                'scope' => Role::SCOPE_CAMPUS,
                'permissions' => array_merge($staffBasics, [
                    'students.view', 'students.create', 'students.edit',
                    'fee.view', 'fee.voucher.view', 'fee.voucher.print',
                    'attendance.view',
                    'inventory.view',
                ]),
            ],

            'receptionist' => [
                'label' => 'Receptionist',
                'scope' => Role::SCOPE_CAMPUS,
                'permissions' => array_merge($staffBasics, [
                    'students.view',
                    'attendance.view',
                    'fee.view', 'fee.voucher.view', 'fee.voucher.print',
                    'transport.view',
                ]),
            ],

            'maid' => [
                'label' => 'Maid',
                'scope' => Role::SCOPE_CAMPUS,
                'permissions' => $staffBasics,
            ],
        ];
    }
}
