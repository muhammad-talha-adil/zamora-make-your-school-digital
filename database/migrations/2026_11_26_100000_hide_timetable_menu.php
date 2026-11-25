<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hide the staff-side Timetable menu (and children) until the module is reviewed.
     */
    public function up(): void
    {
        $this->setActive(false);
    }

    public function down(): void
    {
        $this->setActive(true);
    }

    private function setActive(bool $active): void
    {
        $ids = DB::table('menus')
            ->where('title', 'Timetable')
            ->where('role', 'like', '%owner%')
            ->pluck('id');

        DB::table('menus')->whereIn('id', $ids)->orWhereIn('parent_id', $ids)->update(['is_active' => $active]);
    }
};
