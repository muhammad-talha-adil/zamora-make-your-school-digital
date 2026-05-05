<?php

use App\Http\Controllers\Settings\CampusController;
use App\Http\Controllers\Settings\CampusTypeController;
use App\Http\Controllers\Settings\ClassSubjectController;
use App\Http\Controllers\Settings\ExamTypeController;
use App\Http\Controllers\Settings\MenuController;
use App\Http\Controllers\Settings\MonthController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SchoolClassController;
use App\Http\Controllers\Settings\SchoolController;
use App\Http\Controllers\Settings\SectionController;
use App\Http\Controllers\Settings\SessionController;
use App\Http\Controllers\Settings\SubjectController;
use App\Http\Controllers\Settings\ThemeSettingsController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Redirect settings to profile
    Route::redirect('settings', '/settings/profile');

    // Profile Management Routes
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Profile Management Routes (continued)
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Password Management Routes
    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    // Appearance/Themes Routes
    Route::get('settings/appearance', [ThemeSettingsController::class, 'index'])->name('appearance.index');
    Route::post('settings/appearance/light', [ThemeSettingsController::class, 'updateLight'])->name('appearance.updateLight');
    Route::post('settings/appearance/dark', [ThemeSettingsController::class, 'updateDark'])->name('appearance.updateDark');

    // School Profile Routes
    Route::get('settings/school-profile', [SchoolController::class, 'show'])->name('school-profile.show');
    Route::post('settings/school-profile', [SchoolController::class, 'update'])->name('school-profile.update');

    // Two-Factor Authentication Routes
    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // Menu Settings Routes
    Route::get('settings/menu-settings', [MenuController::class, 'index'])->name('menus.index');
    Route::get('settings/menus/all', [MenuController::class, 'apiIndex'])->name('menus.all');
    Route::get('settings/menus/create', [MenuController::class, 'create'])->name('menus.create')->middleware('permission:settings.manage');
    Route::post('settings/menus', [MenuController::class, 'store'])->name('menus.store')->middleware('permission:settings.manage');
    Route::get('settings/menus/{menu}/edit', [MenuController::class, 'edit'])->name('menus.edit')->middleware('permission:settings.manage');
    Route::patch('settings/menus/{menu}', [MenuController::class, 'update'])->name('menus.update')->middleware('permission:settings.manage');
    Route::delete('settings/menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');
    Route::patch('settings/menus/{menu}/inactivate', [MenuController::class, 'inactivate'])->name('menus.inactivate');
    Route::patch('settings/menus/{menu}/activate', [MenuController::class, 'activate'])->name('menus.activate');
    Route::patch('settings/menus/{id}/restore', [MenuController::class, 'restore'])->name('menus.restore');
    Route::delete('settings/menus/{id}/force-delete', [MenuController::class, 'forceDelete'])->name('menus.force-delete');

    // Campus Types Routes
    Route::get('settings/campus-types', [CampusTypeController::class, 'getAll'])->name('campus-types.getAll');
    Route::get('settings/campus-types/all', [CampusTypeController::class, 'getAll'])->name('campus-types.all');
    Route::get('settings/campus-types/create', [CampusTypeController::class, 'create'])->name('campus-types.create');
    Route::post('settings/campus-types', [CampusTypeController::class, 'store'])->name('campus-types.store');
    Route::get('settings/campus-types/{campusType}/edit', [CampusTypeController::class, 'edit'])->name('campus-types.edit');
    Route::put('settings/campus-types/{campusType}', [CampusTypeController::class, 'update'])->name('campus-types.update');
    Route::patch('settings/campus-types/{campusType}', [CampusTypeController::class, 'update'])->name('campus-types.update');
    Route::delete('settings/campus-types/{campusType}', [CampusTypeController::class, 'destroy'])->name('campus-types.destroy');
    Route::patch('settings/campus-types/{id}/restore', [CampusTypeController::class, 'restore'])->name('campus-types.restore');
    Route::delete('settings/campus-types/{id}/force-delete', [CampusTypeController::class, 'forceDelete'])->name('campus-types.force-delete');

    // Campuses Routes
    Route::get('settings/campuses', [CampusController::class, 'index'])->name('campuses.index');
    Route::get('settings/campuses/all', [CampusController::class, 'apiIndex'])->name('campuses.all');
    Route::get('settings/campuses/create', [CampusController::class, 'create'])->name('campuses.create');
    Route::post('settings/campuses', [CampusController::class, 'store'])->name('campuses.store');
    Route::get('settings/campuses/{campus}/edit', [CampusController::class, 'edit'])->name('campuses.edit');
    Route::put('settings/campuses/{campus}', [CampusController::class, 'update'])->name('campuses.update');
    Route::patch('settings/campuses/{campus}', [CampusController::class, 'update'])->name('campuses.update');
    Route::delete('settings/campuses/{campus}', [CampusController::class, 'destroy'])->name('campuses.destroy');
    Route::patch('settings/campuses/{campus}/inactivate', [CampusController::class, 'inactivate'])->name('campuses.inactivate');
    Route::patch('settings/campuses/{campus}/activate', [CampusController::class, 'activate'])->name('campuses.activate');
    Route::patch('settings/campuses/{id}/restore', [CampusController::class, 'restore'])->name('campuses.restore');
    Route::delete('settings/campuses/{id}/force-delete', [CampusController::class, 'forceDelete'])->name('campuses.force-delete');

    // Sessions Routes
    Route::get('settings/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('settings/sessions/all', [SessionController::class, 'index'])->name('sessions.all');
    Route::get('settings/sessions/create', [SessionController::class, 'create'])->name('sessions.create');
    Route::post('settings/sessions', [SessionController::class, 'store'])->name('sessions.store');
    Route::get('settings/sessions/{session}/edit', [SessionController::class, 'edit'])->name('sessions.edit');
    Route::patch('settings/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
    Route::delete('settings/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    Route::patch('settings/sessions/{session}/inactivate', [SessionController::class, 'inactivate'])->name('sessions.inactivate');
    Route::patch('settings/sessions/{session}/activate', [SessionController::class, 'activate'])->name('sessions.activate');
    Route::patch('settings/sessions/{id}/restore', [SessionController::class, 'restore'])->name('sessions.restore');
    Route::delete('settings/sessions/{id}/force-delete', [SessionController::class, 'forceDelete'])->name('sessions.force-delete');

    // School Classes Routes
    Route::get('settings/school-classes', [SchoolClassController::class, 'index'])->name('school-classes.index');
    Route::get('settings/school-classes/all', [SchoolClassController::class, 'apiIndex'])->name('school-classes.all');
    Route::get('settings/school-classes/create', [SchoolClassController::class, 'create'])->name('school-classes.create');
    Route::post('settings/school-classes', [SchoolClassController::class, 'store'])->name('school-classes.store');
    Route::get('settings/school-classes/{schoolClass}/edit', [SchoolClassController::class, 'edit'])->name('school-classes.edit');
    Route::patch('settings/school-classes/{schoolClass}', [SchoolClassController::class, 'update'])->name('school-classes.update');
    Route::delete('settings/school-classes/{schoolClass}', [SchoolClassController::class, 'destroy'])->name('school-classes.destroy');
    Route::patch('settings/school-classes/{schoolClass}/inactivate', [SchoolClassController::class, 'inactivate'])->name('school-classes.inactivate');
    Route::patch('settings/school-classes/{schoolClass}/activate', [SchoolClassController::class, 'activate'])->name('school-classes.activate');
    Route::patch('settings/school-classes/{id}/restore', [SchoolClassController::class, 'restore'])->name('school-classes.restore');
    Route::delete('settings/school-classes/{id}/force-delete', [SchoolClassController::class, 'forceDelete'])->name('school-classes.force-delete');

    // Sections Routes
    Route::get('settings/sections', [SectionController::class, 'index'])->name('sections.index');
    Route::get('settings/sections/all', [SectionController::class, 'apiIndex'])->name('sections.all');
    Route::get('settings/sections/create', [SectionController::class, 'create'])->name('sections.create');
    Route::post('settings/sections', [SectionController::class, 'store'])->name('sections.store');
    Route::get('settings/sections/{section}/edit', [SectionController::class, 'edit'])->name('sections.edit');
    Route::patch('settings/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
    Route::delete('settings/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');
    Route::patch('settings/sections/{section}/inactivate', [SectionController::class, 'inactivate'])->name('sections.inactivate');
    Route::patch('settings/sections/{section}/activate', [SectionController::class, 'activate'])->name('sections.activate');
    Route::patch('settings/sections/{id}/restore', [SectionController::class, 'restore'])->name('sections.restore');
    Route::delete('settings/sections/{id}/force-delete', [SectionController::class, 'forceDelete'])->name('sections.force-delete');

    // Subjects Routes
    Route::get('settings/subjects', [SubjectController::class, 'index'])->name('subjects.index');
    Route::get('settings/subjects/all', [SubjectController::class, 'apiIndex'])->name('subjects.all');
    Route::get('settings/subjects/create', [SubjectController::class, 'create'])->name('subjects.create');
    Route::post('settings/subjects', [SubjectController::class, 'store'])->name('subjects.store');
    Route::get('settings/subjects/{subject}/edit', [SubjectController::class, 'edit'])->name('subjects.edit');
    Route::patch('settings/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
    Route::delete('settings/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
    Route::patch('settings/subjects/{subject}/inactivate', [SubjectController::class, 'inactivate'])->name('subjects.inactivate');
    Route::patch('settings/subjects/{subject}/activate', [SubjectController::class, 'activate'])->name('subjects.activate');
    Route::patch('settings/subjects/{id}/restore', [SubjectController::class, 'restore'])->name('subjects.restore');
    Route::delete('settings/subjects/{id}/force-delete', [SubjectController::class, 'forceDelete'])->name('subjects.force-delete');

    // Class Subjects Routes (Subjects to Class)
    Route::get('settings/class-subjects', [ClassSubjectController::class, 'index'])->name('class-subjects.index');
    Route::get('settings/class-subjects/sections', [ClassSubjectController::class, 'getSections'])->name('class-subjects.sections');
    Route::get('settings/class-subjects/assigned', [ClassSubjectController::class, 'getAssignedSubjects'])->name('class-subjects.assigned');
    Route::post('settings/class-subjects', [ClassSubjectController::class, 'store'])->name('class-subjects.store');

    // Months Routes
    Route::get('settings/months', [MonthController::class, 'index'])->name('months.index');
    Route::get('settings/months/all', [MonthController::class, 'getAll'])->name('months.all');

    // Exam Types Routes
    Route::get('settings/exam-types', [ExamTypeController::class, 'index'])->name('exam-types.index');
    Route::get('settings/exam-types/all', [ExamTypeController::class, 'apiIndex'])->name('exam-types.all');
    Route::get('settings/exam-types/create', [ExamTypeController::class, 'create'])->name('exam-types.create');
    Route::post('settings/exam-types', [ExamTypeController::class, 'store'])->name('exam-types.store');
    Route::get('settings/exam-types/{examType}/edit', [ExamTypeController::class, 'edit'])->name('exam-types.edit');
    Route::patch('settings/exam-types/{examType}', [ExamTypeController::class, 'update'])->name('exam-types.update');
    Route::delete('settings/exam-types/{examType}', [ExamTypeController::class, 'destroy'])->name('exam-types.destroy');
    Route::patch('settings/exam-types/{examType}/inactivate', [ExamTypeController::class, 'inactivate'])->name('exam-types.inactivate');
    Route::patch('settings/exam-types/{examType}/activate', [ExamTypeController::class, 'activate'])->name('exam-types.activate');
    Route::patch('settings/exam-types/{id}/restore', [ExamTypeController::class, 'restore'])->name('exam-types.restore');
    Route::delete('settings/exam-types/{id}/force-delete', [ExamTypeController::class, 'forceDelete'])->name('exam-types.force-delete');
});
