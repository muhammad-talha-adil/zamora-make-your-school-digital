<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

// Dev/demo data only — not part of the default production seed list.
class SchoolClassSeeder extends Seeder
{
    /**
     * Seeds the same class set — PG, KG-I, KG-II, One..Ten — for every
     * active campus, since a class now belongs to one campus. `code` is left
     * for the model to auto-generate per campus rather than hardcoded, so
     * two campuses don't collide on the same literal string.
     */
    public function run(): void
    {
        $names = [
            'PG' => 'Pre-Primary / Play Group',
            'KG-I' => 'Kindergarten I',
            'KG-II' => 'Kindergarten II',
            'One' => 'Class One',
            'Two' => 'Class Two',
            'Three' => 'Class Three',
            'Four' => 'Class Four',
            'Five' => 'Class Five',
            'Six' => 'Class Six',
            'Seven' => 'Class Seven',
            'Eight' => 'Class Eight',
            'Nine' => 'Class Nine',
            'Ten' => 'Class Ten',
        ];

        $campuses = Campus::where('is_active', true)->get();

        foreach ($campuses as $campus) {
            foreach ($names as $name => $description) {
                SchoolClass::updateOrCreate(
                    ['campus_id' => $campus->id, 'name' => $name],
                    ['description' => $description, 'is_active' => true]
                );
            }

            // One inactive and one soft-deleted class per campus, to keep
            // exercising the inactive/soft-delete-name-reuse paths.
            SchoolClass::updateOrCreate(
                ['campus_id' => $campus->id, 'name' => 'Eleven'],
                ['description' => 'Class Eleven', 'is_active' => false]
            );

            $retired = SchoolClass::firstOrCreate(
                ['campus_id' => $campus->id, 'name' => 'Twelve'],
                ['description' => 'Discontinued class', 'is_active' => false]
            );
            $retired->delete();
        }

        $this->command->info('School classes seeded for '.$campuses->count().' campus(es)!');
    }
}
