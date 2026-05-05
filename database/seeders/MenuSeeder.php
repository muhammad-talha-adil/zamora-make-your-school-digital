<?php

namespace Database\Seeders;

use App\Models\Menu;
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
            'order' => 4,
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

        // ==================== FEE MENU ====================
        $fee = Menu::create([
            'title' => 'Fee Management',
            'icon' => 'banknote',
            'type' => 'main',
            'order' => 5,
            'is_active' => true,
        ]);

        // Fee child menus - PRIMARY (Main Tasks - shown in sidebar)
        Menu::create([
            'title' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'type' => 'main',
            'order' => 1,
            'parent_id' => $fee->id,
            'is_active' => true,
            'url' => '/fee/dashboard',
        ]);

        Menu::create([
            'title' => 'Fee Structures',
            'icon' => 'layers',
            'type' => 'main',
            'order' => 2,
            'parent_id' => $fee->id,
            'is_active' => true,
            'url' => '/fee/structures',
        ]);

        Menu::create([
            'title' => 'Vouchers',
            'icon' => 'file-text',
            'type' => 'main',
            'order' => 4,
            'parent_id' => $fee->id,
            'is_active' => true,
            'url' => '/fee/vouchers',
        ]);

        Menu::create([
            'title' => 'Payments',
            'icon' => 'credit-card',
            'type' => 'main',
            'order' => 5,
            'parent_id' => $fee->id,
            'is_active' => true,
            'url' => '/fee/payments',
        ]);

        Menu::create([
            'title' => 'Reports',
            'icon' => 'bar-chart',
            'type' => 'main',
            'order' => 7,
            'parent_id' => $fee->id,
            'is_active' => true,
            'url' => '/fee/reports',
        ]);

        // Settings (leads to /fee/settings which contains Fee Heads, Discount Types, Fine Rules)
        Menu::create([
            'title' => 'Settings',
            'icon' => 'settings',
            'type' => 'main',
            'order' => 8,
            'parent_id' => $fee->id,
            'is_active' => true,
            'url' => '/fee/settings',
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

        // ==================== FINANCE MENU ====================
        $finance = Menu::create([
            'title' => 'Finance',
            'icon' => 'wallet',
            'type' => 'main',
            'order' => 7,
            'is_active' => true,
        ]);

        // Finance child menus
        Menu::create([
            'title' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'type' => 'main',
            'order' => 1,
            'parent_id' => $finance->id,
            'is_active' => true,
            'url' => '/finance',
        ]);

        Menu::create([
            'title' => 'Transactions',
            'icon' => 'list',
            'type' => 'main',
            'order' => 2,
            'parent_id' => $finance->id,
            'is_active' => true,
            'url' => '/finance/transactions',
        ]);

        Menu::create([
            'title' => 'Receive Payment',
            'icon' => 'arrow-down-circle',
            'type' => 'main',
            'order' => 3,
            'parent_id' => $finance->id,
            'is_active' => true,
            'url' => '/finance/receive-payment',
        ]);

        Menu::create([
            'title' => 'Make Payment',
            'icon' => 'arrow-up-circle',
            'type' => 'main',
            'order' => 4,
            'parent_id' => $finance->id,
            'is_active' => true,
            'url' => '/finance/make-payment',
        ]);

        Menu::create([
            'title' => 'Categories',
            'icon' => 'folder',
            'type' => 'main',
            'order' => 5,
            'parent_id' => $finance->id,
            'is_active' => true,
            'url' => '/finance/categories',
        ]);

        Menu::create([
            'title' => 'Payment Methods',
            'icon' => 'credit-card',
            'type' => 'main',
            'order' => 6,
            'parent_id' => $finance->id,
            'is_active' => true,
            'url' => '/finance/payment-methods',
        ]);

        $financeReports = Menu::create([
            'title' => 'Reports',
            'icon' => 'bar-chart',
            'type' => 'main',
            'order' => 7,
            'parent_id' => $finance->id,
            'is_active' => true,
        ]);

        Menu::create([
            'title' => 'Cash Book',
            'icon' => 'book',
            'type' => 'main',
            'order' => 1,
            'parent_id' => $financeReports->id,
            'is_active' => true,
            'url' => '/finance/reports/cash-book',
        ]);

        Menu::create([
            'title' => 'Income Statement',
            'icon' => 'trending-up',
            'type' => 'main',
            'order' => 2,
            'parent_id' => $financeReports->id,
            'is_active' => true,
            'url' => '/finance/reports/income',
        ]);

        Menu::create([
            'title' => 'Expense Statement',
            'icon' => 'trending-down',
            'type' => 'main',
            'order' => 3,
            'parent_id' => $financeReports->id,
            'is_active' => true,
            'url' => '/finance/reports/expense',
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
            'url' => '/settings/profile',
        ]);

        Menu::create([
            'title' => 'Appearance',
            'icon' => 'palette',
            'type' => 'footer',
            'order' => 2,
            'parent_id' => $settings->id,
            'is_active' => true,
            'url' => '/settings/appearance',
        ]);

        Menu::create([
            'title' => 'School Profile',
            'icon' => 'building',
            'type' => 'footer',
            'order' => 3,
            'parent_id' => $settings->id,
            'is_active' => true,
            'url' => '/settings/school-profile',
        ]);

        Menu::create([
            'title' => 'Menu Settings',
            'icon' => 'cog',
            'type' => 'footer',
            'order' => 4,
            'parent_id' => $settings->id,
            'is_active' => true,
            'url' => '/settings/menu-settings',
        ]);
    }
}
