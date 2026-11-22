<?php

use App\Models\Fee\FeeHead;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Previous Balance and Late Fee heads are needed by voucher generation
     * and payments; add them if the seeder never ran on this database.
     */
    public function up(): void
    {
        FeeHead::query()->firstOrCreate(['code' => 'PREVIOUS_BALANCE'], [
            'name' => 'Previous Balance',
            'category' => 'misc',
            'is_recurring' => false,
            'default_frequency' => 'once',
            'is_optional' => false,
            'sort_order' => 60,
            'is_active' => true,
            'description' => 'Unpaid balance brought forward from earlier vouchers',
        ]);

        FeeHead::query()->firstOrCreate(['code' => 'LATE_FINE'], [
            'name' => 'Late Payment Fine',
            'category' => 'fine',
            'is_recurring' => false,
            'default_frequency' => 'once',
            'is_optional' => true,
            'sort_order' => 40,
            'is_active' => true,
            'description' => 'Fine for late fee payment',
        ]);
    }

    public function down(): void {}
};
