<?php

use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentDocumentType;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AdmissionWorld;

/**
 * Creating a document type, filing a document against a student, and the
 * required-type validation that a required document type must carry a
 * file.
 */
function makeStudentWithEnrollment(AdmissionWorld $world): Student
{
    $user = User::create([
        'name' => 'Sara Khan',
        'username' => 'sara.khan.'.uniqid(),
        'email' => 'sara.khan.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $student = Student::create([
        'user_id' => $user->id,
        'registration_no' => 'REG-'.uniqid(),
        'student_code' => 'STU-'.uniqid(),
        'admission_no' => 'ADM-'.uniqid(),
        'dob' => now()->subYears(10)->toDateString(),
        'gender_id' => $world->maleGender->id,
        'student_status_id' => $world->activeStatus->id,
        'admission_date' => now()->toDateString(),
    ]);

    StudentEnrollmentRecord::create([
        'student_id' => $student->id,
        'session_id' => $world->session->id,
        'class_id' => $world->class->id,
        'section_id' => $world->section->id,
        'campus_id' => $world->campus->id,
        'admission_date' => now()->toDateString(),
        'student_status_id' => $world->activeStatus->id,
    ]);

    return $student->fresh();
}

beforeEach(function () {
    Storage::fake('public');
});

test('a document type can be created', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);

    $response = $this->postJson(route('students.document-types.store'), [
        'name' => 'Birth Certificate',
        'is_required' => true,
    ]);

    $response->assertSuccessful();
    $response->assertJsonPath('documentType.name', 'Birth Certificate');
    $response->assertJsonPath('documentType.is_required', true);

    expect(StudentDocumentType::where('name', 'Birth Certificate')->exists())->toBeTrue();
});

test('a document can be uploaded for a student', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);

    $student = makeStudentWithEnrollment($world);

    $type = StudentDocumentType::create([
        'name' => 'CNIC Copy',
        'is_required' => false,
        'is_active' => true,
    ]);

    $file = UploadedFile::fake()->create('cnic.pdf', 100, 'application/pdf');

    $response = $this->postJson(route('students.documents.store', $student), [
        'student_document_type_id' => $type->id,
        'issue_date' => now()->toDateString(),
        'file' => $file,
    ]);

    $response->assertSuccessful();

    $document = StudentDocument::where('student_id', $student->id)->first();

    expect($document)->not->toBeNull();
    expect($document->student_document_type_id)->toBe($type->id);
    expect($document->path)->not->toBeNull();

    Storage::disk('public')->assertExists($document->path);
});

test('a required document type must be uploaded with a file', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);

    $student = makeStudentWithEnrollment($world);

    $type = StudentDocumentType::create([
        'name' => 'B-Form',
        'is_required' => true,
        'is_active' => true,
    ]);

    $response = $this->postJson(route('students.documents.store', $student), [
        'student_document_type_id' => $type->id,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('file');

    expect(StudentDocument::where('student_id', $student->id)->exists())->toBeFalse();
});
