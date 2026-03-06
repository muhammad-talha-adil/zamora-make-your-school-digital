<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ClassSubjectController extends Controller
{
    /**
     * Get data for subject-class assignment page.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('settings.manage');

        $campusId = $request->get('campus_id');
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');

        // Get all campuses
        $campuses = Campus::orderBy('name', 'asc')->get(['id', 'name']);

        // Get classes (optionally filtered by campus)
        $classes = SchoolClass::orderBy('id', 'asc')->get(['id', 'name']);

        // Get sections (optionally filtered by class)
        $sections = Section::when($classId, function ($query) use ($classId) {
            return $query->where('class_id', $classId);
        })->orderBy('name', 'asc')->get(['id', 'name', 'class_id']);

        // Get all active subjects
        $subjects = Subject::where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'short_name']);

        // Get currently assigned subjects for the selected class-section
        $assignedSubjects = [];
        
        if ($classId) {
            $query = DB::table('class_subject')
                ->where('class_id', $classId);
            
            if ($sectionId) {
                $query->where('section_id', $sectionId);
            } else {
                // If no section selected, show class-level assignments (section_id is NULL)
                $query->whereNull('section_id');
            }

            $assignedSubjects = $query->pluck('subject_id')->toArray();
        }

        return response()->json([
            'campuses' => $campuses,
            'classes' => $classes,
            'sections' => $sections,
            'subjects' => $subjects,
            'assigned_subjects' => $assignedSubjects,
        ]);
    }

    /**
     * Get sections by class.
     */
    public function getSections(Request $request): JsonResponse
    {
        $classId = $request->get('class_id');

        $sections = Section::where('class_id', $classId)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'class_id']);

        return response()->json(['sections' => $sections]);
    }

    /**
     * Get assigned subjects for a specific class-section.
     */
    public function getAssignedSubjects(Request $request): JsonResponse
    {
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        $isAllSections = $request->get('is_all_sections', false);

        if (!$classId) {
            return response()->json(['assigned_subjects' => []]);
        }

        // If "All Sections" mode is selected, get all subjects assigned to any section of this class
        if ($isAllSections) {
            $assignedSubjects = DB::table('class_subject')
                ->where('class_id', $classId)
                ->pluck('subject_id')
                ->toArray();
        } else {
            // Regular mode: get subjects for specific section or class-level
            $query = DB::table('class_subject')
                ->where('class_id', $classId);

            if ($sectionId && $sectionId !== '') {
                $query->where('section_id', $sectionId);
            } else {
                // Get class-level assignments (section_id is NULL)
                $query->whereNull('section_id');
            }

            $assignedSubjects = $query->pluck('subject_id')->toArray();
        }

        return response()->json(['assigned_subjects' => $assignedSubjects]);
    }

    /**
     * Save subject-class-section assignments.
     */
    public function store(Request $request)
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
            'is_all_sections' => 'nullable|boolean',
        ]);

        $classId = $validated['class_id'];
        $sectionId = $validated['section_id'] ?? null;
        $subjectIds = $validated['subject_ids'];
        $isAllSections = $request->boolean('is_all_sections', false);

        try {
            DB::beginTransaction();

            if ($isAllSections) {
                // Get all sections for this class
                $sections = Section::where('class_id', $classId)->get();

                // Remove existing assignments for this class (all sections and class-level)
                DB::table('class_subject')
                    ->where('class_id', $classId)
                    ->delete();

                // Add new assignments for each section
                $now = now();
                $insertData = [];

                foreach ($sections as $section) {
                    foreach ($subjectIds as $subjectId) {
                        $insertData[] = [
                            'class_id' => $classId,
                            'section_id' => $section->id,
                            'subject_id' => $subjectId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (!empty($insertData)) {
                    DB::table('class_subject')->insert($insertData);
                }
            } else {
                // Regular mode: save for specific section or class-level
                // Remove existing assignments for this class-section
                DB::table('class_subject')
                    ->where('class_id', $classId)
                    ->where(function ($query) use ($sectionId) {
                        if ($sectionId) {
                            $query->where('section_id', $sectionId);
                        } else {
                            $query->whereNull('section_id');
                        }
                    })
                    ->delete();

                // Add new assignments
                $now = now();
                $insertData = [];
                
                foreach ($subjectIds as $subjectId) {
                    $insertData[] = [
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'subject_id' => $subjectId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($insertData)) {
                    DB::table('class_subject')->insert($insertData);
                }
            }

            DB::commit();

            // Return Inertia response with success message
            return Inertia::render('settings/SchoolProfile', [
                'flash' => [
                    'success' => $isAllSections ? 'Subjects assigned to all sections successfully' : 'Subjects assigned successfully',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Return Inertia response with error
            return Inertia::render('settings/SchoolProfile', [
                'flash' => [
                    'error' => 'Failed to assign subjects: ' . $e->getMessage(),
                ],
            ]);
        }
    }
}
