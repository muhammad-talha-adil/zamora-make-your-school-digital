<?php

namespace App\Providers;

use App\Models\Student;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Repositories\StudentRepository;
use App\Services\AttendanceService;
use App\Services\Exam\GradeResolver;
use App\Services\GuardianService;
use App\Services\Student\AdmissionCredentials;
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
         * These are singletons and nothing more.
         *
         * Each used to be a closure calling `new` with its dependencies typed
         * out by hand, which is not what the container is for and cost nothing
         * until the day one of them gained a dependency — at which point every
         * admission returned a 500, because the closure was still passing two
         * arguments to a constructor that now takes three.
         *
         * The container reads the constructor. Let it.
         */
        /*
         * The plaintext logins made during one admission, carried from the
         * moment they are generated to the moment the slip prints them.
         * Request-scoped, so they never outlive the page that shows them.
         */
        $this->app->scoped(AdmissionCredentials::class);

        $this->app->singleton(AttendanceService::class);
        $this->app->singleton(StudentUserService::class);
        $this->app->singleton(GuardianService::class);
        $this->app->singleton(StudentRepository::class);
        $this->app->singleton(StudentService::class);

        /*
         * One grading scale per request.
         *
         * The resolver holds what it has resolved and the bands it has read.
         * Two instances mean two cold caches — and, worse, two answers to
         * "which scale applies", which is exactly the drift that class exists
         * to stop.
         */
        $this->app->singleton(GradeResolver::class);
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
