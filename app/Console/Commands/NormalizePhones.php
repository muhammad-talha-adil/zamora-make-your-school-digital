<?php

namespace App\Console\Commands;

use App\Support\Phone;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Stores phone numbers the way the app saves them: Pakistani mobiles as
 * 0333-1112233 (see `App\Support\Phone`). Guardian numbers are the exception —
 * guardian matching compares digits only, so they are kept as 11 plain digits and
 * the screens add the dash when showing them.
 *
 * Safe to run any number of times; numbers it does not recognise are left alone.
 */
class NormalizePhones extends Command
{
    protected $signature = 'phones:normalize {--dry-run : Show what would change without saving}';

    protected $description = 'Store all phone numbers in one uniform format (idempotent)';

    /**
     * @var array<string, array<int, string>>
     */
    private const COLUMNS = [
        'staff_profiles' => ['phone', 'emergency_contact_phone'],
        'suppliers' => ['phone'],
        'admission_enquiries' => ['phone'],
        'schools' => ['phone', 'contact_phone'],
    ];

    public function handle(): int
    {
        $changed = 0;
        $columns = self::COLUMNS + ['guardians' => ['phone']];

        foreach ($columns as $table => $tableColumns) {
            foreach ($tableColumns as $column) {
                $rows = DB::table($table)->whereNotNull($column)->where($column, '!=', '')->get(['id', $column]);

                foreach ($rows as $row) {
                    $normalised = self::normalise((string) $row->{$column}, $table === 'guardians');

                    if ($normalised === null || $normalised === $row->{$column}) {
                        continue;
                    }

                    $changed++;
                    $this->line("{$table}.{$column} #{$row->id}: {$row->{$column}} -> {$normalised}");

                    if ($this->option('dry-run')) {
                        continue;
                    }

                    try {
                        DB::table($table)->where('id', $row->id)->update([$column => $normalised]);
                    } catch (QueryException) {
                        $this->warn("Skipped {$table} #{$row->id}: {$normalised} already belongs to another row.");
                    }
                }
            }
        }

        $this->info($changed === 0 ? 'Phone numbers are already uniform.' : "Normalised {$changed} phone number(s).");

        return self::SUCCESS;
    }

    /**
     * The uniform form of a Pakistani mobile number, or null when the value is not one.
     */
    public static function normalise(string $value, bool $digitsOnly = false): ?string
    {
        $normalised = Phone::normalize($value);

        if ($normalised === null || preg_match('/^03\d{2}-\d{7}$/', $normalised) !== 1) {
            return null;
        }

        return $digitsOnly ? str_replace('-', '', $normalised) : $normalised;
    }
}
