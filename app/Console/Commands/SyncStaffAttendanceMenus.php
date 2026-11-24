<?php

namespace App\Console\Commands;

use App\Models\Menu;
use Illuminate\Console\Command;

/**
 * Idempotent: creates or updates the Staff > Attendance Report sidebar entry.
 * Safe to run on a live database.
 */
class SyncStaffAttendanceMenus extends Command
{
    protected $signature = 'staff:sync-attendance-menus';

    protected $description = 'Create or update the Staff > Attendance Report sidebar menu';

    public function handle(): int
    {
        $staff = Menu::whereNull('parent_id')->where('title', 'Staff')->first();

        if (! $staff) {
            $this->error('Staff menu not found; seed the menus first.');

            return self::FAILURE;
        }

        $attributes = [
            'title' => 'Attendance Report',
            'icon' => 'bar-chart-3',
            'type' => 'main',
            'order' => 7,
            'parent_id' => $staff->id,
            'is_active' => true,
            'url' => '/staff/attendance/report',
            'role' => 'developer,owner,campus_admin',
            'permission' => 'staff.attendance.view',
        ];

        $menu = Menu::withTrashed()->where('parent_id', $staff->id)->where('url', $attributes['url'])->first();

        if ($menu) {
            $menu->restore();
            $menu->update($attributes);
        } else {
            Menu::create($attributes);
        }

        $this->info('Staff attendance menus are in sync.');

        return self::SUCCESS;
    }
}
