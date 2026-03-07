<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Main navigation items
        Menu::create([
            'title' => 'Dashboard',
            'icon' => 'layout-grid',
            'type' => 'main',
            'order' => 1,
            'is_active' => true,
        ]);

        Menu::create([
            'title' => 'Students',
            'icon' => 'users',
            'type' => 'main',
            'order' => 2,
            'is_active' => true,
        ]);

        // Student child menus
        Menu::create([
            'title' => 'New Admission',
            'icon' => 'user-plus',
            'type' => 'main',
            'order' => 1,
            'parent_id' => 2, // Students parent ID
            'is_active' => true,
            'url' => '/students/create',
        ]);

        Menu::create([
            'title' => 'Student List',
            'icon' => 'list',
            'type' => 'main',
            'order' => 2,
            'parent_id' => 2, // Students parent ID
            'is_active' => true,
            'url' => '/students',
        ]);

        // ==================== EXAM MENU ====================
        $exam = Menu::create([
            'title' => 'Exams',
            'icon' => 'clipboard-list',
            'type' => 'main',
            'order' => 3,
            'is_active' => true,
        ]);

        // Exam child menus
        Menu::create([
            'title' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'type' => 'main',
            'order' => 1,
            'parent_id' => $exam->id,
            'is_active' => true,
            'url' => '/exams/dashboard',
        ]);

        Menu::create([
            'title' => 'Exams',
            'icon' => 'file-text',
            'type' => 'main',
            'order' => 2,
            'parent_id' => $exam->id,
            'is_active' => true,
            'url' => '/exams',
        ]);

        

        // Menu::create([
        //     'title' => 'Papers / Date Sheet',
        //     'icon' => 'calendar',
        //     'type' => 'main',
        //     'order' => 3,
        //     'parent_id' => $exam->id,
        //     'is_active' => true,
        //     'url' => '/exams/papers',
        // ]); 

        // Menu::create([
        //     'title' => 'Registrations',
        //     'icon' => 'user-check',
        //     'type' => 'main',
        //     'order' => 4,
        //     'parent_id' => $exam->id,
        //     'is_active' => true,
        //     'url' => '/exams/registrations',
        // ]);

        // Menu::create([
        //     'title' => 'Marking/Results',
        //     'icon' => 'edit-3',
        //     'type' => 'main',
        //     'order' => 5,
        //     'parent_id' => $exam->id,
        //     'is_active' => true,
        // ]);

        // Marking sub-menu under Exams
        // Menu::create([
        //     'title' => 'Marking',
        //     'icon' => 'edit-3',
        //     'type' => 'main',
        //     'order' => 4,
        //     'parent_id' => $exam->id,
        //     'is_active' => true,
        //     'url' => '/exams/marking',
        // ]);

        // // Results sub-menu under Exams
        // Menu::create([
        //     'title' => 'Results',
        //     'icon' => 'bar-chart',
        //     'type' => 'main',
        //     'order' => 5,
        //     'parent_id' => $exam->id,
        //     'is_active' => true,
        //     'url' => '/exams/results',
        // ]);
        // Menu::create([
        //     'title' => 'Marking',
        //     'icon' => 'edit-3',
        //     'type' => 'main',
        //     'order' => 5,
        //     'parent_id' => $exam->id,
        //     'is_active' => true,
        //     'url' => '/exams/marking',
        // ]);

        // Menu::create([
        //     'title' => 'Results',
        //     'icon' => 'bar-chart',
        //     'type' => 'main',
        //     'order' => 6,
        //     'parent_id' => $exam->id,
        //     'is_active' => true,
        //     'url' => '/exams/results',
        // ]);

        // Menu::create([
        //     'title' => 'Revaluations',
        //     'icon' => 'refresh',
        //     'type' => 'main',
        //     'order' => 7,
        //     'parent_id' => $exam->id,
        //     'is_active' => true,
        //     'url' => '/exams/revaluations',
        // ]);

        Menu::create([
            'title' => 'Settings',
            'icon' => 'settings',
            'type' => 'main',
            'order' => 6,
            'parent_id' => $exam->id,
            'is_active' => true,
            'url' => '/exams/settings',
        ]);

        // ==================== ATTENDANCE MENU ====================
        $attendance = Menu::create([
            'title' => 'Attendance',
            'icon' => 'calendar-check',
            'type' => 'main',
            'order' => 5,
            'is_active' => true,
        ]);

        // Attendance child menus
        Menu::create([
            'title' => 'Attendance List',
            'icon' => 'list',
            'type' => 'main',
            'order' => 1,
            'parent_id' => $attendance->id,
            'is_active' => true,
            'url' => '/attendance',
        ]);

        Menu::create([
            'title' => 'Mark Attendance',
            'icon' => 'check-circle',
            'type' => 'main',
            'order' => 2,
            'parent_id' => $attendance->id,
            'is_active' => true,
            'url' => '/attendance/create',
        ]);

        Menu::create([
            'title' => 'Student Reports',
            'icon' => 'file-bar-chart',
            'type' => 'main',
            'order' => 3,
            'parent_id' => $attendance->id,
            'is_active' => true,
            'url' => '/attendance/class/report',
        ]);

        Menu::create([
            'title' => 'Settings',
            'icon' => 'settings',
            'type' => 'main',
            'order' => 4,
            'parent_id' => $attendance->id,
            'is_active' => true,
            'url' => '/attendance/settings',
        ]);

        // ==================== INVENTORY MENU ====================
        $inventory = Menu::create([
            'title' => 'Inventory',
            'icon' => 'package',
            'type' => 'main',
            'order' => 6,
            'is_active' => true,
        ]);

        // Inventory child menus - Consolidated to 4 submenus
        Menu::create([
            'title' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'type' => 'main',
            'order' => 1,
            'parent_id' => $inventory->id,
            'is_active' => true,
            'url' => '/inventory',
        ]);

        Menu::create([
            'title' => 'Items & Stock',
            'icon' => 'warehouse',
            'type' => 'main',
            'order' => 2,
            'parent_id' => $inventory->id,
            'is_active' => true,
            'url' => '/inventory/items-stock',
        ]);

        Menu::create([
            'title' => 'Purchases',
            'icon' => 'shopping-cart',
            'type' => 'main',
            'order' => 3,
            'parent_id' => $inventory->id,
            'is_active' => true,
            'url' => '/inventory/purchases-manage',
        ]);

        Menu::create([
            'title' => 'Student Inventory',
            'icon' => 'user-check',
            'type' => 'main',
            'order' => 4,
            'parent_id' => $inventory->id,
            'is_active' => true,
            'url' => '/inventory/student-manage',
        ]);

        // Footer navigation items
        $settings = Menu::create([
            'title' => 'Settings',
            'icon' => 'settings',
            'type' => 'footer',
            'order' => 1,
            'is_active' => true,
        ]);

        Menu::create([
            'title' => 'Profile',
            'icon' => 'user',
            'type' => 'footer',
            'order' => 1,
            'parent_id' => $settings->id,
            'is_active' => true,
        ]);

        Menu::create([
            'title' => 'Appearance',
            'icon' => 'palette',
            'type' => 'footer',
            'order' => 2,
            'parent_id' => $settings->id,
            'is_active' => true,
        ]);

        Menu::create([
            'title' => 'School Profile',
            'icon' => 'building',
            'type' => 'footer',
            'order' => 3,
            'parent_id' => $settings->id,
            'is_active' => true,
        ]);

        Menu::create([
            'title' => 'Menu Settings',
            'icon' => 'cog',
            'type' => 'footer',
            'order' => 4,
            'parent_id' => $settings->id,
            'is_active' => true,
        ]);
    }
}