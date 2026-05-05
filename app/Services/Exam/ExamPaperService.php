<?php

namespace App\Services\Exam;

use App\Models\Exam\Exam;
use App\Models\Exam\ExamPaper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExamPaperService
{
    /**
     * Validate that the paper date falls within the exam's date range.
     *
     * @param  int  $examId  The exam ID
     * @param  string  $paperDate  The paper date to validate
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validatePaperDate(int $examId, string $paperDate): array
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return ['valid' => false, 'message' => 'Exam not found.'];
        }

        $examStartDate = $exam->start_date;
        $examEndDate = $exam->end_date;
        $paperDateObj = Carbon::parse($paperDate);

        // Check if paper date is before exam start date
        if ($paperDateObj->lt($examStartDate)) {
            return [
                'valid' => false,
                'message' => "Paper date ({$paperDateObj->format('Y-m-d')}) cannot be before exam start date ({$examStartDate->format('Y-m-d')}).",
            ];
        }

        // Check if paper date is after exam end date
        if ($paperDateObj->gt($examEndDate)) {
            return [
                'valid' => false,
                'message' => "Paper date ({$paperDateObj->format('Y-m-d')}) cannot be after exam end date ({$examEndDate->format('Y-m-d')}).",
            ];
        }

        return ['valid' => true, 'message' => 'Paper date is valid.'];
    }

    /**
     * Get the exam's date range for frontend validation.
     *
     * @param  int  $examId  The exam ID
     */
    public function getExamDateRange(int $examId): ?array
    {
        $exam = Exam::find($examId);

        if (! $exam) {
            return null;
        }

        return [
            'start_date' => $exam->start_date->format('Y-m-d'),
            'end_date' => $exam->end_date->format('Y-m-d'),
            'exam_name' => $exam->name,
        ];
    }

    /**
     * Check for date overlaps within the same exam for the same class/section.
     * This ensures no two papers for the same class/section have the same date.
     *
     * Handles null section_id properly (CLASS scope - applies to all sections).
     *
     * @param  int  $examId  The exam ID
     * @param  string  $paperDate  The paper date
     * @param  string  $scopeType  The scope type (SCHOOL, CLASS, SECTION)
     * @param  int|null  $classId  The class ID
     * @param  int|null  $sectionId  The section ID (can be null for CLASS scope)
     * @param  int|null  $campusId  The campus ID
     * @param  int|null  $excludePaperId  Paper ID to exclude (for updates)
     * @return array ['has_overlap' => bool, 'message' => string, 'overlapping_papers' => array]
     */
    public function checkDateOverlap(
        int $examId,
        string $paperDate,
        string $scopeType,
        ?int $classId = null,
        ?int $sectionId = null,
        ?int $campusId = null,
        ?int $excludePaperId = null
    ): array {
        $query = ExamPaper::where('exam_id', $examId)
            ->where('paper_date', $paperDate)
            ->where('status', '!=', 'cancelled');

        // Exclude the paper being updated
        if ($excludePaperId) {
            $query->where('id', '!=', $excludePaperId);
        }

        // For SCHOOL scope: any paper on same date overlaps
        if ($scopeType === 'SCHOOL') {
            $overlappingPapers = $query->get();

            if ($overlappingPapers->isNotEmpty()) {
                return [
                    'has_overlap' => true,
                    'message' => 'A paper already exists on this date for this exam.',
                    'overlapping_papers' => $overlappingPapers->toArray(),
                ];
            }
        }

        // For CLASS scope: check for papers in same class (includes CLASS and SECTION papers)
        // null section_id means CLASS scope - applies to all sections
        if ($scopeType === 'CLASS') {
            $overlappingPapers = $query->where(function ($q) use ($classId, $campusId) {
                $q->where('scope_type', 'SCHOOL')
                    ->orWhere(function ($subQ) use ($classId, $campusId) {
                        $subQ->where('scope_type', 'CLASS')
                            ->where('class_id', $classId);
                        if ($campusId) {
                            $subQ->where('campus_id', $campusId);
                        }
                    })
                    // Also check SECTION papers for this class - they would conflict with CLASS scope
                    ->orWhere(function ($subQ) use ($classId, $campusId) {
                        $subQ->where('scope_type', 'SECTION')
                            ->where('class_id', $classId);
                        if ($campusId) {
                            $subQ->where('campus_id', $campusId);
                        }
                    });
            })->get();

            if ($overlappingPapers->isNotEmpty()) {
                return [
                    'has_overlap' => true,
                    'message' => 'A paper already exists on this date for this class.',
                    'overlapping_papers' => $overlappingPapers->toArray(),
                ];
            }
        }

        // For SECTION scope: check for papers in same section (and higher level scopes)
        // sectionId is provided for specific section
        if ($scopeType === 'SECTION' && $sectionId !== null) {
            $overlappingPapers = $query->where(function ($q) use ($classId, $sectionId, $campusId) {
                $q->where('scope_type', 'SCHOOL')
                    ->orWhere(function ($subQ) use ($classId, $campusId) {
                        $subQ->where('scope_type', 'CLASS')
                            ->where('class_id', $classId);
                        if ($campusId) {
                            $subQ->where('campus_id', $campusId);
                        }
                    })
                    ->orWhere(function ($subQ) use ($classId, $sectionId, $campusId) {
                        $subQ->where('scope_type', 'SECTION')
                            ->where('class_id', $classId)
                            ->where('section_id', $sectionId);
                        if ($campusId) {
                            $subQ->where('campus_id', $campusId);
                        }
                    });
            })->get();

            if ($overlappingPapers->isNotEmpty()) {
                return [
                    'has_overlap' => true,
                    'message' => 'A paper already exists on this date for this section.',
                    'overlapping_papers' => $overlappingPapers->toArray(),
                ];
            }
        }

        return [
            'has_overlap' => false,
            'message' => 'No date overlap found.',
            'overlapping_papers' => [],
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return ExamPaper::create($data);
        });
    }

    public function update(ExamPaper $paper, array $data)
    {
        return DB::transaction(function () use ($paper, $data) {
            $paper->update($data);

            return $paper->fresh();
        });
    }

    public function bulkCreate(array $papers)
    {
        return DB::transaction(function () use ($papers) {
            return ExamPaper::insert($papers);
        });
    }

    /**
     * Check for schedule clashes based on the full combination of
     * Exam, Campus, Class, Section, and Subject.
     *
     * Handles null section_id properly (CLASS scope - applies to all sections).
     *
     * @param  int  $examId  The exam ID
     * @param  string  $scopeType  The scope type (SCHOOL, CLASS, SECTION)
     * @param  string  $date  The paper date
     * @param  string  $startTime  The start time
     * @param  string  $endTime  The end time
     * @param  int|null  $classId  The class ID (optional for SCHOOL scope)
     * @param  int|null  $sectionId  The section ID (optional for SCHOOL and CLASS scope, can be null)
     * @param  int|null  $campusId  The campus ID (optional for SCHOOL scope)
     * @param  int|null  $subjectId  The subject ID to check (optional)
     * @return bool True if there is a clash, false otherwise
     */
    public function checkClash(
        $examId,
        $scopeType,
        $date,
        $startTime,
        $endTime,
        $classId = null,
        $sectionId = null,
        $campusId = null,
        $subjectId = null
    ): bool {
        // Get papers for the same exam on the same date with overlapping times
        $query = ExamPaper::where('exam_id', $examId)
            ->where('paper_date', $date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($subQ) use ($startTime, $endTime) {
                        $subQ->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });

        // For SCHOOL scope: conflict with any paper at same time
        if ($scopeType === 'SCHOOL') {
            return $query->exists();
        }

        // For CLASS scope: conflict with SCHOOL papers OR CLASS/SECTION papers for same class
        // null sectionId means this is a CLASS-level paper (applies to all sections)
        if ($scopeType === 'CLASS') {
            return $query->where(function ($q) use ($classId, $campusId) {
                $q->where('scope_type', 'SCHOOL')
                    ->orWhere(function ($subQ) use ($classId, $campusId) {
                        $subQ->where('scope_type', 'CLASS')
                            ->where('class_id', $classId);
                        if ($campusId) {
                            $subQ->where('campus_id', $campusId);
                        }
                    })
                    // Also check SECTION papers for this class - they would conflict with CLASS scope
                    ->orWhere(function ($subQ) use ($classId, $campusId) {
                        $subQ->where('scope_type', 'SECTION')
                            ->where('class_id', $classId);
                        if ($campusId) {
                            $subQ->where('campus_id', $campusId);
                        }
                    });
            })->exists();
        }

        // For SECTION scope: conflict with SCHOOL, CLASS for same class, or SECTION for same section
        // sectionId is provided for specific section
        if ($scopeType === 'SECTION' && $sectionId !== null) {
            return $query->where(function ($q) use ($classId, $sectionId, $campusId) {
                $q->where('scope_type', 'SCHOOL')
                    ->orWhere(function ($subQ) use ($classId, $campusId) {
                        $subQ->where('scope_type', 'CLASS')
                            ->where('class_id', $classId);
                        if ($campusId) {
                            $subQ->where('campus_id', $campusId);
                        }
                    })
                    ->orWhere(function ($subQ) use ($classId, $sectionId, $campusId) {
                        $subQ->where('scope_type', 'SECTION')
                            ->where('class_id', $classId)
                            ->where('section_id', $sectionId);
                        if ($campusId) {
                            $subQ->where('campus_id', $campusId);
                        }
                    });
            })->exists();
        }

        return false;
    }
}
