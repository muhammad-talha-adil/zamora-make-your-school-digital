<?php

namespace App\Console\Commands;

use App\Models\Exam\ExamType;
use Illuminate\Console\Command;

class SeedExamTypes extends Command
{
    protected $signature = 'exams:seed-types';

    protected $description = 'Add the usual exam types (Mid Term, Final, Monthly Test, Quiz) if missing. Safe to run again.';

    public function handle(): int
    {
        $added = ExamType::ensureDefaults();

        $this->info($added > 0 ? "Added {$added} exam type(s)." : 'All usual exam types already exist.');

        return self::SUCCESS;
    }
}
