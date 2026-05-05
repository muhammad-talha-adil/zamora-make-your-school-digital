<?php

namespace Database\Seeders;

use App\Models\Relation;
use Illuminate\Database\Seeder;

class RelationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $relations = [
            ['name' => 'Mother'],
            ['name' => 'Father'],
            ['name' => 'Guardian'],
            ['name' => 'Uncle'],
            ['name' => 'Aunt'],
        ];

        foreach ($relations as $relation) {
            Relation::firstOrCreate(['name' => $relation['name']], $relation);
        }
    }
}
