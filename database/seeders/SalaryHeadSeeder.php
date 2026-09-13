<?php

namespace Database\Seeders;

use App\Models\Staff\SalaryHead;
use Illuminate\Database\Seeder;

class SalaryHeadSeeder extends Seeder
{
    public function run(): void
    {
        $heads = [
            ['name' => 'House Rent Allowance', 'code' => 'HRA', 'type' => SalaryHead::TYPE_ALLOWANCE, 'is_part_of_gross' => true, 'sort_order' => 1],
            ['name' => 'Conveyance Allowance', 'code' => 'CONV', 'type' => SalaryHead::TYPE_ALLOWANCE, 'is_part_of_gross' => true, 'sort_order' => 2],
            ['name' => 'Medical Allowance', 'code' => 'MED', 'type' => SalaryHead::TYPE_ALLOWANCE, 'is_part_of_gross' => true, 'sort_order' => 3],
            ['name' => 'Utilities Allowance', 'code' => 'UTIL', 'type' => SalaryHead::TYPE_ALLOWANCE, 'is_part_of_gross' => true, 'sort_order' => 4],
            ['name' => 'Provident Fund', 'code' => 'PF', 'type' => SalaryHead::TYPE_DEDUCTION, 'is_part_of_gross' => false, 'sort_order' => 5],
            ['name' => 'Income Tax', 'code' => 'TAX', 'type' => SalaryHead::TYPE_DEDUCTION, 'is_part_of_gross' => false, 'sort_order' => 6],
            ['name' => 'EOBI', 'code' => 'EOBI', 'type' => SalaryHead::TYPE_DEDUCTION, 'is_part_of_gross' => false, 'sort_order' => 7],
        ];

        foreach ($heads as $head) {
            SalaryHead::updateOrCreate(
                ['code' => $head['code']],
                $head + ['is_active' => true]
            );
        }
    }
}
