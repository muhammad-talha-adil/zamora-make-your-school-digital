<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Str;

class GuardianService
{
    /**
     * Create a user account for a guardian with proper username generation and role assignment.
     * 
     * Username Format: GRD-{last_6_digits_of_phone}
     * Example: GRD-123456
     *
     * @param Guardian $guardian The guardian model instance
     * @param string $guardianName The guardian's full name
     * @param string|null $guardianEmail Optional email provided by guardian
     * @return User The created user model
     */
    public function createGuardianUser(Guardian $guardian, string $guardianName, ?string $guardianEmail): User
    {
        // Generate role-prefixed username based on phone number
        $username = $this->generateGuardianUsername($guardian->phone);

        // Generate email based on processed name or use provided email
        $email = $this->generateUniqueEmail($guardianName, $guardianEmail);

        // Generate a secure random password
        $password = $this->generateSecurePassword();

        // Create the user account
        $user = User::create([
            'name' => $guardianName,
            'email' => $email,
            'username' => $username,
            'password' => bcrypt($password),
            'is_active' => true,
        ]);

        // Link the user to the guardian
        $guardian->user_id = $user->id;
        $guardian->save();

        // Assign guardian role to the user
        $this->assignGuardianRole($user);

        return $user;
    }

    /**
     * Find a guardian by phone number or create a new one with user account.
     *
     * @param string|null $phone The phone number to search for
     * @param array $data Guardian data (name, email, cnic, occupation, address)
     * @return Guardian The found or newly created guardian
     */
    public function findOrCreateByPhone(?string $phone, array $data): Guardian
    {
        // Clean the phone number
        $cleanPhone = !empty($phone) ? preg_replace('/[^0-9]/', '', $phone) : null;

        // Try to find existing guardian by phone
        if ($cleanPhone) {
            $guardian = Guardian::where('phone', $cleanPhone)->first();
            if ($guardian) {
                return $guardian;
            }
        }

        // Create new guardian
        $guardian = Guardian::create([
            'phone' => $cleanPhone,
            'cnic' => $data['cnic'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        // Create user account for the guardian
        $this->createGuardianUser($guardian, $data['name'], $data['email'] ?? null);

        return $guardian;
    }

    /**
     * Create a new guardian without phone number lookup.
     *
     * @param array $data Guardian data
     * @return Guardian
     */
    public function createGuardian(array $data): Guardian
    {
        $guardian = Guardian::create([
            'phone' => $data['phone'] ?? null,
            'cnic' => $data['cnic'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        // Create user account for the guardian
        $this->createGuardianUser($guardian, $data['name'], $data['email'] ?? null);

        return $guardian;
    }

    /**
     * Generate a role-prefixed username for guardians.
     *
     * Format: GRD-{last_6_digits_of_phone}
     * Example: GRD-123456
     *
     * @param string|null $phone
     * @return string
     */
    public function generateGuardianUsername(?string $phone): string
    {
        // If no phone provided, generate a random username
        if (empty($phone)) {
            return 'GRD-' . strtoupper(Str::random(8));
        }

        // Extract last 6 digits from phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $last6Digits = substr($cleanPhone, -6);

        // If phone is shorter than 6 digits, pad with zeros
        if (strlen($last6Digits) < 6) {
            $last6Digits = str_pad($last6Digits, 6, '0', STR_PAD_LEFT);
        }

        return 'GRD-' . $last6Digits;
    }

    /**
     * Generate a secure random password for guardian accounts.
     *
     * @param int $length Password length
     * @return string Generated password
     */
    public function generateSecurePassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        // Ensure at least one of each required character type
        $password .= chr(rand(97, 122)); // lowercase
        $password .= chr(rand(65, 90));   // uppercase
        $password .= chr(rand(48, 57));   // number
        $password .= chr(rand(33, 47));    // special char

        // Fill remaining characters
        for ($i = 4; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }

    /**
     * Generate a unique email address for the guardian.
     *
     * @param string $guardianName
     * @param string|null $providedEmail
     * @return string
     */
    protected function generateUniqueEmail(string $guardianName, ?string $providedEmail): string
    {
        // If a valid email is provided, use it (with duplicate handling)
        if (!empty($providedEmail) && filter_var($providedEmail, FILTER_VALIDATE_EMAIL)) {
            $baseEmail = strtolower(trim($providedEmail));

            if (!$this->emailExists($baseEmail)) {
                return $baseEmail;
            }

            return $this->createUniqueEmail($baseEmail);
        }

        // Generate email from guardian name
        $processedName = preg_replace('/\s+/', '', strtolower($guardianName));
        $baseEmail = $processedName . '@school.com';

        if (!$this->emailExists($baseEmail)) {
            return $baseEmail;
        }

        return $this->createUniqueEmail($baseEmail);
    }

    /**
     * Check if an email already exists in the users table.
     *
     * @param string $email
     * @return bool
     */
    protected function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Create a unique email by appending incremental numbers.
     *
     * @param string $baseEmail
     * @return string
     */
    protected function createUniqueEmail(string $baseEmail): string
    {
        $counter = 1;
        $emailParts = explode('@', $baseEmail);
        $username = $emailParts[0];
        $domain = $emailParts[1] ?? 'school.com';

        while (true) {
            $newEmail = $username . $counter . '@' . $domain;
            if (!$this->emailExists($newEmail)) {
                return $newEmail;
            }
            $counter++;
        }
    }

    /**
     * Assign the guardian role to a user.
     *
     * @param User $user
     * @return UserRole
     */
    protected function assignGuardianRole(User $user): UserRole
    {
        $guardianRole = Role::where('name', 'guardian')->first();

        if (!$guardianRole) {
            $guardianRole = Role::create([
                'name' => 'guardian',
                'slug' => 'guardian',
                'label' => 'Guardian',
                'scope_level' => 'FAMILY',
                'is_active' => true,
            ]);
        }

        return UserRole::create([
            'user_id' => $user->id,
            'role_id' => $guardianRole->id,
            'campus_id' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Check if a guardian user account exists for a given phone number.
     *
     * @param string|null $phone
     * @return bool
     */
    public function guardianUserExists(?string $phone): bool
    {
        $username = $this->generateGuardianUsername($phone);
        return User::where('username', $username)->exists();
    }

    /**
     * Get the guardian user by phone number.
     *
     * @param string|null $phone
     * @return User|null
     */
    public function getGuardianUserByPhone(?string $phone): ?User
    {
        $username = $this->generateGuardianUsername($phone);
        return User::where('username', $username)->first();
    }

    /**
     * Get all guardian users (for reporting purposes).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllGuardianUsers()
    {
        $guardianRole = Role::where('name', 'guardian')->first();
        
        if (!$guardianRole) {
            return collect();
        }

        return User::whereHas('roles', function ($query) use ($guardianRole) {
            $query->where('role_id', $guardianRole->id);
        })->get();
    }

    /**
     * Update an existing guardian with new data.
     *
     * @param Guardian $guardian
     * @param array $data
     * @return Guardian
     */
    public function updateGuardian(Guardian $guardian, array $data): Guardian
    {
        $guardian->update([
            'cnic' => $data['cnic'] ?? $guardian->cnic,
            'occupation' => $data['occupation'] ?? $guardian->occupation,
            'address' => $data['address'] ?? $guardian->address,
        ]);

        // Update user name if provided
        if (isset($data['name']) && $guardian->user) {
            $guardian->user->update(['name' => $data['name']]);
        }

        // Update user email if provided
        if (isset($data['email']) && $guardian->user && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $guardian->user->update(['email' => $data['email']]);
        }

        return $guardian->fresh();
    }
}
