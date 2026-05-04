# Exam Module Refactoring - README

## Overview

This document describes the major refactoring changes made to the Exam module in the school management system. The refactoring removes the Offerings/Groups concept and replaces it with a scope-based paper scheduling system.

## What Changed

### 1. Removed Entities

- **ExamOffering** - Deleted model, controller, service, and table
- **ExamGroup** - Deleted model, controller, service, and table

### 2. Schema Changes

#### New Fields on Exams Table
- `publish_at` (datetime, nullable) - When exam is scheduled to be published
- `published_at` (datetime, nullable) - When exam was actually published
- `is_locked` (boolean, default false) - Whether exam is locked for marking
- `locked_by` (unsigned bigint, nullable) - User who locked the exam
- `locked_at` (datetime, nullable) - When exam was locked

#### ExamPapers Table (Redesigned)
- **Removed**: `exam_group_id` (foreign key to exam_groups)
- **Added**:
  - `exam_id` (unsigned bigint, indexed) - Direct link to exam
  - `campus_id` (unsigned bigint, nullable) - Campus scope
  - `class_id` (unsigned bigint, nullable) - Class scope  
  - `section_id` (unsigned bigint, nullable) - Section scope
  - `scope_type` (enum: SCHOOL, CLASS, SECTION) - Defines the targeting scope

#### ExamStudentRegistrations Table
- **Removed**: `exam_group_id`
- **Added**:
  - `exam_id` - Direct link to exam
  - `campus_id`, `class_id`, `section_id` - Cached from student enrollment

#### ExamResultHeaders Table
- **Removed**: `exam_group_id`
- **Added**:
  - `exam_id` - Direct link to exam
  - `campus_id`, `class_id`, `section_id` - Cached values
  - `is_locked` - Per-student result lock

#### GradeSystems Table
- **Added**: `is_active` (boolean) - Only one active grade scale at a time

### 3. New Features

#### Papers Module (Scope-Based Scheduling)
Papers can now be scheduled in three modes:
- **SCHOOL** - All students in the school take this paper
- **CLASS** - All students in a specific class take this paper
- **SECTION** - Only students in a specific section take this paper

#### Clash Detection
New logic prevents scheduling conflicts:
- SCHOOL paper conflicts with any class/section paper at same time
- CLASS paper conflicts with SCHOOL papers and CLASS papers for same class
- SECTION paper conflicts with SCHOOL, CLASS (same class), and SECTION (same section)

#### Settings Module (NEW)
- Grade Scales CRUD
- Set active grade scale
- Grade items with min/max percentages

#### Dashboard Module (NEW)
- Total exams count
- Exams by status
- Latest exam info
- Marking progress (marked vs pending)
- Grade distribution

### 4. Updated Routes

Removed:
- `/exams/offerings/*` - All offering routes
- `/exams/groups/*` - All group routes

Added:
- `/exams/settings` - Settings index
- `/exams/settings/grade-scales` - Grade scales management
- `/exams/dashboard` - Dashboard page
- `/exams/dashboard/stats` - Dashboard API

Modified:
- `/exams/papers` - Now uses exam_id instead of group_id
- `/exams/marking/grid` - New grid-based marking API

### 5. API Changes

#### Papers API
- `POST /exams/papers` - Creates paper with scope_type
- `POST /exams/papers/clash-check` - Now accepts exam_id, scope_type, class_id, section_id

#### Marking API
- `GET /exams/marking/grid` - New endpoint for grid-based marking
- `POST /exams/marking/save-row` - Save single student marks
- `POST /exams/marking/save-bulk` - Save multiple student marks

#### Exam API
- `PATCH /exams/{id}/publish` - Publish exam
- `PATCH /exams/{id}/lock` - Lock exam
- `PATCH /exams/{id}/unlock` - Unlock exam

## How to Use

### Creating a Paper
1. Go to Papers page
2. Select an Exam
3. Select Scope Type:
   - SCHOOL: No additional selection needed
   - CLASS: Select the class
   - SECTION: Select class then section
4. Fill in subject, date, time, marks
5. Save

### Marking Students
1. Go to Marking page
2. Select Exam, Campus (optional), Class, Section
3. The grid shows all students with their applicable papers
4. Enter marks for each paper
5. Save individual rows or use Bulk Save

### Managing Grades
1. Go to Settings > Grade Scales
2. Create a new scale or edit existing
3. Add grade items with min/max percentages
4. Set the scale as Active

## Migration Notes

Run migrations in order:
```bash
php artisan migrate
```

The following migrations were created:
1. `2026_02_15_100013_add_publish_and_lock_fields_to_exams_table`
2. `2026_02_15_100014_update_exam_papers_for_scope_based_design`
3. `2026_02_15_100015_update_exam_student_registrations_for_exam_link`
4. `2026_02_15_100016_update_exam_result_headers_for_exam_link`
5. `2026_02_15_100017_drop_exam_groups_and_offerings_tables`
6. `2026_02_15_100018_add_is_active_to_grade_systems_table`

## Data Migration

If you have existing data:
- Papers will need to be reassigned to exams manually
- Registrations will need exam_id and campus/class/section populated
- Results will need similar updates

## Backward Compatibility

This is a breaking change. The Offerings and Groups concepts have been completely removed. Any code depending on these will need to be updated.

## Testing

After running migrations:
1. Create a new exam
2. Add papers with different scopes
3. Test clash detection
4. Register students
5. Test marking grid
6. Verify results calculation
