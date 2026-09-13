<?php

namespace App\Services\Student;

/**
 * The logins created during one admission, held until the page renders.
 *
 * Every admission creates an account for the child, and often one for the
 * guardian. The password was generated, hashed into the row, and **thrown
 * away**: never shown, never printed, never emailed, and — where the family gave
 * no address — sent to a school-generated mailbox nobody receives mail at.
 *
 * So the account existed and nobody on earth could sign in to it, which is the
 * whole student portal.
 *
 * This carries the plaintext from the moment it is generated to the moment the
 * admission slip prints it, and no further. It is bound `scoped`, so it lives
 * for one request and is never written anywhere.
 */
class AdmissionCredentials
{
    /** @var array<int, array{for: string, name: string, username: string, password: string}> */
    private array $logins = [];

    public function record(string $for, string $name, string $username, string $password): void
    {
        $this->logins[] = [
            'for' => $for,
            'name' => $name,
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * @return array<int, array{for: string, name: string, username: string, password: string}>
     */
    public function all(): array
    {
        return $this->logins;
    }

    public function isEmpty(): bool
    {
        return $this->logins === [];
    }

    /**
     * Hands them over and forgets them.
     *
     * Read once, by whatever is about to show them. Anything that asks again
     * gets nothing, which is the point.
     *
     * @return array<int, array{for: string, name: string, username: string, password: string}>
     */
    public function take(): array
    {
        $logins = $this->logins;
        $this->logins = [];

        return $logins;
    }
}
