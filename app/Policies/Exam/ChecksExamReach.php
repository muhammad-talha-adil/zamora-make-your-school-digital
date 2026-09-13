<?php

namespace App\Policies\Exam;

use App\Policies\Concerns\ChecksSchoolReach;

/**
 * How far into the school somebody's exam access reaches.
 *
 * The widths themselves now live in `ChecksSchoolReach`, shared with the
 * student policy — because two copies of "the three widths" is precisely the
 * drift this was written to stop, and the second copy appeared the moment the
 * next module needed it.
 *
 * This name is kept so the three exam policies read as they did.
 */
trait ChecksExamReach
{
    use ChecksSchoolReach;
}
