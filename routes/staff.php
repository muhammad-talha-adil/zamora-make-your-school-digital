<?php

use App\Http\Controllers\Staff\StaffAttendanceController;
use App\Http\Controllers\Staff\StaffDocumentTypeController;
use App\Http\Controllers\Staff\StaffLeaveController;
use App\Http\Controllers\Staff\StaffProfileController;
use App\Http\Controllers\Staff\StaffSalaryController;
use App\Http\Controllers\Staff\TeacherAssignmentController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
|
| The whole surface, declared once. Anything a later phase builds already has
| its name and its permission here, so no screen has to be re-pointed when the
| controller behind it arrives.
|
| Every route carries a permission. The ten `staff.*` abilities have been
| seeded since the beginning and, until now, not one of them was used: these
| eleven routes sat on `auth` alone, so any signed-in account could read every
| salary in the school and generate a payroll.
|
| `staff.salary.manage` is deliberately separate from `staff.manage`. A campus
| admin may hire, edit and mark attendance without seeing what anybody is paid.
|
*/

$middleware = ['web', 'auth'];

Route::prefix('staff')->name('staff.')->middleware($middleware)->group(function () {

    // ---------------------------------------------------------------- today
    Route::get('/', [StaffController::class, 'index'])
        ->name('index')
        ->middleware('permission:staff.view');

    // ------------------------------------------- departments & designations
    Route::post('/departments', [StaffController::class, 'storeDepartment'])
        ->name('departments.store')
        ->middleware('permission:staff.department.manage');
    Route::put('/departments/{department}', [StaffController::class, 'updateDepartment'])
        ->name('departments.update')
        ->middleware('permission:staff.department.manage');

    Route::post('/designations', [StaffController::class, 'storeDesignation'])
        ->name('designations.store')
        ->middleware('permission:staff.department.manage');
    Route::put('/designations/{designation}', [StaffController::class, 'updateDesignation'])
        ->name('designations.update')
        ->middleware('permission:staff.department.manage');

    // The full lookup panel — departments, designations and document types.
    Route::get('/settings', [StaffController::class, 'settingsPage'])
        ->name('settings.page')
        ->middleware('permission:staff.department.manage');

    Route::controller(StaffDocumentTypeController::class)->prefix('document-types')->name('document-types.')->group(function () {
        Route::get('/', 'index')->name('index')
            ->middleware('permission:staff.view|staff.department.manage');
        Route::post('/', 'store')->name('store')
            ->middleware('permission:staff.department.manage');
        Route::put('/{documentType}', 'update')->name('update')
            ->middleware('permission:staff.department.manage');
        Route::delete('/{documentType}', 'destroy')->name('destroy')
            ->middleware('permission:staff.department.manage');
    });

    // ------------------------------------------------------- staff records
    Route::post('/members', [StaffController::class, 'storeStaff'])
        ->name('members.store')
        ->middleware('permission:staff.manage');
    Route::put('/members/{staffProfile}', [StaffController::class, 'updateStaff'])
        ->name('members.update')
        ->middleware('permission:staff.manage');
    Route::patch('/members/{staffProfile}/toggle', [StaffController::class, 'toggleStaff'])
        ->name('members.toggle')
        ->middleware('permission:staff.manage');

    // ------------------------------------------------------------- payroll
    // Generating and releasing are two abilities: the person who works the
    // figures out is not automatically the person who pays them.
    Route::get('/payroll', [StaffController::class, 'payrollPage'])
        ->name('payroll.page')
        ->middleware('permission:staff.payroll.run');
    Route::post('/payroll/generate', [StaffController::class, 'generatePayroll'])
        ->name('payroll.generate')
        ->middleware('permission:staff.payroll.run');
    Route::post('/payroll/items/{payrollRunItem}/pay', [StaffController::class, 'payPayrollItem'])
        ->name('payroll.items.pay')
        ->middleware('permission:staff.payroll.approve');
    // ================================================================
    // Phase 3 — the person, and their jobs
    // ================================================================

    Route::controller(StaffProfileController::class)->group(function () {
        Route::get('/people', 'index')->name('people.index')
            ->middleware('permission:staff.view');
        Route::get('/people/list', 'list')->name('people.list')
            ->middleware('permission:staff.view');
        Route::get('/people/{staffProfile}', 'show')->name('people.show')
            ->middleware('permission:staff.view|staff.view.own');

        // Who this person is.
        Route::put('/people/{staffProfile}/personal', 'updatePersonal')->name('people.personal')
            ->middleware('permission:staff.manage');

        // One person, many jobs — the blocking fix from the plan.
        Route::post('/people/{staffProfile}/jobs', 'addJob')->name('people.jobs.add')
            ->middleware('permission:staff.manage');
        Route::patch('/jobs/{assignment}/primary', 'makeJobPrimary')->name('jobs.primary')
            ->middleware('permission:staff.manage');
        Route::patch('/jobs/{assignment}/end', 'endJob')->name('jobs.end')
            ->middleware('permission:staff.manage');

        // Joining, leaving, coming back.
        Route::post('/people/{staffProfile}/leave', 'leave')->name('people.leave')
            ->middleware('permission:staff.manage');
        Route::post('/people/{staffProfile}/rejoin', 'rejoin')->name('people.rejoin')
            ->middleware('permission:staff.manage');

        // The personal file.
        Route::post('/people/{staffProfile}/qualifications', 'addQualification')
            ->name('people.qualifications.add')->middleware('permission:staff.manage');
        Route::delete('/qualifications/{qualification}', 'removeQualification')
            ->name('qualifications.remove')->middleware('permission:staff.manage');
        Route::post('/people/{staffProfile}/documents', 'addDocument')
            ->name('people.documents.add')->middleware('permission:staff.manage');
        Route::delete('/documents/{document}', 'removeDocument')
            ->name('documents.remove')->middleware('permission:staff.manage');
    });

    // ================================================================
    // Phase 4 — the teacher
    //
    // `teacher_class_assignments` drives the class width in Attendance, Exam
    // and Student, and until now nothing wrote to it.
    // ================================================================

    Route::controller(TeacherAssignmentController::class)->group(function () {
        Route::get('/teaching', 'page')->name('teaching.page')
            ->middleware('permission:staff.view');
        Route::get('/teaching/list', 'index')->name('teaching.index')
            ->middleware('permission:staff.view');
        Route::get('/teaching/sections', 'sections')->name('teaching.sections')
            ->middleware('permission:staff.view');
        Route::get('/teaching/who-can-teach', 'whoCanTeach')->name('teaching.who-can-teach')
            ->middleware('permission:staff.view');

        Route::post('/people/{staffProfile}/classes', 'store')->name('teaching.assign')
            ->middleware('permission:staff.manage');
        Route::delete('/classes/{assignment}', 'destroy')->name('teaching.unassign')
            ->middleware('permission:staff.manage');

        Route::post('/people/{staffProfile}/subjects', 'allowSubject')->name('teaching.subjects.add')
            ->middleware('permission:staff.manage');
        Route::delete('/people/{staffProfile}/subjects/{subjectId}', 'disallowSubject')
            ->name('teaching.subjects.remove')->middleware('permission:staff.manage');
    });

    // ================================================================
    // Phase 5 — attendance & leave
    //
    // Reuses `WorkingDayCalculator` and `LateArrivalResolver`, built for the
    // student register, rather than a second copy of either.
    // ================================================================

    Route::controller(StaffAttendanceController::class)->prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', 'page')->name('page')
            ->middleware('permission:staff.attendance.mark');
        Route::get('/statuses', 'statuses')->name('statuses')
            ->middleware('permission:staff.attendance.view|staff.attendance.mark');
        Route::get('/people/{staffProfile}', 'index')->name('index')
            ->middleware('permission:staff.attendance.view|staff.view.own');
        Route::get('/people/{staffProfile}/summary', 'summary')->name('summary')
            ->middleware('permission:staff.attendance.view|staff.view.own');
        Route::post('/people/{staffProfile}', 'store')->name('store')
            ->middleware('permission:staff.attendance.mark');
        Route::post('/bulk', 'storeMany')->name('bulk')
            ->middleware('permission:staff.attendance.mark');
        Route::post('/lock', 'lockDay')->name('lock')
            ->middleware('permission:staff.attendance.mark');
    });

    // ================================================================
    // Phase 6 — named salary components
    //
    // `staff.salary.manage` stays separate from `staff.manage`: a campus admin
    // who may hire and edit staff still may not see what anybody is paid.
    // ================================================================

    Route::controller(StaffSalaryController::class)->prefix('salary')->name('salary.')->group(function () {
        Route::get('/people/{staffProfile}', 'index')->name('index')
            ->middleware('permission:staff.salary.manage|staff.view.own');
        Route::post('/people/{staffProfile}', 'store')->name('store')
            ->middleware('permission:staff.salary.manage');
        Route::patch('/{component}/end', 'end')->name('end')
            ->middleware('permission:staff.salary.manage');
    });

    Route::controller(StaffLeaveController::class)->prefix('leaves')->name('leaves.')->group(function () {
        Route::get('/types', 'types')->name('types')
            ->middleware('permission:staff.view|staff.view.own');
        Route::get('/people/{staffProfile}', 'index')->name('index')
            ->middleware('permission:staff.attendance.view|staff.view.own');
        Route::get('/people/{staffProfile}/balance', 'balance')->name('balance')
            ->middleware('permission:staff.attendance.view|staff.view.own');
        Route::post('/people/{staffProfile}', 'store')->name('store')
            ->middleware('permission:staff.view.own|staff.manage');
        Route::patch('/{leave}/decide', 'decide')->name('decide')
            ->middleware('permission:staff.attendance.mark|staff.manage');
        Route::patch('/{leave}/cancel', 'cancel')->name('cancel')
            ->middleware('permission:staff.view.own|staff.manage');
    });
});
