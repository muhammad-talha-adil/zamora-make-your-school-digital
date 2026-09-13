<?php

/**
 * Case 01 — timetabling a paper through the real endpoints.
 *
 * `StoreExamPaperRequest`, `UpdateExamPaperRequest`, `BulkCreateExamPaperRequest`
 * and `StoreRegistrationRequest` still validated `exam_group_id` against
 * `exam_groups` — a table dropped in `2026_02_15_100017_drop_exam_groups_and_offerings_tables.php`
 * in favour of the scope-based design `exam_papers` actually uses. None of the
 * four had `exam_id`/`scope_type` in their rules at all, despite the
 * controllers requiring them from `$validated`. Every one of these endpoints
 * has been unusable from its own frontend since that migration — this had no
 * test coverage at all until now.
 */

use App\Models\Exam\ExamPaper;
use App\Models\Exam\ExamStudentRegistration;
use App\Models\Subject;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
});

it('creates a paper through the real endpoint', function () {
    $subject = Subject::firstOrCreate(['name' => 'English'], ['short_name' => 'ENG', 'is_active' => true]);

    $response = $this->post(route('exam.papers.store'), [
        'exam_id' => $this->world->exam->id,
        'scope_type' => 'SECTION',
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'subject_id' => $subject->id,
        'paper_date' => now()->addDays(10)->toDateString(),
        'start_time' => '09:00',
        'end_time' => '11:00',
        'total_marks' => 100,
        'passing_marks' => 40,
    ]);

    $response->assertCreated();
    expect(ExamPaper::where('exam_id', $this->world->exam->id)->where('subject_id', $subject->id)->exists())->toBeTrue();
});

it('updates a paper through the real endpoint', function () {
    $paper = $this->world->paper('Mathematics');

    $this->put(route('exam.papers.update', $paper->id), [
        'total_marks' => 120,
        'passing_marks' => 50,
    ])->assertSuccessful();

    expect((float) $paper->fresh()->total_marks)->toBe(120.0);
});

it('bulk-creates papers through the real endpoint', function () {
    $science = Subject::firstOrCreate(['name' => 'Science'], ['short_name' => 'SCI', 'is_active' => true]);
    $urdu = Subject::firstOrCreate(['name' => 'Urdu'], ['short_name' => 'URD', 'is_active' => true]);

    $response = $this->post(route('exam.papers.bulk-create'), [
        'exam_id' => $this->world->exam->id,
        'scope_type' => 'SECTION',
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'papers' => [
            ['subject_id' => $science->id, 'paper_date' => now()->addDays(15)->toDateString(), 'start_time' => '09:00', 'end_time' => '11:00', 'total_marks' => 100, 'passing_marks' => 40],
            ['subject_id' => $urdu->id, 'paper_date' => now()->addDays(16)->toDateString(), 'start_time' => '09:00', 'end_time' => '11:00', 'total_marks' => 100, 'passing_marks' => 40],
        ],
    ]);

    $response->assertCreated();
    expect($response->json('count'))->toBe(2);
});

it('registers a single student through the real endpoint', function () {
    $students = $this->world->enrol(1);

    $response = $this->post(route('exam.registrations.store'), [
        'exam_id' => $this->world->exam->id,
        'student_id' => $students[0]->id,
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id,
    ]);

    $response->assertCreated();
    expect(ExamStudentRegistration::where('student_id', $students[0]->id)->exists())->toBeTrue();
});
