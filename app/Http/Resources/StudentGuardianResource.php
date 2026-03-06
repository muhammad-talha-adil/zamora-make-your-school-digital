<?php

namespace App\Http\Resources;

use App\Models\StudentGuardian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentGuardian
 */
class StudentGuardianResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'guardian_id' => $this->guardian_id,
            'guardian' => $this->whenLoaded('guardian', fn() => [
                'id' => $this->guardian->id,
                'name' => $this->guardian->user?->name,
                'email' => $this->guardian->user?->email,
                'phone' => $this->guardian->phone,
                'cnic' => $this->guardian->cnic,
                'occupation' => $this->guardian->occupation,
                'address' => $this->guardian->address,
            ]),
            'relation' => $this->whenLoaded('relation', fn() => [
                'id' => $this->relation->id,
                'name' => $this->relation->name,
            ]),
            'relation_id' => $this->relation_id,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
