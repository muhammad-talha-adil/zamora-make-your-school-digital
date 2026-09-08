<?php

use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Exam\ExamDashboardController;
use App\Http\Controllers\Exam\ExamMarkingController;
use App\Http\Controllers\Exam\ExamPaperController;
use App\Http\Controllers\Exam\ExamRegistrationController;
use App\Http\Controllers\Exam\ExamReportCardController;
use App\Http\Controllers\Exam\ExamResultController;
use App\Http\Controllers\Exam\ExamRevaluationController;
use App\Http\Controllers\Exam\ExamSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('exams')->name('exam.')->middleware(['web', 'auth'])->group(function () {

    // ========================================
    // PAGE ROUTES - Specific routes first (before /{id})
    // ========================================

    // Exam Dashboard - MUST come before /{id} route
    Route::controller(ExamDashboardController::class)->group(function () {
        Route::get('/dashboard', 'indexPage')->name('dashboard.index-page')->middleware('permission:exam.view');
    });

    // Exam Papers / Date Sheet - MUST come before /{id} route
    Route::controller(ExamPaperController::class)->group(function () {
        Route::get('/papers', 'indexPage')->name('papers.index-page')->middleware('permission:exam.paper.view');
        Route::get('/papers/create', 'createPage')->name('papers.create-page')->middleware('permission:exam.paper.manage');
        Route::get('/papers/{id}/edit', 'editPage')->name('papers.edit-page')->middleware('permission:exam.paper.manage');
    });

    // Exam Registrations - MUST come before /{id} route
    Route::controller(ExamRegistrationController::class)->group(function () {
        Route::get('/registrations', 'indexPage')->name('registrations.index-page')->middleware('permission:exam.registration.manage|exam.view');
    });

    // Exam Marking - MUST come before /{id} route
    Route::controller(ExamMarkingController::class)->group(function () {
        Route::get('/marking', 'selectPage')->name('marking.select-page')->middleware('permission:exam.marks.enter|exam.marks.verify');
        Route::get('/marking/grid', 'gridPage')->name('marking.grid-page')->middleware('permission:exam.marks.enter|exam.marks.verify');
    });

    // Exam Results - MUST come before /{id} route
    Route::controller(ExamResultController::class)->group(function () {
        Route::get('/results', 'indexPage')->name('results.index-page')->middleware('permission:exam.result.view|exam.result.view.own');
        Route::get('/results/filter-options', 'getFilterOptions')->name('results.filter-options')->middleware('permission:exam.result.view|exam.result.view.own');
        Route::get('/results/list', 'index')->name('results.index')->middleware('permission:exam.result.view|exam.result.view.own');
        Route::get('/result-sheet', 'resultSheet')->name('results.sheet')->middleware('permission:exam.result.view');
        Route::get('/export-pdf', 'exportPDF')->name('results.export-pdf')->middleware('permission:exam.result.view');
        Route::get('/export-excel', 'exportExcel')->name('results.export-excel')->middleware('permission:exam.result.view');
    });

    // Exam Revaluations - MUST come before /{id} route
    Route::controller(ExamRevaluationController::class)->group(function () {
        Route::get('/revaluations', 'indexPage')->name('revaluations.index-page')->middleware('permission:exam.revaluation.manage');
    });

    // Exam Settings - MUST come before /{id} route
    Route::controller(ExamSettingsController::class)->group(function () {
        Route::get('/settings', 'indexPage')->name('settings.index-page')->middleware('permission:exam.settings');
        Route::get('/settings/grade-scales', 'gradeScalesPage')->name('settings.grade-scales-page')->middleware('permission:exam.settings');
        Route::get('/settings/grade-scales/create', 'createGradeScalePage')->name('settings.grade-scales.create-page')->middleware('permission:exam.settings');
        Route::get('/settings/grade-scales/{id}/edit', 'editGradeScalePage')->name('settings.grade-scales.edit-page')->middleware('permission:exam.settings');
    });

    // Exam list (index) - MUST come before /{id} route
    Route::controller(ExamController::class)->group(function () {
        Route::get('/', 'indexPage')->name('index-page')->middleware('permission:exam.view');
        Route::get('/create', 'createPage')->name('create-page')->middleware('permission:exam.manage');
    });

    // Exam list API - for AJAX calls
    Route::controller(ExamController::class)->group(function () {
        Route::get('/list', 'index')->name('index')->middleware('permission:exam.view');
    });

    // ========================================
    // What the school prints and hands over
    // ========================================

    Route::controller(ExamReportCardController::class)->group(function () {
        // The result card, and the run that prints a whole section.
        Route::get('/results/{resultHeaderId}/card', 'card')
            ->name('results.card')
            ->middleware('permission:exam.result.view|exam.result.view.own');
        Route::get('/{examId}/result-cards', 'sectionCards')
            ->name('results.section-cards')
            ->middleware('permission:exam.result.view');
        Route::put('/results/{resultHeaderId}/remarks', 'saveRemarks')
            ->name('results.remarks')
            ->middleware('permission:exam.marks.enter');

        // "Position: 3rd of 42" — worked out when the exam is published, and
        // again on request when a mark is corrected afterwards.
        Route::post('/{examId}/positions', 'recomputePositions')
            ->name('positions.recompute')
            ->middleware('permission:exam.result.publish');

        // The year, assembled from the terms.
        Route::get('/students/{studentId}/annual-result', 'annual')
            ->name('results.annual')
            ->middleware('permission:exam.result.view|exam.result.view.own');
        Route::get('/annual-results', 'annualForSection')
            ->name('results.annual-section')
            ->middleware('permission:exam.result.view');

        // The sheet pinned to the notice board.
        Route::get('/{examId}/datesheet', 'datesheet')
            ->name('datesheet')
            ->middleware('permission:exam.paper.view|exam.result.view.own');
    });

    // Grace marks — a decision, not a mark entry, so it sits behind the
    // verifying ability rather than the marking one.
    Route::controller(ExamMarkingController::class)->group(function () {
        Route::get('/marking/grace-candidates', 'graceCandidates')
            ->name('marking.grace-candidates')
            ->middleware('permission:exam.marks.verify');
        Route::put('/marking/lines/{lineId}/grace', 'giveGrace')
            ->name('marking.grace')
            ->middleware('permission:exam.marks.verify');
    });

    // ========================================
    // GENERIC ROUTES WITH {id} - Must be AFTER specific routes
    // ========================================

    // Exam Controller - Generic routes with {id} parameter
    Route::controller(ExamController::class)->group(function () {
        Route::get('/{id}', 'showPage')->name('show-page')->middleware('permission:exam.view');
        Route::get('/{id}/edit', 'editPage')->name('edit-page')->middleware('permission:exam.manage');
    });

    // Exam Result Controller - Routes with {id} parameters
    // NOTE: /results/list is defined above in PAGE ROUTES section (line 45)
    Route::controller(ExamResultController::class)->group(function () {
        Route::get('/students/{studentId}/results/{examId}', 'studentResult')->name('results.student')->middleware('permission:exam.result.view|exam.result.view.own');
        Route::get('/papers/{paperId}/report', 'paperWiseReport')->name('results.paper-report')->middleware('permission:exam.result.view');
    });

    // Exam Revaluation Controller - Routes with {id} parameters
    Route::controller(ExamRevaluationController::class)->group(function () {
        Route::get('/revaluations/list', 'index')->name('revaluations.index')->middleware('permission:exam.revaluation.manage');
        Route::get('/revaluations/{id}', 'review')->name('revaluations.review')->middleware('permission:exam.revaluation.manage');
        Route::get('/revaluations/{id}/history', 'history')->name('revaluations.history')->middleware('permission:exam.revaluation.manage|exam.result.view.own');
        Route::patch('/revaluations/{id}/approve', 'approve')->name('revaluations.approve')->middleware('permission:exam.revaluation.manage');
        Route::patch('/revaluations/{id}/reject', 'reject')->name('revaluations.reject')->middleware('permission:exam.revaluation.manage');
        Route::patch('/revaluations/{id}/apply-change', 'applyChange')->name('revaluations.apply-change')->middleware('permission:exam.revaluation.manage');
    });

    // Exam Settings Controller - Routes with {id} parameters
    Route::controller(ExamSettingsController::class)->group(function () {
        Route::get('/grade-scales/{id}/items', 'getGradeScaleItems')->name('grade-scales.items')->middleware('permission:exam.settings');
        Route::put('/grade-scales/{id}/items/{itemId}', 'updateGradeScaleItem')->name('grade-scales.items.update')->middleware('permission:exam.settings');
        Route::delete('/grade-scales/{id}/items/{itemId}', 'deleteGradeScaleItem')->name('grade-scales.items.destroy')->middleware('permission:exam.settings');
    });

    // ========================================
    // API ROUTES
    // ========================================

    // Exam Controller - API Routes
    Route::controller(ExamController::class)->group(function () {
        Route::post('/', 'store')->name('store')->middleware('permission:exam.manage');
        Route::put('/{id}', 'update')->name('update')->middleware('permission:exam.manage');
        Route::patch('/{id}/status', 'changeStatus')->name('status')->middleware('permission:exam.manage');
        Route::patch('/{id}/publish', 'publish')->name('publish')->middleware('permission:exam.result.publish');
        Route::patch('/{id}/unpublish', 'unpublish')->name('unpublish')->middleware('permission:exam.result.publish');
        Route::get('/{id}/readiness', 'readiness')->name('readiness')->middleware('permission:exam.result.publish|exam.manage');
        Route::patch('/{id}/lock', 'lock')->name('lock')->middleware('permission:exam.manage');
        Route::patch('/{id}/unlock', 'unlock')->name('unlock')->middleware('permission:exam.marks.verify');
        Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:exam.delete');
    });

    // Exam Paper Controller - API Routes
    Route::controller(ExamPaperController::class)->group(function () {
        Route::get('/papers/list', 'index')->name('papers.index')->middleware('permission:exam.paper.view');
        Route::post('/papers', 'store')->name('papers.store')->middleware('permission:exam.paper.manage');
        Route::put('/papers/{id}', 'update')->name('papers.update')->middleware('permission:exam.paper.manage');
        Route::post('/papers/bulk-create', 'bulkCreate')->name('papers.bulk-create')->middleware('permission:exam.paper.manage');
        Route::post('/papers/clash-check', 'clashCheck')->name('papers.clash-check')->middleware('permission:exam.paper.manage');
        Route::patch('/papers/{id}/cancel', 'cancel')->name('papers.cancel')->middleware('permission:exam.paper.manage');
        Route::delete('/papers/{id}', 'destroy')->name('papers.destroy')->middleware('permission:exam.paper.manage');

        // New routes for enhanced Add Paper workflow
        Route::get('/papers/sections-by-class', 'getSectionsByClass')->name('papers.sections-by-class')->middleware('permission:exam.paper.view');
        Route::get('/papers/papers-or-subjects', 'getPapersOrSubjects')->name('papers.papers-or-subjects')->middleware('permission:exam.paper.view');
        Route::get('/papers/subjects-by-class', 'getSubjectsByClassAndSection')->name('papers.subjects-by-class')->middleware('permission:exam.paper.view');
        Route::post('/papers/store-single', 'storeSinglePaper')->name('papers.store-single')->middleware('permission:exam.paper.manage');
        Route::post('/papers/store-bulk', 'storeBulkPapers')->name('papers.store-bulk')->middleware('permission:exam.paper.manage');

        // Date validation routes
        Route::get('/papers/exam-date-range', 'getExamDateRange')->name('papers.exam-date-range')->middleware('permission:exam.paper.view');
        Route::post('/papers/validate-date', 'validatePaperDate')->name('papers.validate-date')->middleware('permission:exam.paper.manage');
        Route::post('/papers/check-date-overlap', 'checkDateOverlap')->name('papers.check-date-overlap')->middleware('permission:exam.paper.manage');
    });

    // Exam Registration Controller - API Routes
    Route::controller(ExamRegistrationController::class)->group(function () {
        Route::get('/registrations/list', 'index')->name('registrations.index')->middleware('permission:exam.registration.manage|exam.view');
        Route::post('/registrations/generate', 'generateFromEnrollments')->name('registrations.generate')->middleware('permission:exam.registration.manage');
        Route::post('/registrations', 'store')->name('registrations.store')->middleware('permission:exam.registration.manage');
        Route::post('/registrations/bulk-register', 'bulkRegister')->name('registrations.bulk-register')->middleware('permission:exam.registration.manage');
        Route::patch('/registrations/{id}/withdraw', 'withdraw')->name('registrations.withdraw')->middleware('permission:exam.registration.manage');
    });

    // Exam Marking Controller - API Routes
    Route::controller(ExamMarkingController::class)->group(function () {
        Route::get('/marking/grid-data', 'getGrid')->name('marking.grid-data')->middleware('permission:exam.marks.enter|exam.marks.verify');
        Route::get('/marking/search-students', 'searchStudents')->name('marking.search-students')->middleware('permission:exam.marks.enter|exam.marks.verify');
        Route::post('/marking/save-row', 'saveRow')->name('marking.save-row')->middleware('permission:exam.marks.enter');
        Route::post('/marking/save-bulk', 'saveBulk')->name('marking.save-bulk')->middleware('permission:exam.marks.enter');
    });

    // Exam Revaluation Controller - API Routes
    Route::controller(ExamRevaluationController::class)->group(function () {
        Route::post('/revaluations/request', 'request')->name('revaluations.request')->middleware('permission:exam.revaluation.manage|exam.result.view.own');
    });

    // Exam Settings Controller - API Routes
    Route::controller(ExamSettingsController::class)->group(function () {
        Route::get('/grade-scales', 'getGradeScales')->name('grade-scales.index')->middleware('permission:exam.settings|exam.view');
        Route::post('/grade-scales', 'storeGradeScale')->name('grade-scales.store')->middleware('permission:exam.settings');
        Route::put('/grade-scales/{id}', 'updateGradeScale')->name('grade-scales.update')->middleware('permission:exam.settings');
        Route::delete('/grade-scales/{id}', 'deleteGradeScale')->name('grade-scales.destroy')->middleware('permission:exam.settings');
        Route::patch('/grade-scales/{id}/set-active', 'setActiveGradeScale')->name('grade-scales.set-active')->middleware('permission:exam.settings');
        Route::patch('/grade-scales/{id}/set-default', 'setDefaultGradeScale')->name('grade-scales.set-default')->middleware('permission:exam.settings');
        Route::post('/grade-scales/{id}/items', 'storeGradeScaleItem')->name('grade-scales.items.store')->middleware('permission:exam.settings');
    });

    // Exam Dashboard Controller - API Routes
    Route::controller(ExamDashboardController::class)->group(function () {
        Route::get('/dashboard/stats', 'getStats')->name('dashboard.stats')->middleware('permission:exam.view');
    });

});
