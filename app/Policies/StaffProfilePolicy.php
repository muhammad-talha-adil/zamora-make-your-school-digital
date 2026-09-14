<?php

namespace App\Policies;

use App\Models\StaffProfile;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may read and change a staff record.
 *
 * The ten `staff.*` permissions have been seeded since the beginning and **not
 * one of them was used** — the eleven staff routes sat on `auth` alone, so any
 * signed-in account could read every salary in the school and generate a
 * payroll.
 *
 * The widths are `ChecksSchoolReach`, shared with the student and exam
 * policies. Staff have no class, so only two of the three apply: school-wide,
 * or a campus. A record with no campus is a school-wide post — the principal,
 * the accountant — and stays visible.
 *
 * **Salary is its own width, deliberately.** A campus admin may hire, edit and
 * mark attendance and must not see what anybody is paid, which is why
 * `staff.salary.manage` is a separate ability from `staff.manage` and is
 * checked separately here.
 */
class StaffProfilePolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'staff.view');
    }

    /**
     * The teaching-assignments screen.
     *
     * Wider than {@see viewAny()}: a teacher who only holds `staff.view.own`
     * may still open it to see their own classes and subjects — the
     * controller scopes the list down to just their own assignments in that
     * case, rather than everybody's.
     */
    public function viewTeaching(User $user): bool
    {
        return $this->may($user, 'staff.view', 'staff.view.own');
    }

    public function view(User $user, StaffProfile $staff): bool
    {
        // Their own record. This is the staff portal.
        if ($this->isTheirOwn($user, $staff)) {
            return $this->may($user, 'staff.view.own', 'staff.view');
        }

        return $this->may($user, 'staff.view') && $this->covers($user, $staff);
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'staff.manage');
    }

    public function update(User $user, StaffProfile $staff): bool
    {
        return $this->may($user, 'staff.manage') && $this->covers($user, $staff);
    }

    public function delete(User $user, StaffProfile $staff): bool
    {
        return $this->may($user, 'staff.delete') && $this->covers($user, $staff);
    }

    /**
     * Reading what somebody is paid.
     *
     * The one thing on this record that a person who may otherwise manage staff
     * is not automatically entitled to. Everybody may read their own.
     */
    public function viewSalary(User $user, StaffProfile $staff): bool
    {
        if ($this->isTheirOwn($user, $staff)) {
            return $this->may($user, 'staff.view.own', 'staff.salary.manage');
        }

        return $this->may($user, 'staff.salary.manage') && $this->covers($user, $staff);
    }

    /**
     * Setting what somebody is paid.
     */
    public function manageSalary(User $user, StaffProfile $staff): bool
    {
        // Nobody sets their own salary, whatever they hold.
        if ($this->isTheirOwn($user, $staff) && ! $user->isSuperAdmin()) {
            return false;
        }

        return $this->may($user, 'staff.salary.manage') && $this->covers($user, $staff);
    }

    /**
     * Departments and designations — the lists the whole school shares.
     */
    public function manageStructure(User $user): bool
    {
        return $this->may($user, 'staff.department.manage');
    }

    public function viewAttendance(User $user, StaffProfile $staff): bool
    {
        if ($this->isTheirOwn($user, $staff)) {
            return $this->may($user, 'staff.view.own', 'staff.attendance.view');
        }

        return $this->may($user, 'staff.attendance.view') && $this->covers($user, $staff);
    }

    public function markAttendance(User $user): bool
    {
        return $this->may($user, 'staff.attendance.mark');
    }

    /**
     * Applying for leave is something everybody does for themselves; deciding
     * on it is not.
     */
    public function applyForLeave(User $user, StaffProfile $staff): bool
    {
        return $this->isTheirOwn($user, $staff) || $this->update($user, $staff);
    }

    public function decideLeave(User $user, StaffProfile $staff): bool
    {
        // Nobody approves their own leave.
        if ($this->isTheirOwn($user, $staff) && ! $user->isSuperAdmin()) {
            return false;
        }

        return $this->may($user, 'staff.attendance.mark', 'staff.manage')
            && $this->covers($user, $staff);
    }

    /**
     * Generating a payroll run, and paying it.
     *
     * Two abilities on purpose: the person who works the figures out is not
     * automatically the person who releases the money.
     */
    public function runPayroll(User $user): bool
    {
        return $this->may($user, 'staff.payroll.run');
    }

    public function approvePayroll(User $user): bool
    {
        return $this->may($user, 'staff.payroll.approve');
    }

    /**
     * Whether this record falls inside the user's reach.
     */
    private function covers(User $user, StaffProfile $staff): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // A post with no campus is school-wide and reachable by anybody who may
        // see staff at all.
        if ($staff->campus_id === null) {
            return true;
        }

        $userCampus = $user->campusId();

        return $userCampus === null || (int) $staff->campus_id === (int) $userCampus;
    }

    private function isTheirOwn(User $user, StaffProfile $staff): bool
    {
        return $staff->user_id !== null && (int) $staff->user_id === (int) $user->id;
    }
}
