<?php

namespace App\Providers;

use App\Models\Student;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Repositories\StudentRepository;
use App\Services\AttendanceService;
use App\Services\Exam\GradeResolver;
use App\Services\GuardianService;
use App\Services\SchoolEmailService;
use App\Services\StudentService;
use App\Services\StudentUserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * One grading scale per request.
         *
         * The resolver holds what it has resolved and the bands it has read.
         * Two instances mean two cold caches — and, worse, two answers to
         * "which scale applies", which is exactly the drift this class exists
         * to stop.
         */
        $this->app->singleton(GradeResolver::class);

        // Register AttendanceService as a singleton
        $this->app->singleton(AttendanceService::class, function ($app) {
            return new AttendanceService;
        });

        // Register StudentUserService
        $this->app->singleton(StudentUserService::class, function ($app) {
            return new StudentUserService(
                $app->make(SchoolEmailService::class)
            );
        });

        // Register GuardianService
        $this->app->singleton(GuardianService::class, function ($app) {
            return new GuardianService(
                $app->make(SchoolEmailService::class)
            );
        });

        // Register StudentRepository
        $this->app->singleton(StudentRepository::class, function ($app) {
            return new StudentRepository(
                $app->make(StudentUserService::class),
                $app->make(GuardianService::class)
            );
        });

        // Register StudentService
        $this->app->singleton(StudentService::class, function ($app) {
            return new StudentService(
                $app->make(StudentRepository::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Share theme settings with Inertia
        Inertia::share('themeSettings', function () {
            return ThemeSetting::all()->keyBy('mode')->map(function ($setting) {
                return $setting->colors_json;
            })->toArray();
        });

        // Register explicit route model binding for 'student'
        Route::model('student', Student::class);

        // Custom route binder for student with eager loading
        Route::bind('student', function ($value) {
            return Student::with([
                'user',
                'gender',
                'studentStatus',
                'studentGuardians.guardian.user',
                'studentGuardians.relation',
                'enrollmentRecords.campus',
                'enrollmentRecords.class',
                'enrollmentRecords.section',
                'enrollmentRecords.session',
            ])->findOrFail($value);
        });

        // Every ability is granted to the developer role, which owns the
        // subscription and system tooling the owner deliberately cannot reach.
        //
        // Individual permissions are no longer defined as gates here: Spatie
        // resolves them itself and caches the lookup, where the old loop ran a
        // query per permission on every request.
        Gate::before(function (User $user) {
            return $user->hasRole('developer') ? true : null;
        });
    }
}
