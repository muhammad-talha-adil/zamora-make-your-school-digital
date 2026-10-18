<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, TwoFactorAuthenticatable {
        HasRoles::assignRole as private baseAssignRole;
        HasRoles::removeRole as private baseRemoveRole;
        HasRoles::syncRoles as private baseSyncRoles;
    }

    /**
     * Only account-level attributes worth an audit trail, and only when they
     * actually change. Role changes are not a dirty attribute on this model —
     * they live on the `model_has_roles` pivot — so {@see assignRole()},
     * {@see removeRole()} and {@see syncRoles()} below log them explicitly.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'username', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "user account {$eventName}");
    }

    public function assignRole(...$roles): static
    {
        $before = $this->getRoleNames()->all();

        $this->guardCampusAdminChange($roles);

        $this->baseAssignRole(...$roles);

        $this->logRoleChange($before);

        return $this;
    }

    public function removeRole(...$role): static
    {
        $before = $this->getRoleNames()->all();

        $this->guardCampusAdminChange($role, removing: true);

        $this->baseRemoveRole(...$role);

        $this->logRoleChange($before);

        return $this;
    }

    public function syncRoles(...$roles): static
    {
        $before = $this->getRoleNames()->all();

        $this->guardCampusAdminChange($roles);

        $this->baseSyncRoles(...$roles);

        $this->logRoleChange($before);

        return $this;
    }

    /**
     * `campus_admin` is not an ordinary designation role: only an
     * owner/developer may grant or remove it, and a campus may only ever
     * have one active holder. Every role-change path on this model
     * (`assignRole`/`removeRole`/`syncRoles`) routes through here, rather
     * than each call site re-implementing the rule — the alternative lets a
     * forgotten call site quietly hand out admin access.
     *
     * The currently-authenticated user is who the rule is checked against,
     * not `$this` — e.g. a console/seeder context has no authenticated user
     * and is exempt, matching how the rest of the app's "owner only" checks
     * already work.
     *
     * @param  array<int, mixed>  $roles
     */
    private function guardCampusAdminChange(array $roles, bool $removing = false): void
    {
        $flat = collect($roles)->flatten()->map(
            fn ($role) => $role instanceof Role ? $role->name : $role
        );

        if (! $flat->contains('campus_admin')) {
            return;
        }

        $actor = auth()->user();

        if (! $actor) {
            return;
        }

        abort_unless($actor->isSuperAdmin(), 403, 'Only the owner can assign or change a Campus Admin.');

        if ($removing) {
            return;
        }

        $campusId = $this->staffProfile?->campus_id;

        if (! $campusId) {
            return;
        }

        $existingAdmin = self::role('campus_admin')
            ->whereHas('staffProfile', fn ($q) => $q->where('campus_id', $campusId)->where('is_active', true))
            ->where('id', '!=', $this->id)
            ->first();

        if ($existingAdmin) {
            throw ValidationException::withMessages([
                'designation_id' => "This campus already has an admin ({$existingAdmin->name}). Deactivate them or give them another role first.",
            ]);
        }
    }

    /**
     * Records a role change as its own activity entry, since role assignment
     * happens through Spatie Permission's pivot table rather than a column on
     * this model that {@see LogsActivity} could pick up on its own.
     *
     * @param  array<int, string>  $before
     */
    private function logRoleChange(array $before): void
    {
        $after = $this->getRoleNames()->all();

        if ($before === $after) {
            return;
        }

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['before' => $before, 'after' => $after])
            ->log('user roles changed');
    }

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
        return $this->hasAnyRole(['developer', 'owner']);
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
     * The campus this user is currently scoped to.
     *
     * For campus-restricted staff this is simply their own campus. For a
     * school-wide user (owner/developer) it is whichever campus they picked
     * from the header switcher (`viewing_campus_id` in session) — there is no
     * "all campuses" option, so this is the single source of truth every
     * campus-scoping check in the app reads from.
     */
    public function campusId(): ?int
    {
        if ($this->isSuperAdmin()) {
            $viewingCampusId = session('viewing_campus_id');

            return $viewingCampusId !== null ? (int) $viewingCampusId : null;
        }

        return $this->staffProfile?->campus_id;
    }

    /**
     * Whether this user only sees the classes they have been given.
     *
     * A teacher does. A campus admin does not — they see their whole campus.
     * A teacher can be lifted to the same campus-wide view without a second
     * role for it, by granting them the `attendance.view.campus-wide`
     * permission directly (Staff > People > a teacher > Permissions) — the
     * same path that used to require making them a "head_teacher".
     */
    public function isClassRestricted(): bool
    {
        return $this->hasRole('teacher')
            && ! $this->isSuperAdmin()
            && ! $this->hasRole('campus_admin')
            && ! $this->hasPermissionTo('attendance.view.campus-wide');
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
