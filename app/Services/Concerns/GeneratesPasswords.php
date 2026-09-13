<?php

namespace App\Services\Concerns;

use Illuminate\Support\Str;

/**
 * Passwords for the accounts this system creates.
 *
 * There were two of these, one in `StudentUserService` and an identical copy in
 * `GuardianService`, and both built the password out of `rand()`:
 *
 * ```php
 * $password .= chr(rand(97, 122));
 * $password .= $characters[rand(0, strlen($characters) - 1)];
 * ```
 *
 * `rand()` is not a cryptographic generator. Its state is recoverable from a
 * modest run of its output, so a person who could see a handful of passwords
 * this school had issued could work out the rest. These passwords open a
 * child's record.
 *
 * `Str::password()` draws from the same character classes and uses
 * `random_int()` underneath, which is the CSPRNG.
 */
trait GeneratesPasswords
{
    /**
     * A password a family can be handed on a printed slip.
     *
     * Symbols are left out deliberately. This is read off a piece of paper and
     * typed by somebody who may be doing it on a phone keypad; a password with
     * `^` in it gets written down wrong, and a school that cannot log a parent
     * in resets it to something worse.
     */
    public function generateSecurePassword(int $length = 12): string
    {
        return Str::password($length, letters: true, numbers: true, symbols: false, spaces: false);
    }
}
