<?php

/**
 * spatie/laravel-activitylog wiring: traited models write an {@see Activity}
 * row with the right causer/subject/description, and the owner/developer-only
 * viewing screen enforces the same restriction the middleware-level checks
 * elsewhere in the app use.
 */

use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeVoucher;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentStatus;
use App\Models\Subscription;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\FeeWorld;
use Tests\Support\StaffWorld;

function makeActivityLogUserWithRole(string $role): User
{
    Role::firstOrCreate(
        ['name' => $role, 'guard_name' => 'web'],
        ['label' => ucfirst($role), 'scope_level' => Role::SCOPE_SYSTEM, 'is_active' => true]
    );

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('logs a student status change with the right causer, subject and description', function () {
    $world = FeeWorld::make();
    $causer = makeActivityLogUserWithRole('owner');

    $this->actingAs($causer);

    $world->student->update(['student_status_id' => $world->student->student_status_id]);
    // A no-op update should not create noise; change to a genuinely different value.
    $otherStatusId = StudentStatus::create(['name' => 'Withdrawn'])->id;
    $world->student->update(['student_status_id' => $otherStatusId]);

    $activity = Activity::query()->where('subject_type', Student::class)->where('subject_id', $world->student->id)->orderByDesc('id')->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($causer->id);
    expect($activity->causer_type)->toBe(User::class);
    expect($activity->description)->toBe('student updated');
    expect($activity->attribute_changes->get('attributes'))->toHaveKey('student_status_id');
});

it('logs a fee voucher status change', function () {
    $world = FeeWorld::make();
    $world->structureWithAllFrequencies();
    $causer = makeActivityLogUserWithRole('owner');

    $this->actingAs($causer);

    $world->generate(4);
    $voucher = $world->voucherFor(4);
    $voucher->update(['status' => VoucherStatus::PAID]);

    $activity = Activity::query()
        ->where('subject_type', FeeVoucher::class)
        ->where('subject_id', $voucher->id)
        ->where('description', 'fee voucher updated')
        ->orderByDesc('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($causer->id);
});

it('logs a staff profile salary change', function () {
    $world = StaffWorld::make();
    $causer = makeActivityLogUserWithRole('owner');

    $this->actingAs($causer);

    $staff = $world->person('Jane Teacher');
    $staff->update(['basic_salary' => 75000]);

    $activity = Activity::query()
        ->where('subject_type', StaffProfile::class)
        ->where('subject_id', $staff->id)
        ->orderByDesc('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('staff profile updated');
    expect($activity->attribute_changes->get('attributes'))->toHaveKey('basic_salary');
});

it('logs a subscription status change without a manual file log call', function () {
    $developer = makeActivityLogUserWithRole('developer');
    $this->actingAs($developer);

    Subscription::current()->update(['status' => 'suspended']);

    $activity = Activity::query()->where('subject_type', Subscription::class)->orderByDesc('id')->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('subscription updated');
    expect($activity->causer_id)->toBe($developer->id);

    $controller = file_get_contents(app_path('Http/Controllers/Settings/SubscriptionController.php'));
    expect($controller)->not->toContain('Log::info');
});

it('logs a user role change as its own activity entry', function () {
    $admin = makeActivityLogUserWithRole('developer');
    $this->actingAs($admin);

    $user = User::factory()->create();
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web'], ['label' => 'Teacher', 'scope_level' => Role::SCOPE_CAMPUS, 'is_active' => true]);

    $user->assignRole('teacher');

    $activity = Activity::query()
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('description', 'user roles changed')
        ->orderByDesc('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->properties->get('after'))->toBe(['teacher']);
});

it('lets an owner and a developer view the activity log', function (string $role) {
    $user = makeActivityLogUserWithRole($role);

    $this->actingAs($user)->get(route('activity-log.index'))->assertOk();
})->with(['owner', 'developer']);

it('turns away anyone else from the activity log', function (string $role) {
    $user = makeActivityLogUserWithRole($role);

    $this->actingAs($user)->get(route('activity-log.index'))->assertForbidden();
})->with(['campus_admin', 'teacher', 'super_admin']);

it('filters the activity log by subject type', function () {
    $world = FeeWorld::make();
    $causer = makeActivityLogUserWithRole('owner');
    $this->actingAs($causer);

    $otherStatusId = StudentStatus::create(['name' => 'Withdrawn'])->id;
    $world->student->update(['student_status_id' => $otherStatusId]);

    $response = $this->actingAs($causer)->get(route('activity-log.index', ['subject_type' => Student::class]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/ActivityLog')
        ->where('activities.data.0.subject_type', 'Student'));
});
