<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class StudentPolicy
{
    use HandlesAuthorization;

    /**
     * Log authorization attempts for audit trail.
     */
    private function logAuthorization(User $user, string $ability, ?Student $student = null): void
    {
        Log::info('Student Authorization Attempt', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'ability' => $ability,
            'student_id' => $student?->id,
            'student_code' => $student?->student_code,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Determine whether the user can view any students.
     */
    public function viewAny(User $user): bool
    {
        $this->logAuthorization($user, 'viewAny');

        return $user->can('students.view') || $user->can('students.view_any');
    }

    /**
     * Determine whether the user can view a specific student.
     */
    public function view(User $user, Student $student): bool
    {
        $this->logAuthorization($user, 'view', $student);

        // Allow if user has specific student view permission
        if ($user->can('students.view')) {
            return true;
        }

        // Allow students to view their own data
        if ($user->can('students.view_self') && $this->isOwnStudent($user, $student)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create students.
     */
    public function create(User $user): bool
    {
        $this->logAuthorization($user, 'create');

        return $user->can('students.create');
    }

    /**
     * Determine whether the user can update students.
     */
    public function update(User $user, Student $student): bool
    {
        $this->logAuthorization($user, 'update', $student);

        // Allow if user has specific student update permission
        if ($user->can('students.update')) {
            return true;
        }

        // Allow students to update their own data (limited fields)
        if ($user->can('students.update_self') && $this->isOwnStudent($user, $student)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete students.
     */
    public function delete(User $user, Student $student): bool
    {
        $this->logAuthorization($user, 'delete', $student);

        // Prevent deletion of students with active enrollments
        if ($student->currentEnrollment) {
            return $user->can('students.delete_with_enrollment');
        }

        return $user->can('students.delete');
    }

    /**
     * Determine whether the user can restore students.
     */
    public function restore(User $user, Student $student): bool
    {
        $this->logAuthorization($user, 'restore', $student);

        return $user->can('students.restore');
    }

    /**
     * Determine whether the user can permanently delete students.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        $this->logAuthorization($user, 'forceDelete', $student);

        return $user->can('students.force_delete');
    }

    /**
     * Determine whether the user can change student status.
     */
    public function changeStatus(User $user, Student $student): bool
    {
        $this->logAuthorization($user, 'changeStatus', $student);

        return $user->can('students.change_status');
    }

    /**
     * Determine whether the user can re-admit students.
     */
    public function readmit(User $user, Student $student): bool
    {
        $this->logAuthorization($user, 'readmit', $student);

        return $user->can('students.readmit');
    }

    /**
     * Determine whether the user can export students.
     */
    public function export(User $user): bool
    {
        $this->logAuthorization($user, 'export');

        return $user->can('students.export');
    }

    /**
     * Determine whether the user can import students.
     */
    public function import(User $user): bool
    {
        $this->logAuthorization($user, 'import');

        return $user->can('students.import');
    }

    /**
     * Check if the student belongs to the user (for self-service permissions).
     */
    private function isOwnStudent(User $user, Student $student): bool
    {
        return $student->user_id === $user->id;
    }
}
