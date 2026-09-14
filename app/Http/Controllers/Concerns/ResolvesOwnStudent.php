<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolving "my own record" with no route parameter.
 *
 * Generalises the pattern proven in
 * `StudentLeaveController::ownStudents()`/`assertMayActFor()`: a student
 * signs in and reads their own record directly, a guardian signs in to the
 * same account type and reads their ward's — there is no separate guardian
 * portal, one endpoint serves both. Every portal controller should resolve
 * "who am I looking at" through here rather than re-deriving it.
 */
trait ResolvesOwnStudent
{
    /**
     * The children this viewer may read records for: themselves, or their
     * wards.
     *
     * @return Collection<int, Student>
     */
    protected function ownStudents(User $user): Collection
    {
        if ($user->student) {
            return new Collection([$user->student]);
        }

        if ($user->guardian) {
            return $user->guardian->students()->with('user:id,name')->get();
        }

        return new Collection;
    }

    /**
     * The one child a portal screen should show right now.
     *
     * Defaults to the first (for most families, only) child, but accepts an
     * optional `?student_id=` so a guardian with more than one child can pick
     * which one's records to view — validated against the caller's own
     * children before anything is queried against it, never trusted as-is.
     */
    protected function resolveOwnStudent(User $user, ?int $requestedStudentId = null): Student
    {
        $students = $this->ownStudents($user);

        if ($students->isEmpty()) {
            abort(403, 'No student record is linked to this account.');
        }

        if ($requestedStudentId !== null) {
            $match = $students->firstWhere('id', $requestedStudentId);

            if (! $match) {
                abort(403, 'That student is not linked to this account.');
            }

            return $match;
        }

        return $students->first();
    }
}
