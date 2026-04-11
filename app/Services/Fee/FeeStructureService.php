<?php

namespace App\Services\Fee;

use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeStructureItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FeeStructureService
{
    public function list(array $filters = []): Collection
    {
        $query = FeeStructure::with(['session', 'campus', 'schoolClass', 'section', 'creator']);

        if (isset($filters['session_id'])) {
            $query->where('session_id', $filters['session_id']);
        }

        if (isset($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        if (isset($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (isset($filters['section_id'])) {
            $query->where('section_