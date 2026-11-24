<?php

namespace App\Console\Commands;

use App\Models\Menu;
use Illuminate\Console\Command;

/**
 * Idempotent: creates or updates the five Transport sidebar entries and the
 * two Transport entries under Students, with the permission each page's route
 * is gated on. Safe to run on a live database; nothing is deleted except the
 * old Transport children that no longer exist.
 */
class SyncTransportMenus extends Command
{
    protected $signature = 'transport:sync-menus';

    protected $description = 'Create or update the Transport sidebar menus (and the Students > Transport entries)';

    public function handle(): int
    {
        $transport = Menu::whereNull('parent_id')->where('title', 'Transport')->first();
        $students = Menu::whereNull('parent_id')->where('title', 'Students')->first();

        if (! $transport || ! $students) {
            $this->error('Transport or Students menu not found; seed the menus first.');

            return self::FAILURE;
        }

        $staff = 'developer,owner,campus_admin,receptionist';

        $rows = [
            [$transport, 'Dashboard', 'layout-dashboard', 1, '/transport', 'developer,owner,campus_admin,driver,receptionist', 'transport.view.own'],
            [$transport, 'Vehicles', 'bus', 2, '/transport/vehicles', $staff, 'transport.vehicle.manage'],
            [$transport, 'Routes & Stops', 'map-pin', 3, '/transport/routes', $staff, 'transport.route.manage'],
            [$transport, 'Expenses', 'receipt', 4, '/transport/expenses', $staff, 'transport.expense.manage'],
            [$transport, 'Reports', 'bar-chart-3', 5, '/transport/reports', $staff, 'transport.view'],
            [$students, 'Transport Assignment', 'bus', 6, '/students/transport', $staff, 'transport.assignment.manage'],
            [$students, 'Transport Monthly Dues', 'receipt', 7, '/students/transport-dues', $staff, 'transport.assignment.manage'],
        ];

        foreach ($rows as [$parent, $title, $icon, $order, $url, $role, $permission]) {
            $menu = Menu::withTrashed()->where('parent_id', $parent->id)->where('url', $url)->first()
                ?? Menu::withTrashed()->where('parent_id', $parent->id)->where('title', $title)->first();

            $attributes = [
                'title' => $title,
                'icon' => $icon,
                'type' => 'main',
                'order' => $order,
                'parent_id' => $parent->id,
                'is_active' => true,
                'url' => $url,
                'role' => $role,
                'permission' => $permission,
            ];

            if ($menu) {
                $menu->restore();
                $menu->update($attributes);
            } else {
                Menu::create($attributes);
            }
        }

        $this->info('Transport menus are in sync.');

        return self::SUCCESS;
    }
}
