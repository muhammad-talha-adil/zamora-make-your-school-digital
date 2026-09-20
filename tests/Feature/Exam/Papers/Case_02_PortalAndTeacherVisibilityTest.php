<?php

/**
 * Case 02 — the timing set on the papers screen (#84).
 *
 * A paper's date/time was already stored and already reachable through
 * `ExamPaper::scopeVisibleTo()`, but neither the family portal nor a teacher
 * had anywhere that read it: `PortalExamController::index()` only ever
 * queried `ExamResultHeader` (a published result), and there was no teacher
 * screen for exam papers at all. This asserts both now show it.
 */

use App\Models\Exam\ExamPaper;
use App\Models\SchoolClass;
use App\Models\Section;
use Tests\Support\PortalWorld;

beforeEach(function () {
    $this->world = PortalWorld::make();
});

it('shows a child their own class upcoming exam paper timing on the portal', function () {
    [$user, $student] = $this->world->portalStudent();
    $paper = $this->world->paperFor('Mathematics', '2026-09-25');

    $response = $this->actingAs($user)->get(route('portal.exams.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Exams/Index')
        ->where('upcomingPapers.0.id', $paper->id)
        ->where('upcomingPapers.0.subject', 'Mathematics')
        ->where('upcomingPapers.0.start_time', '09:00')
    );
});

it('does not show a family a paper timetabled for a different class', function () {
    [$user] = $this->world->portalStudent();
    $paper = $this->world->paperFor('Mathematics', '2026-09-25');
    $otherClass = SchoolClass::create(['name' => 'Other Class', 'level' => 99, 'is_active' => true]);
    $paper->update(['class_id' => $otherClass->id, 'section_id' => null]);

    $response = $this->actingAs($user)->get(route('portal.exams.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Exams/Index')
        ->where('upcomingPapers', [])
    );
});

it('shows a teacher the upcoming papers for the class they are assigned', function () {
    $teacher = $this->world->teacherFor();
    $paper = $this->world->paperFor('Science', '2026-09-28');

    $response = $this->actingAs($teacher)->get(route('staff.teaching.exams'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Staff/Teaching/Exams')
        ->where('papers.0.id', $paper->id)
        ->where('papers.0.subject', 'Science')
    );
});

it('does not show a teacher papers for a class they are not assigned to', function () {
    $teacher = $this->world->teacherFor();
    $paper = $this->world->paperFor('Science', '2026-09-28');
    $otherSection = Section::create(['name' => 'Other Section', 'class_id' => $paper->class_id, 'is_active' => true]);
    $paper->update(['section_id' => $otherSection->id]);

    $response = $this->actingAs($teacher)->get(route('staff.teaching.exams'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Staff/Teaching/Exams')
        ->where('papers', [])
    );
});

it('turns away a signed-in user with no staff record from the teacher exams screen', function () {
    [$outsider] = $this->world->portalStudent();

    $this->actingAs($outsider)->get(route('staff.teaching.exams'))->assertForbidden();
});

it('excludes a cancelled paper from both the portal and teacher lists', function () {
    [$user] = $this->world->portalStudent();
    $teacher = $this->world->teacherFor();
    $paper = $this->world->paperFor('Urdu', '2026-09-30');
    $paper->update(['status' => 'cancelled']);

    $this->actingAs($user)->get(route('portal.exams.index'))
        ->assertInertia(fn ($page) => $page->where('upcomingPapers', []));

    $this->actingAs($teacher)->get(route('staff.teaching.exams'))
        ->assertInertia(fn ($page) => $page->where('papers', []));

    expect(ExamPaper::where('id', $paper->id)->exists())->toBeTrue();
});
