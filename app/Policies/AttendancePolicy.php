<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may read and change a register.
 *
 * Every method here used to be a bare permission check that ignored the record
 * entirely, so any teacher holding `attendance.view` could read **every
 * campus's** registers and anyone holding `attendance.edit` could rewrite a
 * class they had nothing to do with.
 *
 * There are three widths of access, and each method applies the same three:
 *
 *  - **School-wide** — developer, owner, super admin. Every campus.
 *  - **Campus** — campus admin, head teacher. Their own campus, every class in
 *    it. A head teacher sits here until wings are modelled, which is the more
 *    restrictive of the two readings we can support today.
 *  - **Class** — a teacher. Only the sections they have been given.
 *
 * A permission says *what* somebody may do; this says *whose records*. Both
 * have to pass.
 */
class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any attendances.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the attendance.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        if (! $user->hasPermission('attendance.view') && ! $user->isSuperAdmin()) {
            return false;
        }

        return $this->coversRegister($user, $attendance);
    }

    /**
     * Determine whether the user can create attendances.
     *
     * Class-level: there is no record to check yet. The register being written
     * is checked by `update()` when it already exists, and the controller
     * authorises that before it touches anything.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('attendance.mark') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the attendance.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        // A signed-off register is closed to everybody who is not exempt from
        // the lock, whatever else they may do.
        if ($attendance->is_locked) {
            return false;
        }

        if (! $user->hasPermission('attendance.edit') && ! $user->isSuperAdmin()) {
            return false;
        }

        return $this->coversRegister($user, $attendance);
    }

    /**
     * Determine whether the user can delete the attendance.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        if ($attendance->is_locked) {
            return false;
        }

        if (! $user->hasPermission('attendance.delete') && ! $user->isSuperAdmin()) {
            return false;
        }

        return $this->coversRegister($user, $attendance);
    }

    /**
     * Determine whether the user can lock the attendance.
     */
    public function lock(User $user, Attendance $attendance): bool
    {
        if ($attendance->is_locked) {
            return false;
        }

        if (! $user->hasPermission('attendance.lock') && ! $user->isSuperAdmin()) {
            return false;
        }

        return $this->coversRegister($user, $attendance);
    }

    /**
     * Determine whether the user can unlock the attendance.
     *
     * Reopening a closed register is deliberately narrower than closing one: a
     * teacher may sign their own register off, but only a campus admin or above
     * may open it again. That is the point of closing it.
     */
    public function unlock(User $user, Attendance $attendance): bool
    {
        if (! $attendance->is_locked) {
            return false;
        }

        if (! $user->hasPermission('attendance.unlock') && ! $user->isSuperAdmin()) {
            return false;
        }

        if ($user->isClassRestricted()) {
            return false;
        }

        return $this->coversRegister($user, $attendance);
    }

    /**
     * Determine whether the user can view attendance reports.
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('attendance.reports') || $user->isSuperAdmin();
    }

    /**
     * Whether this register falls inside the user's reach.
     *
     * The single place the three widths are decided, so the methods above
     * cannot drift apart from one another.
     */
    private function coversRegister(User $user, Attendance $attendance): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Their campus, whichever role they hold within it.
        $campusId = $user->campusId();

        if ($campusId !== null && (int) $attendance->campus_id !== (int) $campusId) {
            return false;
        }

        if (! $user->isClassRestricted()) {
            return true;
        }

        return $user->teachesSection(
            $attendance->class_id,
            $attendance->section_id,
            $attendance->session_id
        );
    }
}
