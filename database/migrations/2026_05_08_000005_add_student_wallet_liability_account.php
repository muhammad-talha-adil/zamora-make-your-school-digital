<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('chart_of_accounts')
            ->where('code', '2200')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('chart_of_accounts')->insert([
            'code' => '2200',
            'name' => 'Student Advance Wallet',
            'account_type' => 'liability',
            'parent_id' => null,
            'is_active' => true,
            'is_system' => true,
            'description' => 'Advance receipts and student wallet balances not yet applied to receivables',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('chart_of_accounts')->where('code', '2200')->delete();
    }
};
