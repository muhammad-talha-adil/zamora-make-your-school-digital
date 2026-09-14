<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * `student.test@school.com` / `guardian.test@school.com` exist as bare
 * fallback accounts from `UsersSeeder` (no student/guardian record linked),
 * so every family portal screen would show "no student linked" for them —
 * fine for demonstrating that state on purpose, but not useful for actually
 * checking the portal works.
 *
 * Runs last, once real students/guardians/fee vouchers/exam results already
 * exist: repoints one already-fully-seeded student and one already-linked
 * guardian onto these fixed, memorable emails (password stays `123456`, same
 * as every other seeded account) — reusing all their real generated data for
 * free instead of building a parallel fixture from scratch.
 */
class TestLoginFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $student = Student::whereHas('user')->with('user')->first();

        if ($student?->user) {
            $this->freeUpEmail('student.test@school.com', $student->user->id);
            $student->user->update(['email' => 'student.test@school.com', 'username' => 'student_test']);
        }

        $guardian = Guardian::whereHas('user')
            ->whereHas('students')
            ->with('user')
            ->first();

        if ($guardian?->user) {
            $this->freeUpEmail('guardian.test@school.com', $guardian->user->id);
            $guardian->user->update(['email' => 'guardian.test@school.com', 'username' => 'guardian_test']);
        }

        $this->command?->info('Test login fixtures repointed onto real records.');
    }

    /**
     * `UsersSeeder` already created a bare placeholder account on this
     * email as a fallback — delete it before a real record claims the same
     * address, or the unique constraint rejects the update.
     */
    private function freeUpEmail(string $email, int $exceptUserId): void
    {
        User::where('email', $email)->where('id', '!=', $exceptUserId)->delete();
    }
}
