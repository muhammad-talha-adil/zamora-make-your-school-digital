<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, let's check if there are any duplicate records
        // and handle them before adding the unique constraint
        
        // Get all records with their counts grouped by the unique fields
        $duplicates = DB::table('fee_structures')
            ->select('session_id', 'campus_id', 'class_id', 'section_id', 'title', DB::raw('COUNT(*) as count'))
            ->whereNotNull('title')
            ->groupBy('session_id', 'campus_id', 'class_id', 'section_id', 'title')
            ->having('count', '>', 1)
            ->get();

        // If there are duplicates, handle them
        if ($duplicates->count() > 0) {
            foreach ($duplicates as $dup) {
                // Get IDs of records to keep (oldest)
                $idsToKeep = DB::table('fee_structures')
                    ->where('session_id', $dup->session_id)
                    ->where('campus_id', $dup->campus_id)
                    ->where(function ($q) use ($dup) {
                        $q->where('class_id', $dup->class_id)
                          ->orWhereNull('class_id');
                    })
                    ->where(function ($q) use ($dup) {
                        $q->where('section_id', $dup->section_id)
                          ->orWhereNull('section_id');
                    })
                    ->where('title', $dup->title)
                    ->orderBy('created_at')
                    ->limit(1)
                    ->pluck('id');

                // Delete duplicates (keep oldest)
                if ($idsToKeep->isNotEmpty()) {
                    DB::table('fee_structures')
                        ->where('session_id', $dup->session_id)
                        ->where('campus_id', $dup->campus_id)
                        ->where(function ($q) use ($dup) {
                            $q->where('class_id', $dup->class_id)
                              ->orWhereNull('class_id');
                        })
                        ->where(function ($q) use ($dup) {
                            $q->where('section_id', $dup->section_id)
                              ->orWhereNull('section_id');
                        })
                        ->where('title', $dup->title)
                        ->whereNotIn('id', $idsToKeep)
                        ->delete();
                }
            }
        }

        // Now add the unique constraint
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->unique(['session_id', 'campus_id', 'class_id', 'section_id', 'title'], 'fee_structures_unique_scope');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropUnique('fee_structures_unique_scope');
        });
    }
};
