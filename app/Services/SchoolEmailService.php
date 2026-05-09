<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;

class SchoolEmailService
{
    public function getManagedDomain(): string
    {
        $schoolName = School::query()->value('name');

        if (! is_string($schoolName) || trim($schoolName) === '') {
            return 'school.com';
        }

        $words = preg_split('/[^A-Za-z0-9]+/', trim($schoolName)) ?: [];
        $initials = collect($words)
            ->filter(fn ($word) => $word !== '')
            ->map(fn ($word) => strtolower(substr($word, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials.'school.com' : 'school.com';
    }

    public function generateUniqueEmailFromName(string $name, ?int $ignoreUserId = null): string
    {
        $localPart = preg_replace('/[^a-z0-9]+/', '', strtolower($name)) ?: 'student';
        $baseEmail = $localPart.'@'.$this->getManagedDomain();

        if (! $this->emailExists($baseEmail, $ignoreUserId)) {
            return $baseEmail;
        }

        return $this->createUniqueEmail($baseEmail, $ignoreUserId);
    }

    public function isManagedEmail(?string $email): bool
    {
        if (! $email || ! str_contains($email, '@')) {
            return false;
        }

        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

        return in_array($domain, [
            strtolower($this->getManagedDomain()),
            'school.com',
        ], true);
    }

    public function ensureUniqueProvidedEmail(string $email, ?int $ignoreUserId = null): string
    {
        $normalizedEmail = strtolower(trim($email));

        if (! $this->emailExists($normalizedEmail, $ignoreUserId)) {
            return $normalizedEmail;
        }

        return $this->createUniqueEmail($normalizedEmail, $ignoreUserId);
    }

    private function emailExists(string $email, ?int $ignoreUserId = null): bool
    {
        return User::query()
            ->where('email', $email)
            ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->exists();
    }

    private function createUniqueEmail(string $baseEmail, ?int $ignoreUserId = null): string
    {
        $counter = 1;
        $emailParts = explode('@', $baseEmail);
        $username = $emailParts[0];
        $domain = $emailParts[1] ?? $this->getManagedDomain();

        while (true) {
            $newEmail = $username.$counter.'@'.$domain;

            if (! $this->emailExists($newEmail, $ignoreUserId)) {
                return $newEmail;
            }

            $counter++;
        }
    }
}
