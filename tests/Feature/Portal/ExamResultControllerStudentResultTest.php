<?php

/**
 * `ExamResultController::studentResult()` used to be a stub returning
 * `{"message": "Student result", ...}` placeholder JSON. It now returns the
 * real result card data, behind the same `ExamResultHeaderPolicy::view()`
 * ownership check the rest of the module already uses.
 */

use Tests\Support\PortalWorld;

beforeEach(function () {
    $this->world = PortalWorld::make();
});

it('returns the real result card data, not the stub', function () {
    [$user, $student] = $this->world->portalStudent();
    $result = $this->world->examResultFor($student);

    $response = $this->actingAs($user)->getJson(
        route('exam.results.student', ['studentId' => $student->id, 'examId' => $result->exam_id])
    );

    $response->assertOk();
    $response->assertJson(['success' => true]);
    $response->assertJsonMissing(['message' => 'Student result']);
    $response->assertJsonPath('data.student.id', $student->id);
});

it('forbids a student from reading another student\'s result through the same endpoint', function () {
    [$userA] = $this->world->portalStudent('a');
    [, $studentB] = $this->world->portalStudent('b');
    $resultB = $this->world->examResultFor($studentB);

    $this->actingAs($userA)->getJson(
        route('exam.results.student', ['studentId' => $studentB->id, 'examId' => $resultB->exam_id])
    )->assertForbidden();
});
