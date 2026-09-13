<?php

use App\Http\Controllers\AdmissionEnquiryController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPromotionController;
use App\Models\Student;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Student module.
| These routes are loaded by the RouteServiceProvider.
|
| Features implemented:
| - Explicit route model binding with 'student' parameter
| - Rate limiting on sensitive endpoints
| - Proper RESTful resource routes
| - Authorization via middleware
|
*/

// Environment-based middleware
$middleware = app()->environment('local') ? ['auth'] : ['auth', 'verified'];

/*
|--------------------------------------------------------------------------
| Route Model Binding
|--------------------------------------------------------------------------
|
| Register explicit binding for 'student' parameter to use Student model.
| This enables dependency injection of Student model in controller methods.
|
*/
Route::model('student', Student::class);

// Main student routes
Route::prefix('students')->name('students.')->middleware($middleware)->group(function () {
    /*
    |--------------------------------------------------------------------------
    | API Routes (Rate Limited)
    |--------------------------------------------------------------------------
    |
    | These routes are rate limited to prevent abuse.
    |
    */
    // Rate limited API endpoints (30 requests per minute)
    Route::middleware('throttle:30,1')->group(function () {
        // Dropdown data for students
        Route::get('/all', [StudentController::class, 'apiIndex'])->name('api.all')
            ->middleware('permission:students.view');

        // Helper endpoints for forms
        Route::get('/sections-by-class', [StudentController::class, 'getSectionsByClass'])
            ->name('sections-by-class')
            ->middleware('permission:students.view');
        Route::get('/guardian-by-phone', [StudentController::class, 'getGuardianByPhone'])
            ->name('guardian-by-phone')
            ->middleware('permission:students.create|students.edit');

        // Export and Import endpoints
        Route::get('/export', [StudentController::class, 'export'])->name('export')
            ->middleware('permission:students.export');
        Route::post('/import', [StudentController::class, 'import'])->name('import')
            ->middleware('permission:students.import');
    });

    /*
    |--------------------------------------------------------------------------
    | Admission enquiries
    |--------------------------------------------------------------------------
    |
    | The family who walked in and has not admitted a child yet. Before
    | `/{student}`, or "enquiries" is read as a student id.
    |
    */
    Route::get('/enquiries', [AdmissionEnquiryController::class, 'page'])
        ->name('enquiries.page')
        ->middleware('permission:students.view');
    Route::get('/enquiries/list', [AdmissionEnquiryController::class, 'index'])
        ->name('enquiries.index')
        ->middleware('permission:students.view');
    Route::post('/enquiries', [AdmissionEnquiryController::class, 'store'])
        ->name('enquiries.store')
        ->middleware('permission:students.create');
    Route::put('/enquiries/{id}', [AdmissionEnquiryController::class, 'update'])
        ->name('enquiries.update')
        ->middleware('permission:students.create');
    Route::get('/enquiries/{id}/prefill', [AdmissionEnquiryController::class, 'prefill'])
        ->name('enquiries.prefill')
        ->middleware('permission:students.create');
    Route::post('/enquiries/{id}/admitted', [AdmissionEnquiryController::class, 'markAdmitted'])
        ->name('enquiries.admitted')
        ->middleware('permission:students.create');
    Route::delete('/enquiries/{id}', [AdmissionEnquiryController::class, 'destroy'])
        ->name('enquiries.destroy')
        ->middleware('permission:students.create');

    // ID cards — a section at a time, eight to a page.
    Route::get('/id-cards', [StudentController::class, 'idCards'])
        ->name('id-cards')
        ->middleware('permission:students.view');

    /*
    |--------------------------------------------------------------------------
    | Promotion, transfer and the leaving certificate
    |--------------------------------------------------------------------------
    |
    | A school moves a whole section at a time, at the end of a year or in the
    | middle of one. Each of these is the same act underneath: an enrolment
    | period closed and another opened.
    |
    | These come before the `/{student}` routes, or "promotion" is read as a
    | student id.
    |
    */
    Route::get('/promotion', [StudentPromotionController::class, 'page'])
        ->name('promotion.page')
        ->middleware('permission:students.promote|students.view');

    Route::get('/promotion/preview', [StudentPromotionController::class, 'preview'])
        ->name('promotion.preview')
        ->middleware('permission:students.promote|students.view');

    Route::post('/promotion', [StudentPromotionController::class, 'promote'])
        ->name('promotion.run')
        ->middleware('permission:students.promote');

    // A section promoted by mistake is otherwise fixed by hand in the database.
    Route::post('/promotion/revert', [StudentPromotionController::class, 'revert'])
        ->name('promotion.revert')
        ->middleware('permission:students.promote');

    Route::post('/transfer', [StudentPromotionController::class, 'transfer'])
        ->name('transfer')
        ->middleware('permission:students.edit');

    /*
    |--------------------------------------------------------------------------
    | Resource Routes
    |--------------------------------------------------------------------------
    |
    | Standard RESTful resource controller routes.
    | Uses explicit route model binding for 'student' parameter.
    |
    | Methods:
    | - GET    /students           -> index()   (List all students)
    | - GET    /students/create    -> create()  (Show create form)
    | - POST   /students           -> store()   (Create new student)
    | - GET    /students/{student} -> show()    (Show single student)
    | - GET    /students/{student}/edit -> edit() (Show edit form)
    | - PUT    /students/{student} -> update()  (Update student)
    | - DELETE /students/{student} -> destroy() (Delete student)
    |
    | Additional routes:
    | - POST   /students/{student}/change-status -> changeStatus()
    | - POST   /students/{student}/readmit        -> readmit()
    | - POST   /students/{student}/restore        -> restore()
    |
    */
    Route::resource('/', StudentController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->parameters(['' => 'student']);

    // Additional action routes
    Route::post('/{student}/change-status', [StudentController::class, 'changeStatus'])
        ->name('change-status')
        ->middleware('throttle:60,1');

    Route::post('/{student}/readmit', [StudentController::class, 'readmit'])
        ->name('readmit')
        ->middleware('throttle:60,1');

    Route::post('/{student}/restore', [StudentController::class, 'restore'])
        ->name('restore')
        ->middleware('throttle:60,1');

    // Print admission form
    Route::get('/{student}/print', [StudentController::class, 'print'])
        ->name('print');

    // The School Leaving Certificate. A child cannot be admitted anywhere else
    // without it.
    Route::get('/{student}/leaving-certificate', [StudentController::class, 'leavingCertificate'])
        ->name('leaving-certificate')
        ->middleware('permission:students.view');

    // The rest of this child's family, for the fee module's family concession.
    Route::get('/{student}/siblings', [StudentController::class, 'siblings'])
        ->name('siblings')
        ->middleware('permission:students.view');
});

/*
|--------------------------------------------------------------------------
| Route Model Binding Configuration (AppServiceProvider)
|--------------------------------------------------------------------------
|
| To enable explicit route model binding, add this to AppServiceProvider boot():
|
| Route::bind('student', function ($value) {
|     return Student::with([
|         'user',
|         'gender',
|         'studentStatus',
|         'studentGuardians.guardian.user',
|         'studentGuardians.relation',
|         'enrollmentRecords.campus',
|         'enrollmentRecords.class',
|         'enrollmentRecords.section',
|         'enrollmentRecords.session',
|     ])->findOrFail($value);
| });
|
*/
