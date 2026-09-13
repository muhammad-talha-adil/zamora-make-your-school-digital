<?php

use App\Models\Campus;
use App\Models\CampusType;
use App\Models\Permission;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * The campus, campus type, class, section and subject controllers were built
 * with no authorization at all — any logged-in user could create, edit or
 * delete the school's structure. These tests pin the permissions each action
 * is now gated on (see the new CampusPolicy, CampusTypePolicy,
 * SchoolClassPolicy, SectionPolicy and SubjectPolicy).
 */
function userWithPermission(string $permission, bool $withPermission = true): User
{
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

    $user = User::factory()->create();

    if ($withPermission) {
        $user->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    return $user;
}

test('a user without school.campus.manage cannot create, update or delete a campus', function () {
    $user = userWithPermission('school.campus.manage', withPermission: false);
    $type = CampusType::create(['name' => 'Main']);
    $campus = Campus::create(['name' => 'North Campus', 'campus_type_id' => $type->id, 'is_active' => true]);

    $this->actingAs($user)
        ->post(route('campuses.store'), ['name' => 'South Campus', 'campus_type_id' => $type->id])
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('campuses.update', $campus), ['name' => 'Renamed', 'campus_type_id' => $type->id])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('campuses.destroy', $campus))
        ->assertForbidden();
});

test('a user with school.campus.manage can create a campus', function () {
    $user = userWithPermission('school.campus.manage');
    $type = CampusType::create(['name' => 'Main']);

    $response = $this->actingAs($user)->post(route('campuses.store'), [
        'name' => 'South Campus',
        'campus_type_id' => $type->id,
    ]);

    $response->assertSessionHasNoErrors();
    expect(Campus::where('name', 'South Campus')->exists())->toBeTrue();
});

test('a user without school.profile.manage cannot update the school profile', function () {
    $user = userWithPermission('school.profile.manage', withPermission: false);
    School::create(['name' => 'Original School']);

    $this->actingAs($user)
        ->get(route('school-profile.show'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('school-profile.update'), ['name' => 'Renamed School'])
        ->assertForbidden();
});

test('a user without academics.class.manage cannot create, update or delete a class', function () {
    $user = userWithPermission('academics.class.manage', withPermission: false);
    $class = SchoolClass::create(['name' => 'Class 1', 'code' => 'C1', 'is_active' => true]);

    $this->actingAs($user)
        ->post(route('school-classes.store'), ['name' => 'Class 2', 'code' => 'C2'])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('school-classes.update', $class), ['name' => 'Renamed', 'code' => 'C1'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('school-classes.destroy', $class))
        ->assertForbidden();
});

test('a user without academics.class.manage cannot create, update or delete a section', function () {
    $user = userWithPermission('academics.class.manage', withPermission: false);
    $class = SchoolClass::create(['name' => 'Class 1', 'code' => 'C1', 'is_active' => true]);
    $section = Section::create(['name' => 'A', 'class_id' => $class->id, 'is_active' => true]);

    $this->actingAs($user)
        ->post(route('sections.store'), ['name' => 'B', 'class_id' => $class->id])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('sections.update', $section), ['name' => 'Renamed', 'class_id' => $class->id])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('sections.destroy', $section))
        ->assertForbidden();
});

test('a user without academics.subject.manage cannot create, update or delete a subject', function () {
    $user = userWithPermission('academics.subject.manage', withPermission: false);
    $subject = Subject::create(['name' => 'Mathematics', 'is_active' => true]);

    $this->actingAs($user)
        ->post(route('subjects.store'), ['name' => 'Science'])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('subjects.update', $subject), ['name' => 'Renamed'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('subjects.destroy', $subject))
        ->assertForbidden();
});

test('a user without academics.class.manage cannot assign subjects to a class', function () {
    $user = userWithPermission('academics.class.manage', withPermission: false);
    $class = SchoolClass::create(['name' => 'Class 1', 'code' => 'C1', 'is_active' => true]);
    $subject = Subject::create(['name' => 'Mathematics', 'is_active' => true]);

    $this->actingAs($user)
        ->postJson(route('class-subjects.store'), [
            'class_id' => $class->id,
            'subject_ids' => [$subject->id],
        ])
        ->assertForbidden();
});

test('the same section name is allowed in two different classes but not twice in one class', function () {
    $user = userWithPermission('academics.class.manage');
    $classOne = SchoolClass::create(['name' => 'Class 1', 'code' => 'C1', 'is_active' => true]);
    $classTwo = SchoolClass::create(['name' => 'Class 2', 'code' => 'C2', 'is_active' => true]);
    Section::create(['name' => 'A', 'class_id' => $classOne->id, 'is_active' => true]);

    $this->actingAs($user)
        ->post(route('sections.store'), ['name' => 'A', 'class_id' => $classTwo->id])
        ->assertSessionHasNoErrors();

    expect(Section::where('class_id', $classTwo->id)->where('name', 'A')->exists())->toBeTrue();

    $this->actingAs($user)
        ->post(route('sections.store'), ['name' => 'A', 'class_id' => $classOne->id])
        ->assertSessionHasErrors('name');
});
