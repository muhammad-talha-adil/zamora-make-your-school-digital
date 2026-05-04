<?php

use App\Http\Controllers\StudentController;
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
        Route::get('/all', [StudentController::class, 'apiIndex'])->name('api.all');

        // Helper endpoints for forms
        Route::get('/sections-by-class', [StudentController::class, 'getSectionsByClass'])
            ->name('sections-by-class');
        Route::get('/guardian-by-phone', [StudentController::class, 'getGuardianByPhone'])
            ->name('guardian-by-phone');

        // Export and Import endpoints
        Route::get('/export', [StudentController::class, 'export'])->name('export');
        Route::post('/import', [StudentController::class, 'import'])->name('import');
    });

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
