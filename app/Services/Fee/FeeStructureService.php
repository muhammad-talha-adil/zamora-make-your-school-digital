<?php

namespace App\Services\Fee;

use App\Models\Fee\FeeStructure;
use Illuminate\Support\Collection;

class FeeStructureService
{
    public function list(array $filters = []): Collection
    {
        $query = FeeStructure::with(['session', 'campus', 'schoolClass', 'section', 'creator'])
            ->withCount('items');

        if (! empty($filters['session_id'])) {
            $query->where('session_id', $filters['session_id']);
        }

        if (! empty($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        if (! empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (! empty($filters['section_id'])) {
            $query->where('section_id', $filters['section_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        return $query->latest()->get();
    }
}
