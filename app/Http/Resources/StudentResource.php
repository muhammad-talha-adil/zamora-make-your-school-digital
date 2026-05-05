<?php

namespace App\Http\Resources;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Student
 */
class StudentResource extends JsonResource
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
            'student_code' => $this->student_code,
            'registration_no' => $this->registration_no,
            'admission_no' => $this->admission_no,
            'name' => $this->name,
            'email' => $this->user?->email,
            'dob' => $this->dob?->toIso8601String(),
            'dob_formatted' => $this->dob?->format('Y-m-d'),
            'age' => $this->dob ? now()->diffInYears($this->dob) : null,
            'gender' => $this->whenLoaded('gender', fn () => [
                'id' => $this->gender->id,
                'name' => $this->gender->name,
            ]),
            'gender_id' => $this->gender_id,
            'b_form' => $this->b_form,
            'status' => $this->whenLoaded('studentStatus', fn () => [
                'id' => $this->studentStatus->id,
                'name' => $this->studentStatus->name,
            ]),
            'student_status_id' => $this->student_status_id,
            'admission_date' => $this->admission_date?->toIso8601String(),
            'admission_date_formatted' => $this->admission_date?->format('Y-m-d'),
            'description' => $this->description,
            'image' => $this->image,
            'image_url' => $this->image ? asset('storage/'.$this->image) : null,

            // Current enrollment information
            'current_enrollment' => $this->whenLoaded('currentEnrollment', fn () => new StudentEnrollmentResource($this->currentEnrollment)),
            'current_enrollment_data' => $this->whenLoaded('enrollmentRecords', function () {
                $enrollment = $this->enrollmentRecords
                    ->sortByDesc('admission_date')
                    ->first(fn ($r) => $r->leave_date === null);

                if ($enrollment) {
                    return [
                        'campus' => [
                            'id' => $enrollment->campus?->id,
                            'name' => $enrollment->campus?->name,
                        ],
                        'class' => [
                            'id' => $enrollment->class?->id,
                            'name' => $enrollment->class?->name,
                        ],
                        'section' => [
                            'id' => $enrollment->section?->id,
                            'name' => $enrollment->section?->name,
                        ],
                        'session' => [
                            'id' => $enrollment->session?->id,
                            'name' => $enrollment->session?->name,
                        ],
                        'monthly_fee' => $enrollment->monthly_fee,
                        'annual_fee' => $enrollment->annual_fee,
                    ];
                }

                return null;
            }),

            // Guardian information
            'guardians' => $this->whenLoaded('studentGuardians', fn () => StudentGuardianResource::collection($this->studentGuardians)),
            'primary_guardian' => $this->whenLoaded('studentGuardians', function () {
                $primaryGuardian = $this->studentGuardians->where('is_primary', true)->first();

                return $primaryGuardian ? new StudentGuardianResource($primaryGuardian) : null;
            }),

            // Enrollment history
            'enrollment_history' => $this->whenLoaded('enrollmentRecords', fn () => StudentEnrollmentResource::collection(
                $this->enrollmentRecords->sortByDesc('admission_date')
            )),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Metadata for pagination
            'serial' => $this->when(isset($this->serial), $this->serial),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Student retrieved successfully.',
        ];
    }
}
