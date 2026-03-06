<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        School::create([
            'name' => 'My School Management System',
            'slogan' => 'Excellence in Education',
            'address' => '123 School Street, Education City',
            'phone' => '+1-234-567-8900',
            'logo_path' => '/sample-logo.png',
            'is_active' => true,
        ]);
    }
}