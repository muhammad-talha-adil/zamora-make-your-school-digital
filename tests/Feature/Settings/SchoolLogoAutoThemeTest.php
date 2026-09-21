<?php

use App\Models\Permission;
use App\Models\School;
use App\Models\ThemeSetting;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * The client extracts a palette from an uploaded logo and posts it as a
 * `theme_colors` JSON string alongside the school-profile update. The
 * controller must persist it into the same `theme_settings` rows the
 * Appearance screen manages (see SchoolController::saveAutoTheme), so it
 * applies app-wide through the existing theming pipeline.
 */
function autoThemePayload(): string
{
    $palette = [
        'sidebar_bg' => '#101010',
        'sidebar_text' => '#ffffff',
        'sidebar_active_bg' => '#202020',
        'sidebar_active_text' => '#ffffff',
        'header_bg' => '#303030',
        'header_text' => '#ffffff',
        'content_bg' => '#f5f5f5',
        'content_text' => '#0a0a0a',
        'card_bg' => '#ffffff',
        'card_text' => '#0a0a0a',
        'primary' => '#405060',
        'primary_text' => '#ffffff',
        'danger' => '#b00020',
        'danger_text' => '#ffffff',
    ];

    return json_encode(['light' => $palette, 'dark' => $palette]);
}

test('a user with school.theme.manage gets an auto-generated theme saved for both modes', function () {
    Permission::firstOrCreate(['name' => 'school.profile.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'school.theme.manage', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo(['school.profile.manage', 'school.theme.manage']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    School::create(['name' => 'Zamora School']);

    $this->actingAs($user)
        ->post(route('school-profile.update'), [
            'name' => 'Zamora School',
            'theme_colors' => autoThemePayload(),
        ])
        ->assertSessionHasNoErrors();

    $light = ThemeSetting::where('mode', 'light')->first();
    $dark = ThemeSetting::where('mode', 'dark')->first();

    expect($light)->not->toBeNull()
        ->and($light->colors_json['card_bg'])->toBe('#ffffff')
        ->and($light->updated_by)->toBe($user->id)
        ->and($dark)->not->toBeNull()
        ->and($dark->colors_json['primary'])->toBe('#405060');
});

test('a user without school.theme.manage does not get an auto-theme saved even if one is posted', function () {
    Permission::firstOrCreate(['name' => 'school.profile.manage', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('school.profile.manage');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    School::create(['name' => 'Zamora School']);

    $this->actingAs($user)
        ->post(route('school-profile.update'), [
            'name' => 'Zamora School',
            'theme_colors' => autoThemePayload(),
        ])
        ->assertSessionHasNoErrors();

    expect(ThemeSetting::count())->toBe(0);
});

test('a malformed theme_colors payload is ignored without failing the school-profile save', function () {
    Permission::firstOrCreate(['name' => 'school.profile.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'school.theme.manage', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo(['school.profile.manage', 'school.theme.manage']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    School::create(['name' => 'Zamora School']);

    $this->actingAs($user)
        ->post(route('school-profile.update'), [
            'name' => 'Zamora School',
            'theme_colors' => 'not-json',
        ])
        ->assertSessionHasNoErrors();

    expect(ThemeSetting::count())->toBe(0);
});
