<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'remember_token',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    /**
     * @return HasOne<Student, $this>
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * @return HasOne<Guardian, $this>
     */
    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    // Permission & Role Helpers
    //
    // `hasRole()`, `hasAnyRole()`, `can()` and the `roles` / `permissions`
    // relations all come from Spatie's HasRoles trait, which also caches the
    // lookups. Only the project-specific helpers live here.

    /**
     * Whether the user holds an ability, directly or through a role.
     *
     * A thin alias over Spatie's `hasPermissionTo()` that swallows the
     * exception thrown for an ability that does not exist yet, so a check
     * against a not-yet-seeded permission denies access instead of erroring.
     */
    public function hasPermission(string $permission): bool
    {
        try {
            return $this->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    /**
     * Roles that may act across every campus.
     *
     * Campus-scoped screens use this to decide whether to filter by campus at
     * all; see `isCampusRestricted()` for the inverse.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole(['developer', 'owner', 'super_admin']);
    }

    /**
     * Whether the user only ever sees their own campus.
     */
    public function isCampusRestricted(): bool
    {
        return ! $this->isSuperAdmin();
    }

    /**
     * The developer role owns subscription and system tooling, which is
     * deliberately kept out of the owner's reach.
     */
    public function isDeveloper(): bool
    {
        return $this->hasRole('developer');
    }

    /**
     * The classes this user teaches, in the sessions they teach them.

     *
     * @return HasManyThrough<TeacherClassAssignment, StaffProfile, $this>
     */
    public function teachingAssignments(): HasManyThrough
    {
        return $this->hasManyThrough(
            TeacherClassAssignment::class,
            StaffProfile::class,
            'user_id',
            'staff_profile_id'
        );
    }

    /**
     * The campus this user belongs to, if they are restricted to one.
     *
     * Taken from their staff record, which is where a member of staff's campus
     * has always been kept.
     */
    public function campusId(): ?int
    {
        return $this->staffProfile?->campus_id;
    }

    /**
     * Whether this user only sees the classes they have been given.
     *
     * A teacher does. A campus admin does not — they see their whole campus —
     * and a head teacher is treated as campus-wide until wings are modelled,
     * which is deliberately the more restrictive of the two readings we can
     * support today.
     */
    public function isClassRestricted(): bool
    {
        return $this->hasRole('teacher')
            && ! $this->isSuperAdmin()
            && ! $this->hasAnyRole(['campus_admin', 'head_teacher']);
    }

    /**
     * Whether this user is responsible for a class, or a section of one.
     *
     * A teacher given the whole class covers every section in it, which is how
     * a school without sections is arranged.
     */
    public function teachesSection(?int $classId, ?int $sectionId, ?int $sessionId = null): bool
    {
        if (! $classId) {
            return false;
        }

        return $this->teachingAssignments()
            ->active()
            ->coveringSection($classId, $sectionId)
            ->when($sessionId, fn ($query) => $query->where('teacher_class_assignments.session_id', $sessionId))
            ->exists();
    }

    /**
     * Whether this user is the class teacher of a section.
     */
    public function isClassTeacherOf(?int $classId, ?int $sectionId, ?int $sessionId = null): bool
    {
        if (! $classId) {
            return false;
        }

        return $this->teachingAssignments()
            ->active()
            ->classTeacher()
            ->coveringSection($classId, $sectionId)
            ->when($sessionId, fn ($query) => $query->where('teacher_class_assignments.session_id', $sessionId))
            ->exists();
    }
}
