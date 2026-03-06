<?php

namespace App\Services\Exam;

use App\Models\Exam\ExamPaper;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use App\Models\Exam\GradeSystem;
use Illuminate\Support\Facades\DB;

class ExamMarkingService
{
    /**
     * Get the active grade system for calculations.
     */
    protected function getActiveGradeSystem()
    {
        return GradeSystem::where('is_active', true)->first()
            ?? GradeSystem::where('is_default', true)->first();
    }

    /**
     * Calculate grade based on percentage.
     */
    protected function calculateGrade(?float $percentage): ?int
    {
        if ($percentage === null) {
            return null;
        }

        $gradeSystem = $this->getActiveGradeSystem();
        if (!$gradeSystem) {
            return null;
        }

        $gradeItem = $gradeSystem->gradeSystemItems()
            ->where('min_percentage', '<=', $percentage)
            ->where('max_percentage', '>=', $percentage)
            ->first();

        return $gradeItem?->id;
    }

    /**
     * Save marks for a paper.
     */
    public function saveMarks(ExamPaper $paper, array $students)
    {
        return DB::transaction(function () use ($paper, $students) {
            foreach ($students as $studentData) {
                $studentId = $studentData['student_id'];
                $obtainedMarks = $studentData['obtained_marks'] ?? null;
                $isAbsent = $studentData['is_absent'] ?? false;
                $isExempt = $studentData['is_exempt'] ?? false;
                $remarks = $studentData['remarks'] ?? null;

                // Get or create result header using exam_id (not exam_group_id)
                $header = ExamResultHeader::firstOrCreate(
                    [
                        'exam_id' => $paper->exam_id,
                        'student_id' => $studentId,
                    ],
                    [
                        'exam_id' => $paper->exam_id,
                        'student_id' => $studentId,
                        'campus_id' => $paper->campus_id,
                        'class_id' => $paper->class_id,
                        'section_id' => $paper->section_id,
                        'status' => 'draft',
                    ]
                );

                // Get or create result line
                $line = ExamResultLine::firstOrCreate(
                    [
                        'result_header_id' => $header->id,
                        'exam_paper_id' => $paper->id,
                    ],
                    [
                        'result_header_id' => $header->id,
                        'exam_paper_id' => $paper->id,
                    ]
                );

                // Calculate percentage
                $percentage = null;
                if (!$isAbsent && !$isExempt && $obtainedMarks !== null && $paper->total_marks > 0) {
                    $percentage = round(($obtainedMarks / $paper->total_marks) * 100, 2);
                }

                // Calculate grade
                $gradeItemId = $this->calculateGrade($percentage);

                $line->update([
                    'total_marks_snapshot' => $paper->total_marks,
                    'passing_marks_snapshot' => $paper->passing_marks,
                    'obtained_marks' => $isAbsent ? null : $obtainedMarks,
                    'is_absent' => $isAbsent,
                    'is_exempt' => $isExempt,
                    'remarks' => $remarks,
                    'percentage_cache' => $percentage,
                    'grade_item_id_cache' => $gradeItemId,
                ]);
            }

            // Update header totals after all marks are saved
            $this->updateHeaderTotals($paper);

            return true;
        });
    }

    /**
     * Update the result header with calculated totals.
     */
    protected function updateHeaderTotals(ExamPaper $paper): void
    {
        // Get all result headers for this exam that have this paper
        $headerIds = ExamResultLine::where('exam_paper_id', $paper->id)
            ->distinct()
            ->pluck('result_header_id');

        foreach ($headerIds as $headerId) {
            $header = ExamResultHeader::find($headerId);
            if (!$header) continue;

            // Get all result lines for this header
            $lines = $header->examResultLines;

            // Calculate totals (excluding absent and exempt students)
            $totalObtained = $lines
                ->where('is_absent', false)
                ->where('is_exempt', false)
                ->sum('obtained_marks');

            $totalMax = $lines
                ->where('is_absent', false)
                ->where('is_exempt', false)
                ->sum('total_marks_snapshot');

            $overallPercentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : null;

            // Calculate overall grade
            $overallGradeItemId = $this->calculateGrade($overallPercentage);

            // Determine status
            $status = 'draft';
            $hasUnmarked = $lines->contains(function ($line) {
                return $line->obtained_marks === null && !$line->is_absent && !$line->is_exempt;
            });

            if (!$hasUnmarked) {
                $status = 'submitted';
            }

            $header->update([
                'total_obtained_cache' => $totalObtained,
                'overall_percentage_cache' => $overallPercentage,
                'overall_grade_item_id_cache' => $overallGradeItemId,
                'status' => $status,
            ]);
        }
    }
}
