<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The menu the sidebar is built from.
 *
 * Three things were wrong or missing, all found while checking where the new
 * screens should live:
 *
 *  - **`/exams/exams` is not a route.** The exam list is at `/exams`, so that
 *    entry fell through to `/exams/{id}`, tried to find an exam called "exams",
 *    and 404ed. It has been in the menu the whole time.
 *  - **Half the exam module was unreachable from the sidebar.** Papers,
 *    Marking, Registrations, Results and Revaluations all have screens and none
 *    of them were listed — only the Exam Dashboard linked to them. The orders
 *    3, 4 and 5 were left empty, as though somebody meant to.
 *  - **The new screens have nowhere to be.** Promotion, enquiries and the
 *    annual result are their own questions, not a tab on something else.
 *
 * Written with `updateOrInsert` on the path, so running it twice changes
 * nothing — the seeder lesson.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exams = DB::table('menus')->where('path', '/exams')->whereNull('parent_id')->first();
        $students = DB::table('menus')
            ->where('path', '/students')
            ->whereNull('parent_id')
            ->first();

        if ($exams) {
            // The list is at `/exams`, not `/exams/exams`. Scoped to the
            // child, because the parent carries `/exams` as well.
            DB::table('menus')
                ->where('path', '/exams/exams')
                ->where('parent_id', $exams->id)
                ->update(['path' => '/exams']);

            $this->put($exams->id, [
                ['All Exams', '/exams', 'ClipboardList', 2],
                ['Date Sheet & Papers', '/exams/papers', 'CalendarDays', 3],
                ['Marking', '/exams/marking', 'PencilLine', 4],
                ['Grace Marks', '/exams/marking/grace', 'HeartHandshake', 5],
                ['Results', '/exams/results', 'Trophy', 6],
                ['Annual Result', '/exams/annual-result', 'CalendarRange', 7],
                ['Registrations', '/exams/registrations', 'UserCheck', 8],
                ['Rechecking', '/exams/revaluations', 'RotateCcw', 9],
                // Settings last, where a school looks for it. It sat on 6,
                // which the annual result now holds.
                ['Settings', '/exams/settings', 'Settings', 10],
            ]);
        }

        if ($students) {
            $this->put($students->id, [
                ['Admission Enquiries', '/students/enquiries', 'PhoneCall', 20],
                ['Promotion', '/students/promotion', 'ArrowUpCircle', 21],
            ]);
        }
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: int}>  $items
     */
    private function put(int $parentId, array $items): void
    {
        foreach ($items as [$title, $path, $icon, $order]) {
            // Matched on the path **and** the parent: a child may legitimately
            // carry the same path as the parent it sits under, and matching on
            // the path alone would rewrite the parent into its own child.
            DB::table('menus')->updateOrInsert(
                ['path' => $path, 'parent_id' => $parentId],
                [
                    'title' => $title,
                    'icon' => $icon,
                    'order' => $order,
                    'type' => 'main',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('menus')->whereNotNull('parent_id')->whereIn('path', [
            '/exams/papers',
            '/exams/marking',
            '/exams/marking/grace',
            '/exams/results',
            '/exams/annual-result',
            '/exams/registrations',
            '/exams/revaluations',
            '/students/enquiries',
            '/students/promotion',
        ])->delete();
    }
};
