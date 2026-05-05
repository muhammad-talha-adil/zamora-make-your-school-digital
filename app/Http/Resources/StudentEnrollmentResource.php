<?php

namespace App\Http\Resources;

use App\Models\StudentEnrollmentRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentEnrollmentRecord
 */
class StudentEnrollmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'session' => $this->whenLoaded('session', fn () => [
                'id' => $this->session->id,
                'name' => $this->session->name,
            ]),
            'session_id' => $this->session_id,
            'class' => $this->whenLoaded('class', fn () => [
                'id' => $this->class->id,
                'name' => $this->class->name,
            ]),
            'class_id' => $this->class_id,
            'section' => $this->whenLoaded('section', fn () => [
                'id' => $this->section->id,
                'name' => $this->section->name,
            ]),
            'section_id' => $this->section_id,
            'campus' => $this->whenLoaded('campus', fn () => [
                'id' => $this->campus->id,
                'name' => $this->campus->name,
            ]),
            'campus_id' => $this->campus_id,
            'admission_date' => $this->admission_date?->toIso8601String(),
            'admission_date_formatted' => $this->admission_date?->format('Y-m-d'),
            'leave_date' => $this->leave_date?->toIso8601String(),
            'leave_date_formatted' => $this->leave_date?->format('Y-m-d'),
            'is_active' => $this->isActive(),
            'status' => $this->whenLoaded('studentStatus', fn () => [
                'id' => $this->studentStatus->id,
                'name' => $this->studentStatus->name,
            ]),
            'student_status_id' => $this->student_status_id,
            'previous_enrollment' => $this->whenLoaded('previousEnrollment', fn () => [
                'id' => $this->previousEnrollment->id,
                'admission_date' => $this->previousEnrollment->admission_date?->toIso8601String(),
            ]),
            'previous_enrollment_id' => $this->previous_enrollment_id,
            'monthly_fee' => $this->monthly_fee,
            'annual_fee' => $this->annual_fee,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
