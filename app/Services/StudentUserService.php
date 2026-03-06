<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Str;

class StudentUserService
{
    /**
     * Create a user account for a student with proper username generation and role assignment.
     * 
     * Username Format: STU-{student_code}
     * Example: STU-000001
     *
     * @param string $studentName The student's full name
     * @param string|null $studentEmail Optional email provided by student
     * @param string $studentCode The student's unique system code (e.g., STU-000001)
     * @return User The created user model
     */
    public function createStudentUser(string $studentName, ?string $studentEmail, string $studentCode): User
    {
        // Generate role-prefixed username
        $username = $this->generateStudentUsername($studentCode);

        // Generate email based on processed name or use provided email
        $email = $this->generateUniqueEmail($studentName, $studentEmail);

        // Generate a secure random password
        $password = $this->generateSecurePassword();

        // Create the user account
        $user = User::create([
            'name' => $studentName,
            'email' => $email,
            'username' => $username,
            'password' => bcrypt($password),
            'is_active' => true,
        ]);

        // Assign student role to the user
        $this->assignStudentRole($user);

        return $user;
    }

    /**
     * Generate a role-prefixed username for students.
     *
     * Format: STU-{student_code}
     * Example: STU-000001
     *
     * @param string $studentCode
     * @return string
     */
    public function generateStudentUsername(string $studentCode): string
    {
        // Ensure student_code has STU- prefix
        if (Str::startsWith($studentCode, 'STU-')) {
            return $studentCode;
        }
        
        return 'STU-' . $studentCode;
    }

    /**
     * Generate a secure random password for student accounts.
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
     * Generate a unique email address for the student.
     *
     * @param string $studentName
     * @param string|null $providedEmail
     * @return string
     */
    protected function generateUniqueEmail(string $studentName, ?string $providedEmail): string
    {
        // If a valid email is provided, use it (with duplicate handling)
        if (!empty($providedEmail) && filter_var($providedEmail, FILTER_VALIDATE_EMAIL)) {
            $baseEmail = strtolower(trim($providedEmail));

            if (!$this->emailExists($baseEmail)) {
                return $baseEmail;
            }

            return $this->createUniqueEmail($baseEmail);
        }

        // Generate email from student name
        $processedName = preg_replace('/\s+/', '', strtolower($studentName));
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
     * Assign the student role to a user.
     *
     * @param User $user
     * @return UserRole
     */
    protected function assignStudentRole(User $user): UserRole
    {
        $studentRole = Role::where('name', 'student')->first();

        if (!$studentRole) {
            $studentRole = Role::create([
                'name' => 'student',
                'slug' => 'student',
                'label' => 'Student',
                'scope_level' => 'SELF',
                'is_active' => true,
            ]);
        }

        return UserRole::create([
            'user_id' => $user->id,
            'role_id' => $studentRole->id,
            'campus_id' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Check if a student user account exists for a given student code.
     *
     * @param string $studentCode
     * @return bool
     */
    public function studentUserExists(string $studentCode): bool
    {
        $username = $this->generateStudentUsername($studentCode);
        return User::where('username', $username)->exists();
    }

    /**
     * Get the student user by student code.
     *
     * @param string $studentCode
     * @return User|null
     */
    public function getStudentUserByStudentCode(string $studentCode): ?User
    {
        $username = $this->generateStudentUsername($studentCode);
        return User::where('username', $username)->first();
    }

    /**
     * Get the student user by admission number (legacy support).
     *
     * @param string $admissionNo
     * @return User|null
     * @deprecated Use getStudentUserByStudentCode instead
     */
    public function getStudentUserByAdmissionNo(string $admissionNo): ?User
    {
        return User::where('username', $admissionNo)->first();
    }
}
