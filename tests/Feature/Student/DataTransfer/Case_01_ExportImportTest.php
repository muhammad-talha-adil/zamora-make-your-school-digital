<?php

/**
 * Case 01 — getting the roll in and out.
 *
 * Both used to be a message and nothing else:
 *
 * ```php
 * return response()->json([
 *     'success' => true,
 *     'message' => 'Import started. You will be notified when complete.',
 * ]);
 * ```
 *
 * Nothing was read, nothing was written, nothing was queued, nobody was
 * notified — and import is how a school onboards. They prepare a spreadsheet
 * for a week, upload it, are told it worked, and find an empty system.
 */

use App\Models\StaffProfile;
use App\Models\Student;
use App\Services\Student\StudentExportService;
use Illuminate\Http\UploadedFile;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

/** A CSV the importer can read. */
function csvFile(array $rows, array $header = ['admission_no', 'name', 'dob', 'gender', 'class', 'session', 'campus']): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
    $handle = fopen($path, 'w');

    fputcsv($handle, $header);

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return new UploadedFile($path, 'students.csv', 'text/csv', null, true);
}

/* -------------------------------------------------------------------- export */

it('exports a real file, not a promise of one', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-E1']))
        ->assertSessionHasNoErrors();

    $response = $this->get(route('students.export'));

    $response->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())->toContain('ADM-E1')
        ->and($response->streamedContent())->toContain('admission_no');
});

it('carries the columns the importer reads back', function () {
    $this->post(route('students.store'), $this->world->payload())->assertSessionHasNoErrors();

    $csv = $this->get(route('students.export'))->streamedContent();

    // A school exports a class, corrects it in Excel, and puts it back.
    foreach (StudentExportService::COLUMNS as $column) {
        expect($csv)->toContain($column);
    }
});

it('exports only the children this person may see', function () {
    $this->world->withFullRoles();
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-E9']))
        ->assertSessionHasNoErrors();

    $admin = $this->world->userWithRole('campus_admin', 'admin.export@school.test');
    StaffProfile::updateOrCreate(['user_id' => $admin->id], [
        'employee_no' => 'EMP-EXP',
        'campus_id' => $this->world->otherCampus->id,
        'employment_type' => 'permanent',
        'hire_date' => '2026-04-01',
        'is_active' => true,
    ]);

    // The ability alone used to hand over the whole school.
    $csv = $this->actingAs($admin->fresh())->get(route('students.export'))->streamedContent();

    expect($csv)->not->toContain('ADM-E9');
});

/* -------------------------------------------------------------------- import */

it('imports the children in the file', function () {
    $file = csvFile([
        ['ADM-I1', 'Bilal Ahmed', '2014-03-02', 'Male', $this->world->class->name, $this->world->session->name, $this->world->campus->name],
        ['ADM-I2', 'Sara Khan', '2015-07-19', 'Male', $this->world->class->name, $this->world->session->name, $this->world->campus->name],
    ]);

    $this->post(route('students.import'), ['file' => $file])
        ->assertSuccessful()
        ->assertJsonPath('data.imported', 2);

    expect(Student::whereIn('admission_no', ['ADM-I1', 'ADM-I2'])->count())->toBe(2);
});

it('says which row is wrong, and which cell', function () {
    $file = csvFile([
        ['ADM-I1', 'Bilal Ahmed', '2014-03-02', 'Male', $this->world->class->name, $this->world->session->name, $this->world->campus->name],
        ['ADM-I2', 'Sara Khan', 'not-a-date', 'Male', $this->world->class->name, $this->world->session->name, $this->world->campus->name],
    ]);

    $problems = $this->post(route('students.import'), ['file' => $file])
        ->assertStatus(422)
        ->json('data.problems');

    // "Import failed" is not something a school with four hundred rows can act
    // on.
    expect($problems)->toHaveCount(1)
        ->and($problems[0])->toContain('Row 3');
});

it('writes nothing at all when one row is wrong', function () {
    $file = csvFile([
        ['ADM-I1', 'Bilal Ahmed', '2014-03-02', 'Male', $this->world->class->name, $this->world->session->name, $this->world->campus->name],
        ['ADM-I2', 'Sara Khan', 'not-a-date', 'Male', $this->world->class->name, $this->world->session->name, $this->world->campus->name],
    ]);

    $this->post(route('students.import'), ['file' => $file])->assertStatus(422);

    // A half-imported file is what a school cannot recover from by hand.
    expect(Student::count())->toBe(0);
});

it('names a class it does not recognise', function () {
    $file = csvFile([
        ['ADM-I1', 'Bilal Ahmed', '2014-03-02', 'Male', 'Clas 5', $this->world->session->name],
    ]);

    $problems = $this->post(route('students.import'), ['file' => $file])
        ->assertStatus(422)
        ->json('data.problems');

    expect($problems[0])->toContain('Clas 5');
});

it('refuses a file with no admission number column', function () {
    $file = csvFile([['Bilal', '2014-03-02']], ['name', 'dob']);

    $this->post(route('students.import'), ['file' => $file])
        ->assertStatus(422)
        ->assertJsonPath('data.problems.0', 'The file has no "admission_no" column.');
});

it('checks a file without importing it', function () {
    $file = csvFile([
        ['ADM-I1', 'Bilal Ahmed', '2014-03-02', 'Male', $this->world->class->name, $this->world->session->name, $this->world->campus->name],
    ]);

    $this->post(route('students.import'), ['file' => $file, 'dry_run' => true])
        ->assertSuccessful()
        ->assertJsonPath('data.would_import', 1);

    // Nobody should discover a bad row by importing it.
    expect(Student::count())->toBe(0);
});

it('skips a child already on the roll rather than refusing the file', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-I1']))
        ->assertSessionHasNoErrors();

    $file = csvFile([
        ['ADM-I1', 'Bilal Ahmed', '2014-03-02', 'Male', $this->world->class->name, $this->world->session->name, $this->world->campus->name],
        ['ADM-I2', 'Sara Khan', '2015-07-19', 'Male', $this->world->class->name, $this->world->session->name, $this->world->campus->name],
    ]);

    // Uploading a corrected file must not mean deleting first.
    $this->post(route('students.import'), ['file' => $file])
        ->assertSuccessful()
        ->assertJsonPath('data.imported', 1);

    expect(Student::count())->toBe(2);
});

it('does not let somebody without the ability import', function () {
    $this->world->withFullRoles();
    $teacher = $this->world->userWithRole('teacher', 'teacher.import@school.test');

    $this->actingAs($teacher)
        ->post(route('students.import'), [
            'file' => csvFile([['ADM-X', 'X', '2014-01-01', 'Male', $this->world->class->name, $this->world->session->name, $this->world->campus->name]]),
        ])
        ->assertForbidden();
});
