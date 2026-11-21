<?php

namespace App\Services\Staff;

use App\Models\Role;
use App\Models\User;
use App\Services\Concerns\GeneratesPasswords;
use App\Services\SchoolEmailService;

/**
 * Gives a staff member a way to sign in.
 *
 * Hiring used to set every password to the same fixed word and show nothing,
 * so nobody could tell a new member how to log in. The password is now random
 * and handed back once, to be read off the screen, never stored in plain text.
 */
class StaffLoginService
{
    use GeneratesPasswords;

    public function __construct(private SchoolEmailService $emails) {}

    /**
     * A free email to log in with: the one given, or one made from the name.
     */
    public function emailFor(string $name, ?string $given, ?int $ignoreUserId = null): string
    {
        if ($given) {
            return $this->emails->ensureUniqueProvidedEmail($given, $ignoreUserId);
        }

        return $this->emails->generateUniqueEmailFromName($name, $ignoreUserId);
    }

    /**
     * Sets a fresh random password and optionally the role.
     *
     * @return array{name: string, username: string, email: string, password: string}
     */
    public function issue(User $user, ?string $email = null, ?string $role = null): array
    {
        $password = $this->generateSecurePassword();

        $user->forceFill([
            'email' => $email ?: $user->email,
            'password' => bcrypt($password),
            'is_active' => true,
        ])->save();

        if ($role !== null && Role::where('name', $role)->exists()) {
            $user->assignRole($role);
        }

        return [
            'name' => $user->name,
            'username' => (string) $user->username,
            'email' => $user->email,
            'password' => $password,
        ];
    }
}
