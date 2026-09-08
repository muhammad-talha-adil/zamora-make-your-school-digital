<?php

/**
 * Case 03 — a register may only change its own rows.
 *
 * The update endpoint fetched each row by the id in the request and never
 * checked it belonged to the register named in the URL. A teacher authorised
 * for their own class could post that class's id in the URL and another
 * class's row ids in the body, and rewrite marks they were never authorised to
 * touch — a locked register included, since the policy only ever saw the
 * record named in the URL.
 */

use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->students = $this->world->enrol(1);
    $this->otherStudents = $this->world->enrol(1, $this->world->school->otherSection->id);

    $this->mine = $this->world->register('2026-04-06');
    $this->myRow = $this->world->mark($this->mine, $this->students[0], 'P');

    $this->theirs = $this->world->register('2026-04-06', $this->world->school->otherSection->id);
    $this->theirRow = $this->world->mark($this->theirs, $this->otherStudents[0], 'P');

    $this->actingAs($this->world->school->actor);
});

it('updates a row that belongs to the register', function () {
    $this->put(route('attendance.update', $this->mine), [
        'attendances' => [[
            'id' => $this->myRow->id,
            'student_id' => $this->students[0]->id,
            'attendance_status_id' => $this->world->status('A')->id,
        ]],
    ])->assertRedirect(route('attendance.index'));

    expect($this->myRow->fresh()->attendance_status_id)->toBe($this->world->status('A')->id);
});

it('rejects a row belonging to another register', function () {
    $this->put(route('attendance.update', $this->mine), [
        'attendances' => [[
            'id' => $this->theirRow->id,
            'student_id' => $this->otherStudents[0]->id,
            'attendance_status_id' => $this->world->status('A')->id,
        ]],
    ])->assertSessionHasErrors('attendances.0.id');
});

it('leaves the other register untouched when the attempt is rejected', function () {
    $before = $this->theirRow->attendance_status_id;

    $this->put(route('attendance.update', $this->mine), [
        'attendances' => [[
            'id' => $this->theirRow->id,
            'student_id' => $this->otherStudents[0]->id,
            'attendance_status_id' => $this->world->status('A')->id,
        ]],
    ]);

    expect($this->theirRow->fresh()->attendance_status_id)->toBe($before);
});

it('rejects the whole request when one row belongs elsewhere', function () {
    $this->put(route('attendance.update', $this->mine), [
        'attendances' => [
            [
                'id' => $this->myRow->id,
                'student_id' => $this->students[0]->id,
                'attendance_status_id' => $this->world->status('A')->id,
            ],
            [
                'id' => $this->theirRow->id,
                'student_id' => $this->otherStudents[0]->id,
                'attendance_status_id' => $this->world->status('A')->id,
            ],
        ],
    ])->assertSessionHasErrors('attendances.1.id');

    // The legitimate row is not written either: the request is refused whole.
    expect($this->myRow->fresh()->attendance_status_id)->toBe($this->world->status('P')->id);
});

it('rejects a row id that does not exist at all', function () {
    $this->put(route('attendance.update', $this->mine), [
        'attendances' => [[
            'id' => 999999,
            'student_id' => $this->students[0]->id,
            'attendance_status_id' => $this->world->status('A')->id,
        ]],
    ])->assertSessionHasErrors('attendances.0.id');
});
