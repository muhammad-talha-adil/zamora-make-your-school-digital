<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('discount_types')->where('code', 'OTHER')->exists();

        if (! $exists) {
            DB::table('discount_types')->insert([
                'name' => 'Other Discount',
                'code' => 'OTHER',
                'value_type' => 'percent',
                'default_value' => null,
                'description' => 'General discount for any other reason',
                'requires_approval' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('discount_types')->where('code', 'OTHER')->delete();
    }
};
