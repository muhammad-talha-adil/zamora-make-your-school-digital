<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Student;
use App\Models\ThemeSetting;
use App\Repositories\StudentRepository;
use App\Services\AttendanceService;
use App\Services\GuardianService;
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
        // Register AttendanceService as a singleton
        $this->app->singleton(AttendanceService::class, function ($app) {
            return new AttendanceService;
        });

        // Register StudentUserService
        $this->app->singleton(StudentUserService::class, function ($app) {
            return new StudentUserService;
        });

        // Register GuardianService
        $this->app->singleton(GuardianService::class, function ($app) {
            return new GuardianService;
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

        // Register gates for permissions
        Gate::before(function ($user, $ability) {
            if ($user->userRoles()->where('is_active', true)->whereHas('role', function ($query) {
                $query->where('name', 'developer');
            })->exists()) {
                return true;
            }
        });

        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            Gate::define($permission->key, function ($user) use ($permission) {
                return $user->userRoles()->where('is_active', true)->whereHas('role.permissions', function ($query) use ($permission) {
                    $query->where('permissions.id', $permission->id);
                })->exists();
            });
        }
    }
}
