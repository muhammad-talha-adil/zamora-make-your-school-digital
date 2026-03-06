<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any attendances.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the attendance.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $user->hasPermission('attendance.view') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create attendances.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('attendance.create') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the attendance.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        // Check if attendance is locked
        if ($attendance->is_locked) {
            return false;
        }

        return $user->hasPermission('attendance.edit') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the attendance.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        // Check if attendance is locked
        if ($attendance->is_locked) {
            return false;
        }

        return $user->hasPermission('attendance.delete') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can lock the attendance.
     */
    public function lock(User $user, Attendance $attendance): bool
    {
        // Cannot lock if already locked
        if ($attendance->is_locked) {
            return false;
        }

        return $user->hasPermission('attendance.lock') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can unlock the attendance.
     */
    public function unlock(User $user, Attendance $attendance): bool
    {
        // Cannot unlock if already unlocked
        if (!$attendance->is_locked) {
            return false;
        }

        return $user->hasPermission('attendance.unlock') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view attendance reports.
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('attendance.reports') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can export attendance data.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('attendance.export') || $user->hasRole('admin');
    }
}
