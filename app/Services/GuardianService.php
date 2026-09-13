<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\Role;
use App\Models\User;
use App\Services\Concerns\GeneratesPasswords;
use App\Services\Student\AdmissionCredentials;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class GuardianService
{
    use GeneratesPasswords;

    public function __construct(
        protected SchoolEmailService $schoolEmailService,
        protected AdmissionCredentials $credentials
    ) {}

    /**
     * Create a user account for a guardian with proper username generation and role assignment.
     *
     * Username Format: GRD-{last_6_digits_of_phone}
     * Example: GRD-123456
     *
     * @param  Guardian  $guardian  The guardian model instance
     * @param  string  $guardianName  The guardian's full name
     * @param  string|null  $guardianEmail  Optional email provided by guardian
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

        // Handed to the family on the admission slip, and held nowhere else.
        $this->credentials->record('guardian', $guardianName, $username, $password);

        return $user;
    }

    /**
     * Find a guardian by phone number or create a new one with user account.
     *
     * @param  string|null  $phone  The phone number to search for
     * @param  array  $data  Guardian data (name, email, cnic, occupation, address)
     * @return Guardian The found or newly created guardian
     */
    public function findOrCreateByPhone(?string $phone, array $data): Guardian
    {
        // Clean the phone number
        $cleanPhone = ! empty($phone) ? preg_replace('/[^0-9]/', '', $phone) : null;

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
     * @param  array  $data  Guardian data
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
     */
    public function generateGuardianUsername(?string $phone): string
    {
        // If no phone provided, generate a random username
        if (empty($phone)) {
            return 'GRD-'.strtoupper(Str::random(8));
        }

        // Extract last 6 digits from phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $last6Digits = substr($cleanPhone, -6);

        // If phone is shorter than 6 digits, pad with zeros
        if (strlen($last6Digits) < 6) {
            $last6Digits = str_pad($last6Digits, 6, '0', STR_PAD_LEFT);
        }

        return 'GRD-'.$last6Digits;
    }

    /**
     * Generate a unique email address for the guardian.
     */
    protected function generateUniqueEmail(string $guardianName, ?string $providedEmail): string
    {
        // If a valid email is provided, use it (with duplicate handling)
        if (! empty($providedEmail) && filter_var($providedEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->schoolEmailService->ensureUniqueProvidedEmail($providedEmail);
        }

        return $this->schoolEmailService->generateUniqueEmailFromName($guardianName);
    }

    /**
     * Assign the guardian role to a user.
     */
    protected function assignGuardianRole(User $user): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'guardian', 'guard_name' => 'web'],
            [
                'label' => 'Guardian',
                'scope_level' => Role::SCOPE_SELF,
                'is_active' => true,
            ]
        );

        $user->assignRole($role);
    }

    /**
     * Check if a guardian user account exists for a given phone number.
     */
    public function guardianUserExists(?string $phone): bool
    {
        $username = $this->generateGuardianUsername($phone);

        return User::where('username', $username)->exists();
    }

    /**
     * Get the guardian user by phone number.
     */
    public function getGuardianUserByPhone(?string $phone): ?User
    {
        $username = $this->generateGuardianUsername($phone);

        return User::where('username', $username)->first();
    }

    /**
     * Get all guardian users (for reporting purposes).
     *
     * The guardian accounts, or an empty collection where the role does not
     * exist yet. Typed to what both branches actually return.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function getAllGuardianUsers()
    {
        $guardianRole = Role::where('name', 'guardian')->first();

        if (! $guardianRole) {
            return collect();
        }

        return User::whereHas('roles', function ($query) use ($guardianRole) {
            $query->where('role_id', $guardianRole->id);
        })->get();
    }

    /**
     * Update an existing guardian with new data.
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

        if (isset($data['email']) && $guardian->user && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $guardian->user->update([
                'email' => $this->schoolEmailService->ensureUniqueProvidedEmail($data['email'], $guardian->user->id),
            ]);
        } elseif (isset($data['name']) && $guardian->user && $this->schoolEmailService->isManagedEmail($guardian->user->email)) {
            $guardian->user->update([
                'email' => $this->schoolEmailService->generateUniqueEmailFromName($data['name'], $guardian->user->id),
            ]);
        }

        return $guardian->fresh() ?? $guardian;
    }
}
