<?php

namespace App\Console\Commands;

use App\Models\Section;
use Illuminate\Console\Command;

class CheckSections extends Command
{
    protected $signature = 'check:sections';

    protected $description = 'Check sections and their class relationships';

    public function handle()
    {
        $section = Section::with('schoolClass')->first();
        if ($section) {
            $this->info('Section: '.$section->name);
            $this->info('class_id: '.$section->class_id);
            $this->info('schoolClass: '.($section->schoolClass ? $section->schoolClass->name : 'NULL'));
        } else {
            $this->warn('No sections found');
        }

        $total = Section::count();
        $withClass = Section::has('schoolClass')->count();
        $this->line("Total: $total, With class: $withClass");
    }
}
