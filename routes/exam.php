<?php
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Exam\ExamPaperController;
use App\Http\Controllers\Exam\ExamRegistrationController;
use App\Http\Controllers\Exam\ExamMarkingController;
use App\Http\Controllers\Exam\ExamResultController;
use App\Http\Controllers\Exam\ExamRevaluationController;
use App\Http\Controllers\Exam\ExamSettingsController;
use App\Http\Controllers\Exam\ExamDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('exams')->name('exam.')->middleware(['web', 'auth'])->group(function () {

    // ========================================
    // PAGE ROUTES - Specific routes first (before /{id})
    // ========================================

    // Exam Dashboard - MUST come before /{id} route
    Route::controller(ExamDashboardController::class)->group(function () {
        Route::get('/dashboard', 'indexPage')->name('dashboard.index-page');
    });

    // Exam Papers / Date Sheet - MUST come before /{id} route
    Route::controller(ExamPaperController::class)->group(function () {
        Route::get('/papers', 'indexPage')->name('papers.index-page');
        Route::get('/papers/create', 'createPage')->name('papers.create-page');
        Route::get('/papers/{id}/edit', 'editPage')->name('papers.edit-page');
    });

    // Exam Registrations - MUST come before /{id} route
    Route::controller(ExamRegistrationController::class)->group(function () {
        Route::get('/registrations', 'indexPage')->name('registrations.index-page');
    });

    // Exam Marking - MUST come before /{id} route
    Route::controller(ExamMarkingController::class)->group(function () {
        Route::get('/marking', 'selectPage')->name('marking.select-page');
        Route::get('/marking/grid', 'gridPage')->name('marking.grid-page');
    });

    // Exam Results - MUST come before /{id} route
    Route::controller(ExamResultController::class)->group(function () {
        Route::get('/results', 'indexPage')->name('results.index-page');
        Route::get('/results/filter-options', 'getFilterOptions')->name('results.filter-options');
        Route::get('/results/list', 'index')->name('results.index');
        Route::get('/result-sheet', 'resultSheet')->name('results.sheet');
        Route::get('/export-pdf', 'exportPDF')->name('results.export-pdf');
        Route::get('/export-excel', 'exportExcel')->name('results.export-excel');
    });

    // Exam Revaluations - MUST come before /{id} route
    Route::controller(ExamRevaluationController::class)->group(function () {
        Route::get('/revaluations', 'indexPage')->name('revaluations.index-page');
    });

    // Exam Settings - MUST come before /{id} route
    Route::controller(ExamSettingsController::class)->group(function () {
        Route::get('/settings', 'indexPage')->name('settings.index-page');
        Route::get('/settings/grade-scales', 'gradeScalesPage')->name('settings.grade-scales-page');
        Route::get('/settings/grade-scales/create', 'createGradeScalePage')->name('settings.grade-scales.create-page');
        Route::get('/settings/grade-scales/{id}/edit', 'editGradeScalePage')->name('settings.grade-scales.edit-page');
    });

    // Exam list (index) - MUST come before /{id} route
    Route::controller(ExamController::class)->group(function () {
        Route::get('/', 'indexPage')->name('index-page');
        Route::get('/create', 'createPage')->name('create-page');
    });

    // Exam list API - for AJAX calls
    Route::controller(ExamController::class)->group(function () {
        Route::get('/list', 'index')->name('index');
    });

    // ========================================
    // GENERIC ROUTES WITH {id} - Must be AFTER specific routes
    // ========================================

    // Exam Controller - Generic routes with {id} parameter
    Route::controller(ExamController::class)->group(function () {
        Route::get('/{id}', 'showPage')->name('show-page');
        Route::get('/{id}/edit', 'editPage')->name('edit-page');
    });

    // Exam Result Controller - Routes with {id} parameters
    // NOTE: /results/list is defined above in PAGE ROUTES section (line 45)
    Route::controller(ExamResultController::class)->group(function () {
        Route::get('/students/{studentId}/results/{examId}', 'studentResult')->name('results.student');
        Route::get('/papers/{paperId}/report', 'paperWiseReport')->name('results.paper-report');
    });

    // Exam Revaluation Controller - Routes with {id} parameters
    Route::controller(ExamRevaluationController::class)->group(function () {
        Route::get('/revaluations/list', 'index')->name('revaluations.index');
        Route::get('/revaluations/{id}', 'review')->name('revaluations.review');
        Route::get('/revaluations/{id}/history', 'history')->name('revaluations.history');
        Route::patch('/revaluations/{id}/approve', 'approve')->name('revaluations.approve');
        Route::patch('/revaluations/{id}/reject', 'reject')->name('revaluations.reject');
        Route::patch('/revaluations/{id}/apply-change', 'applyChange')->name('revaluations.apply-change');
    });

    // Exam Settings Controller - Routes with {id} parameters
    Route::controller(ExamSettingsController::class)->group(function () {
        Route::get('/grade-scales/{id}/items', 'getGradeScaleItems')->name('grade-scales.items');
        Route::put('/grade-scales/{id}/items/{itemId}', 'updateGradeScaleItem')->name('grade-scales.items.update');
        Route::delete('/grade-scales/{id}/items/{itemId}', 'deleteGradeScaleItem')->name('grade-scales.items.destroy');
    });

    // ========================================
    // API ROUTES
    // ========================================

    // Exam Controller - API Routes
    Route::controller(ExamController::class)->group(function () {
        Route::post('/', 'store')->name('store');
        Route::put('/{id}', 'update')->name('update');
        Route::patch('/{id}/status', 'changeStatus')->name('status');
        Route::patch('/{id}/publish', 'publish')->name('publish');
        Route::patch('/{id}/lock', 'lock')->name('lock');
        Route::patch('/{id}/unlock', 'unlock')->name('unlock');
        Route::delete('/{id}', 'destroy')->name('destroy');
    });

    // Exam Paper Controller - API Routes
    Route::controller(ExamPaperController::class)->group(function () {
        Route::get('/papers/list', 'index')->name('papers.index');
        Route::post('/papers', 'store')->name('papers.store');
        Route::put('/papers/{id}', 'update')->name('papers.update');
        Route::post('/papers/bulk-create', 'bulkCreate')->name('papers.bulk-create');
        Route::post('/papers/clash-check', 'clashCheck')->name('papers.clash-check');
        Route::patch('/papers/{id}/cancel', 'cancel')->name('papers.cancel');
        Route::delete('/papers/{id}', 'destroy')->name('papers.destroy');
        
        // New routes for enhanced Add Paper workflow
        Route::get('/papers/sections-by-class', 'getSectionsByClass')->name('papers.sections-by-class');
        Route::get('/papers/papers-or-subjects', 'getPapersOrSubjects')->name('papers.papers-or-subjects');
        Route::get('/papers/subjects-by-class', 'getSubjectsByClassAndSection')->name('papers.subjects-by-class');
        Route::post('/papers/store-single', 'storeSinglePaper')->name('papers.store-single');
        Route::post('/papers/store-bulk', 'storeBulkPapers')->name('papers.store-bulk');
        
        // Date validation routes
        Route::get('/papers/exam-date-range', 'getExamDateRange')->name('papers.exam-date-range');
        Route::post('/papers/validate-date', 'validatePaperDate')->name('papers.validate-date');
        Route::post('/papers/check-date-overlap', 'checkDateOverlap')->name('papers.check-date-overlap');
    });

    // Exam Registration Controller - API Routes
    Route::controller(ExamRegistrationController::class)->group(function () {
        Route::get('/registrations', 'index')->name('registrations.index');
        Route::post('/registrations/generate', 'generateFromEnrollments')->name('registrations.generate');
        Route::post('/registrations', 'store')->name('registrations.store');
        Route::post('/registrations/bulk-register', 'bulkRegister')->name('registrations.bulk-register');
        Route::patch('/registrations/{id}/withdraw', 'withdraw')->name('registrations.withdraw');
    });

    // Exam Marking Controller - API Routes
    Route::controller(ExamMarkingController::class)->group(function () {
        Route::get('/marking/grid-data', 'getGrid')->name('marking.grid-data');
        Route::get('/marking/search-students', 'searchStudents')->name('marking.search-students');
        Route::post('/marking/save-row', 'saveRow')->name('marking.save-row');
        Route::post('/marking/save-bulk', 'saveBulk')->name('marking.save-bulk');
    });

    // Exam Revaluation Controller - API Routes
    Route::controller(ExamRevaluationController::class)->group(function () {
        Route::post('/revaluations/request', 'request')->name('revaluations.request');
    });

    // Exam Settings Controller - API Routes
    Route::controller(ExamSettingsController::class)->group(function () {
        Route::get('/grade-scales', 'getGradeScales')->name('grade-scales.index');
        Route::post('/grade-scales', 'storeGradeScale')->name('grade-scales.store');
        Route::put('/grade-scales/{id}', 'updateGradeScale')->name('grade-scales.update');
        Route::delete('/grade-scales/{id}', 'deleteGradeScale')->name('grade-scales.destroy');
        Route::patch('/grade-scales/{id}/set-active', 'setActiveGradeScale')->name('grade-scales.set-active');
        Route::patch('/grade-scales/{id}/set-default', 'setDefaultGradeScale')->name('grade-scales.set-default');
        Route::post('/grade-scales/{id}/items', 'storeGradeScaleItem')->name('grade-scales.items.store');
    });

    // Exam Dashboard Controller - API Routes
    Route::controller(ExamDashboardController::class)->group(function () {
        Route::get('/dashboard/stats', 'getStats')->name('dashboard.stats');
    });

});
