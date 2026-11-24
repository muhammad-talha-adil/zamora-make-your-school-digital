<?php

use App\Http\Controllers\Staff\StaffAttendanceController;
use App\Http\Controllers\Staff\StaffDocumentTypeController;
use App\Http\Controllers\Staff\StaffLeaveController;
use App\Http\Controllers\Staff\StaffProfileController;
use App\Http\Controllers\Staff\StaffSalaryController;
use App\Http\Controllers\Staff\StaffSelfServiceController;
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

Route::prefix('staff')->name('staff.')->middleware([...$middleware, 'campus.exists'])->group(function () {

    // ---------------------------------------------------------------- today
    Route::get('/dashboard', [StaffController::class, 'index'])
        ->name('index')
        ->middleware('permission:staff.view');

    // Old `/staff/people...` bookmarks keep working.
    Route::get('/people/{path?}', fn (?string $path = null) => redirect('/staff'.($path ? '/'.$path : ''), 301))
        ->where('path', '.*');

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
    Route::delete('/designations/{designation}', [StaffController::class, 'destroyDesignation'])
        ->name('designations.destroy')
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
    // Activate/deactivate: the screen asks a plain yes/no; `staff.manage`
    // (and the campus check in the policy) is the gate.
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

    Route::get('/payroll/items/{payrollRunItem}/slip', [StaffSelfServiceController::class, 'salarySlip'])
        ->name('payroll.items.slip')
        ->middleware('permission:staff.salary.manage|staff.view.own');

    // -------------------------------------------------------------- advances
    Route::get('/members/{staffProfile}/advances', [StaffController::class, 'advancesForStaff'])
        ->name('advances.index')
        ->middleware('permission:staff.payroll.run|staff.view.own');
    Route::post('/advances', [StaffController::class, 'giveAdvance'])
        ->name('advances.store')
        ->middleware('permission:staff.payroll.approve');
    // Writing a balance off early is money leaving the books without a
    // payroll item behind it — the acting user's password is re-verified
    // server-side first, same as any other destructive action.
    Route::patch('/advances/{staffAdvance}/return', [StaffController::class, 'returnAdvance'])
        ->name('advances.return')
        ->middleware(['permission:staff.payroll.approve', 'password.confirm.server']);
    // ================================================================
    // Phase 3 — the person, and their jobs
    // ================================================================

    Route::controller(StaffProfileController::class)->group(function () {
        // A teacher, driver or clerk otherwise has no way to reach their own
        // record without already knowing its numeric id. An admin does not
        // need this — they already have full navigation to any profile.
        Route::get('/me', 'me')->name('me')
            ->middleware('permission:staff.view.own');
        Route::get('/me/dashboard', [StaffSelfServiceController::class, 'dashboard'])->name('me.dashboard')
            ->middleware('permission:staff.view.own');

        Route::get('/', 'index')->name('people.index')
            ->middleware('permission:staff.view');
        Route::get('/create', 'create')->name('people.create')
            ->middleware('permission:staff.manage');
        Route::get('/list', 'list')->name('people.list')
            ->middleware('permission:staff.view');
        // ID cards — same shape as the student list's `/id-cards`.
        Route::get('/id-cards', 'idCards')->name('people.id-cards')
            ->middleware('permission:staff.view');
        Route::get('/{staffProfile}', 'show')->name('people.show')
            ->middleware('permission:staff.view|staff.view.own')
            ->whereNumber('staffProfile');

        // Who this person is.
        Route::put('/{staffProfile}/personal', 'updatePersonal')->name('people.personal')
            ->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');

        // One person, many jobs — the blocking fix from the plan.
        Route::post('/{staffProfile}/jobs', 'addJob')->name('people.jobs.add')
            ->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');
        Route::patch('/jobs/{assignment}/primary', 'makeJobPrimary')->name('jobs.primary')
            ->middleware('permission:staff.manage');
        Route::patch('/jobs/{assignment}/end', 'endJob')->name('jobs.end')
            ->middleware('permission:staff.manage');

        // Joining, leaving, coming back.
        Route::post('/{staffProfile}/leave', 'leave')->name('people.leave')
            ->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');
        Route::post('/{staffProfile}/rejoin', 'rejoin')->name('people.rejoin')
            ->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');

        // The personal file.
        Route::post('/{staffProfile}/qualifications', 'addQualification')
            ->name('people.qualifications.add')->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');
        Route::delete('/qualifications/{qualification}', 'removeQualification')
            ->name('qualifications.remove')->middleware('permission:staff.manage');
        Route::post('/{staffProfile}/documents', 'addDocument')
            ->name('people.documents.add')->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');
        Route::delete('/documents/{document}', 'removeDocument')
            ->name('documents.remove')->middleware('permission:staff.manage');

        // Per-staff-member direct permission grants — role-independent,
        // campus-scoped the same way every other `update` action on a
        // StaffProfile is (StaffProfilePolicy::update).
        Route::get('/{staffProfile}/permissions', 'permissions')
            ->name('people.permissions')->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');
        Route::post('/{staffProfile}/login', 'issueLogin')
            ->name('people.login')->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');
        Route::put('/{staffProfile}/permissions', 'updatePermissions')
            ->name('people.permissions.update')->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');
    });

    // ================================================================
    // Phase 4 — the teacher
    //
    // `teacher_class_assignments` drives the class width in Attendance, Exam
    // and Student, and until now nothing wrote to it.
    // ================================================================

    Route::controller(TeacherAssignmentController::class)->group(function () {
        // `staff.view.own` lets a teacher reach their own assigned classes and
        // subjects here too — the controller scopes the list down to just
        // their own assignments when that is the only ability they hold.
        Route::get('/teaching', 'page')->name('teaching.page')
            ->middleware('permission:staff.view|staff.view.own');
        Route::get('/teaching/list', 'index')->name('teaching.index')
            ->middleware('permission:staff.view|staff.view.own');
        Route::get('/teaching/sections', 'sections')->name('teaching.sections')
            ->middleware('permission:staff.view|staff.view.own');
        Route::get('/teaching/who-can-teach', 'whoCanTeach')->name('teaching.who-can-teach')
            ->middleware('permission:staff.view|staff.view.own');
        Route::get('/teaching/exams', 'examPapers')->name('teaching.exams')
            ->middleware('permission:staff.view|staff.view.own');

        Route::post('/{staffProfile}/classes', 'store')->name('teaching.assign')
            ->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');
        Route::delete('/classes/{assignment}', 'destroy')->name('teaching.unassign')
            ->middleware('permission:staff.manage');

        Route::post('/{staffProfile}/subjects', 'allowSubject')->name('teaching.subjects.add')
            ->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');
        Route::delete('/{staffProfile}/subjects/{subjectId}', 'disallowSubject')
            ->name('teaching.subjects.remove')->middleware('permission:staff.manage')
            ->whereNumber('staffProfile');
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
