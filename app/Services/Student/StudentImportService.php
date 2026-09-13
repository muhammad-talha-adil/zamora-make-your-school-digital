<?php

namespace App\Services\Student;

use App\Models\Campus;
use App\Models\Gender;
use App\Models\Relation;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentStatus;
use App\Repositories\StudentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Getting a school's roll into the system.
 *
 * `import()` used to return "Import started. You will be notified when
 * complete." and do nothing at all with the file — which is worse than the
 * export stub, because import is how a school onboards. They spend a week
 * preparing a spreadsheet, upload it, are told it worked, and find an empty
 * system.
 *
 * Two rules this is built around, both learned here:
 *
 *  - **Say what is wrong, row by row.** A school with four hundred rows and one
 *    bad date needs the row number, not "import failed".
 *  - **Nothing is half-written.** Either the file goes in or it does not, so a
 *    school can correct the sheet and upload the same file again without
 *    hunting for which children already made it.
 */
class StudentImportService
{
    /** How many rows one upload may carry. */
    public const MAX_ROWS = 2000;

    /*
     * The repository rather than `StudentService`, which is what this reached
     * for first — and `StudentService` depends on the importer, so the container
     * chased the two of them round until Xdebug stopped it. `create()` on the
     * service is one line delegating here anyway.
     */
    public function __construct(private StudentRepository $students) {}

    /**
     * Reads a file without writing anything, and says what would happen.
     *
     * A school uploads its sheet, sees the problems, fixes them, and uploads
     * again. Nobody should discover a bad row by importing it.
     *
     * @return array<string, mixed>
     */
    public function dryRun(UploadedFile $file): array
    {
        return $this->process($file, commit: false);
    }

    /**
     * Reads the file and admits everybody in it.
     *
     * @return array<string, mixed>
     */
    public function import(UploadedFile $file): array
    {
        return $this->process($file, commit: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function process(UploadedFile $file, bool $commit): array
    {
        $rows = $this->read($file);

        if (isset($rows['error'])) {
            return ['ok' => false, 'imported' => 0, 'problems' => [$rows['error']], 'rows' => []];
        }

        $lookups = $this->lookups();
        $problems = [];
        $ready = [];

        foreach ($rows as $number => $row) {
            $result = $this->check($row, $number, $lookups);

            if ($result['problems'] !== []) {
                $problems = array_merge($problems, $result['problems']);

                continue;
            }

            if ($result['skip']) {
                continue;
            }

            $ready[] = $result['payload'];
        }

        if ($problems !== []) {
            // Nothing is written when anything is wrong. A half-imported file
            // is the thing a school cannot recover from by hand.
            return [
                'ok' => false,
                'imported' => 0,
                'would_import' => count($ready),
                'problems' => $problems,
            ];
        }

        if (! $commit) {
            return ['ok' => true, 'imported' => 0, 'would_import' => count($ready), 'problems' => []];
        }

        DB::transaction(function () use ($ready) {
            foreach ($ready as $payload) {
                $this->students->createWithRelationships($payload);
            }
        });

        return ['ok' => true, 'imported' => count($ready), 'problems' => []];
    }

    /**
     * The rows of the file, keyed by the line number a person would see.
     *
     * @return array<int, array<string, string>>|array{error: string}
     */
    private function read(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return ['error' => 'That file could not be opened.'];
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);

            return ['error' => 'That file is empty.'];
        }

        // Excel writes a byte-order mark in front of the first heading and then
        // nothing matches it.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(fn ($name) => strtolower(trim((string) $name)), $header);

        foreach (['admission_no', 'name', 'dob'] as $required) {
            if (! in_array($required, $header, true)) {
                fclose($handle);

                return ['error' => "The file has no \"{$required}\" column."];
            }
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            // A trailing blank line is not a row a school needs telling about.
            if ($values === [null] || $values === ['']) {
                continue;
            }

            $values = array_pad(array_slice($values, 0, count($header)), count($header), null);
            $rows[$line] = array_combine($header, array_map(
                fn ($v) => $v === null ? '' : trim((string) $v),
                $values
            ));

            if (count($rows) > self::MAX_ROWS) {
                fclose($handle);

                return ['error' => 'That file has more than '.self::MAX_ROWS
                    .' rows. Split it and upload the parts.'];
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Checks one row and turns it into an admission payload.
     *
     * @param  array<string, string>  $row
     * @param  array<string, Collection<string, mixed>>  $lookups
     * @return array{problems: array<int, string>, skip: bool, payload: array<string, mixed>}
     */
    private function check(array $row, int $line, array $lookups): array
    {
        $problems = [];

        $validator = Validator::make($row, [
            'admission_no' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date', 'before:today'],
            'b_form' => ['nullable', 'string', 'max:15'],
            'father_phone' => ['nullable', 'string', 'max:20'],
        ]);

        foreach ($validator->errors()->all() as $message) {
            $problems[] = "Row {$line}: {$message}";
        }

        // A child already on the roll under this number is skipped, not
        // refused: uploading a corrected file must not mean deleting first.
        if ($problems === [] && Student::withTrashed()->where('admission_no', $row['admission_no'])->exists()) {
            return ['problems' => [], 'skip' => true, 'payload' => []];
        }

        $resolved = [];

        foreach (
            [
                'class' => ['classes', 'class_id', true],
                'section' => ['sections', 'section_id', false],
                'campus' => ['campuses', 'campus_id', false],
                'session' => ['sessions', 'session_id', true],
                'gender' => ['genders', 'gender_id', true],
                'status' => ['statuses', 'student_status_id', false],
            ] as $column => [$bag, $key, $required]
        ) {
            $name = $row[$column] ?? '';

            if ($name === '') {
                if ($required) {
                    $problems[] = "Row {$line}: {$column} is required.";
                }

                continue;
            }

            $id = $lookups[$bag]->get(mb_strtolower($name));

            if (! $id) {
                // Named, not guessed. A school that has typed "Clas 5" needs to
                // be told which cell is wrong.
                $problems[] = "Row {$line}: there is no {$column} called \"{$name}\".";

                continue;
            }

            $resolved[$key] = $id;
        }

        if ($problems !== []) {
            return ['problems' => $problems, 'skip' => false, 'payload' => []];
        }

        $resolved['student_status_id'] ??= $lookups['statuses']->get('active');

        /*
         * A school with one campus should not have to write its name in every
         * row; a school with three has to say which, because guessing would
         * quietly put four hundred children on the wrong campus.
         */
        if (! isset($resolved['campus_id'])) {
            if ($lookups['campuses']->count() === 1) {
                $resolved['campus_id'] = $lookups['campuses']->first();
            } else {
                $problems[] = "Row {$line}: this school has more than one campus, so the campus must be named.";

                return ['problems' => $problems, 'skip' => false, 'payload' => []];
            }
        }

        // A class may genuinely have no sections.
        $resolved['section_id'] ??= null;

        return [
            'problems' => [],
            'skip' => false,
            // Every column but the three required ones is optional, so each is
            // read with a default. A file carrying only what a school actually
            // has is the normal file.
            'payload' => array_merge($resolved, [
                'admission_no' => $row['admission_no'],
                'name' => $row['name'],
                'dob' => $row['dob'],
                'b_form' => ($row['b_form'] ?? '') ?: null,
                'admission_date' => ($row['admission_date'] ?? '') ?: now()->toDateString(),
                'monthly_fee' => ($row['monthly_fee'] ?? '') ?: 0,
                'annual_fee' => ($row['annual_fee'] ?? '') ?: 0,
                'father_name' => ($row['father_name'] ?? '') ?: 'Guardian',
                // `student_guardians.relation_id` is NOT NULL, and an import
                // that names a father has said what the relation is.
                'father_relation_id' => $lookups['relations']->get('father')
                    ?? $lookups['relations']->first(),
                'father_phone' => ($row['father_phone'] ?? '') ?: null,
                'father_cnic' => ($row['father_cnic'] ?? '') ?: null,
            ]),
        ];
    }

    /**
     * Every name the file might use, lower-cased, mapped to its id.
     *
     * Read once. Looking each one up per row turned a four-hundred-row file
     * into two and a half thousand queries.
     *
     * @return array<string, Collection<string, mixed>>
     */
    private function lookups(): array
    {
        $byName = fn ($rows) => $rows->mapWithKeys(
            fn ($row) => [mb_strtolower((string) $row->name) => $row->id]
        );

        return [
            'classes' => $byName(SchoolClass::all(['id', 'name'])),
            'sections' => $byName(Section::all(['id', 'name'])),
            'campuses' => $byName(Campus::all(['id', 'name'])),
            'sessions' => $byName(Session::all(['id', 'name'])),
            'genders' => $byName(Gender::all(['id', 'name'])),
            'statuses' => $byName(StudentStatus::all(['id', 'name'])),
            'relations' => $byName(Relation::all(['id', 'name'])),
        ];
    }
}
