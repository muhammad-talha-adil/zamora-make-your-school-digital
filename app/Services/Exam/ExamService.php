<?php

namespace App\Services\Exam;

use App\Models\Exam\Exam;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function list(array $filters = [])
    {
        $query = Exam::with(['examType', 'session']);

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (isset($filters['exam_type_id'])) {
            $query->where('exam_type_id', $filters['exam_type_id']);
        }

        if (isset($filters['session_id'])) {
            $query->where('session_id', $filters['session_id']);
        }

        $exams = $query->get();

        // Add paper_count and result_count to each exam
        $exams->each(function ($exam) {
            $exam->paper_count = $exam->examPapers()->count();
            $exam->result_count = $exam->resultHeaders()->whereHas('examResultLines', function ($query) {
                $query->whereNotNull('obtained_marks');
            })->count();
        });

        return $exams;
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Auto-calculate status based on start_date
            $data['status'] = $this->calculateStatus(
                $data['start_date'] ?? null,
                $data['end_date'] ?? null
            );

            $exam = Exam::create($data);

            return $exam->load(['examType', 'session']);
        });
    }

    public function update(Exam $exam, array $data)
    {
        return DB::transaction(function () use ($exam, $data) {
            // Auto-calculate status based on start_date
            $data['status'] = $this->calculateStatus(
                $data['start_date'] ?? $exam->start_date,
                $data['end_date'] ?? $exam->end_date
            );

            $exam->update($data);

            return $exam->fresh(['examType', 'session']);
        });
    }

    /**
     * Calculate exam status based on start and end dates
     */
    private function calculateStatus($startDate, $endDate): string
    {
        $today = now()->toDateString();

        // If no dates provided, default to scheduled
        if (! $startDate) {
            return Exam::STATUS_SCHEDULED;
        }

        // Convert to Carbon if needed
        $startDate = $startDate instanceof Carbon ? $startDate->toDateString() : $startDate;
        $endDate = $endDate instanceof Carbon ? $endDate->toDateString() : $endDate;

        // If exam has ended, it's completed
        if ($endDate && $endDate < $today) {
            return Exam::STATUS_COMPLETED;
        }

        // If exam has started (today is between start and end date)
        if ($startDate <= $today && $endDate && $endDate >= $today) {
            return Exam::STATUS_ACTIVE;
        }

        // If start date is today
        if ($startDate === $today) {
            return Exam::STATUS_ACTIVE;
        }

        // If start date is in the past (but end date is in future)
        if ($startDate < $today && $endDate && $endDate > $today) {
            return Exam::STATUS_ACTIVE;
        }

        // Otherwise it's scheduled
        return Exam::STATUS_SCHEDULED;
    }

    public function changeStatus(Exam $exam, string $status)
    {
        return DB::transaction(function () use ($exam, $status) {
            $exam->update(['status' => $status]);
            $exam = $exam->fresh(['examType', 'session']);

            // Add paper_count and result_count to the response
            $exam->paper_count = $exam->examPapers()->count();
            $exam->result_count = $exam->resultHeaders()->whereHas('examResultLines', function ($query) {
                $query->whereNotNull('obtained_marks');
            })->count();

            return $exam;
        });
    }

    public function delete(Exam $exam)
    {
        return DB::transaction(function () use ($exam) {
            return $exam->delete();
        });
    }
}
