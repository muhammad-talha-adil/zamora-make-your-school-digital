<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Staff "Dashboard" and "Staff Directory" opened the same page. The
     * dashboard now lives at /staff/dashboard and the directory at /staff.
     */
    public function up(): void
    {
        DB::table('menus')->where('title', 'Dashboard')->where('url', '/staff')->update(['url' => '/staff/dashboard']);
        DB::table('menus')->where('url', '/staff/people')->update(['url' => '/staff']);
    }

    public function down(): void {}
};
