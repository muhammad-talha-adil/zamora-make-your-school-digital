<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\StaffProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolHubController extends Controller
{
    /**
     * One central "School Setting" page (#101) — a heading per module, each
     * linking out to that module's own existing settings screen rather than
     * re-plumbing every controller's props into one giant tabbed page. Most
     * of the settings pages being linked (Fee, Exam, Staff, School Profile)
     * are already substantial tab-hubs in their own right, so a link-out
     * card is simpler and safer here than inlining every one of them as a
     * full tab on top of a tab.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $modules = [
            [
                'key' => 'school',
                'title' => 'School Profile',
                'description' => 'School info, campuses, campus types, classes, sections, sessions and subjects.',
                'route' => 'school-profile.show',
                'visible' => $user?->can('school.profile.manage') ?? false,
            ],
            [
                'key' => 'attendance',
                'title' => 'Attendance',
                'description' => 'Leave types, holidays, shift timings and other attendance rules.',
                'route' => 'attendance.settings',
                'visible' => $user?->can('attendance.settings') ?? false,
            ],
            [
                'key' => 'fee',
                'title' => 'Fee',
                'description' => 'Fee heads, discount types and fine rules.',
                'route' => 'fee.settings.index',
                'visible' => $user?->hasAnyPermission([
                    'fee.head.manage', 'fee.discount.manage', 'fee.fine.manage', 'fee.structure.manage',
                ]) ?? false,
            ],
            [
                'key' => 'exam',
                'title' => 'Exam',
                'description' => 'Exam types, grading scales and marking rules.',
                'route' => 'exam.settings.index-page',
                'visible' => $user?->can('exam.settings') ?? false,
            ],
            [
                'key' => 'staff',
                'title' => 'Staff',
                'description' => 'Departments, designations and salary components.',
                'route' => 'staff.settings.page',
                'visible' => Gate::check('manageStructure', StaffProfile::class),
            ],
            [
                'key' => 'inventory',
                'title' => 'Inventory',
                'description' => 'Inventory types, items and low-stock thresholds.',
                'route' => 'inventory.settings',
                'visible' => $user?->can('inventory.view') ?? false,
            ],
        ];

        return Inertia::render('settings/SchoolSettingHub', [
            'modules' => collect($modules)
                ->filter(fn (array $module) => $module['visible'])
                ->map(fn (array $module) => [
                    'key' => $module['key'],
                    'title' => $module['title'],
                    'description' => $module['description'],
                    'url' => route($module['route']),
                ])
                ->values(),
        ]);
    }
}
