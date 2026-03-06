// ─── Base Entity Types ───────────────────────────────────────────────────────

export interface ExamType {
    id: number;
    name: string;
}

export interface Session {
    id: number;
    name: string;
}

export interface Campus {
    id: number;
    name: string;
}

export interface SchoolClass {
    id: number;
    name: string;
}

export interface Section {
    id: number;
    name: string;
    class_id: number;
}

export interface Subject {
    id: number;
    name: string;
}

export interface GradeSystem {
    id: number;
    name: string;
}

export interface User {
    id: number;
    name: string;
}

export interface Student {
    id: number;
    registration_no?: string;
    user?: User;
}

// ─── Exam ────────────────────────────────────────────────────────────────────

export interface Exam {
    id: number;
    name: string;
    exam_type_id: number;
    session_id: number;
    start_date: string;
    end_date: string;
    start_date_formatted?: string;
    end_date_formatted?: string;
    status: string;
    exam_type?: ExamType;
    session?: Session;
    exam_offerings?: ExamOffering[];
    paper_count?: number;
    result_count?: number;
}

// ─── Exam Offering ───────────────────────────────────────────────────────────

export interface ExamOffering {
    id: number;
    exam_id: number;
    campus_id: number;
    grade_system_id: number;
    result_visibility: string;
    is_published?: boolean;
    is_locked?: boolean;
    exam?: Exam;
    campus?: Campus;
    grade_system?: GradeSystem;
    exam_groups?: ExamGroup[];
}

// ─── Exam Group ──────────────────────────────────────────────────────────────

export interface ExamGroup {
    id: number;
    exam_offering_id: number;
    class_id: number;
    section_id: number;
    status?: string;
    exam_offering?: ExamOffering;
    class?: SchoolClass;
    section?: Section;
    exam_papers?: ExamPaper[];
}

// ─── Exam Paper ──────────────────────────────────────────────────────────────

export interface ExamPaper {
    id: number;
    exam_group_id: number;
    subject_id: number;
    paper_date: string;
    start_time: string;
    end_time: string;
    total_marks: number;
    passing_marks: number;
    is_cancelled?: boolean;
    exam_group?: ExamGroup;
    subject?: Subject;
    exam_result_lines?: ExamResultLine[];
}

// ─── Exam Result ─────────────────────────────────────────────────────────────

export interface ExamResultHeader {
    id: number;
    status: string;
    is_locked: boolean;
    total_obtained_marks?: number;
    total_max_marks?: number;
    overall_percentage?: number;
}

export interface ExamResultLine {
    id: number;
    student_id: number;
    obtained_marks?: number | null;
    total_marks_snapshot?: number;
    is_absent: boolean;
    is_exempt: boolean;
    remarks?: string;
    student?: Student;
    resultHeader?: ExamResultHeader;
}

// ─── Exam Registration ───────────────────────────────────────────────────────

export interface ExamStudentRegistration {
    id: number;
    exam_group_id: number;
    student_id: number;
    roll_no_snapshot?: string;
    status: string;
    student?: Student;
    exam_group?: ExamGroup;
}

// ─── Page Props ──────────────────────────────────────────────────────────────

// Exams
export interface ExamIndexProps {
    exams: Exam[];
    sessions: Session[];
    examTypes: ExamType[];
    filters?: {
        session_id?: string;
        exam_type_id?: string;
        status?: string;
        search?: string;
    };
}

export interface ExamCreateProps {
    sessions: Session[];
    examTypes: ExamType[];
}

export interface ExamEditProps {
    exam: Exam;
    sessions: Session[];
    examTypes: ExamType[];
}

export interface ExamShowProps {
    exam: Exam;
}

// Offerings
export interface OfferingIndexProps {
    offerings: ExamOffering[];
    exams: Exam[];
    filters?: {
        exam_id?: string;
    };
}

export interface OfferingCreateProps {
    exams: Exam[];
    campuses: Campus[];
    gradeSystems: GradeSystem[];
    selectedExamId?: string;
}

export interface OfferingEditProps {
    offering: ExamOffering;
    exams: Exam[];
    campuses: Campus[];
    gradeSystems: GradeSystem[];
}

// Groups
export interface GroupIndexProps {
    groups: ExamGroup[];
    offerings: ExamOffering[];
    classes: SchoolClass[];
    sections: Section[];
    filters?: {
        offering_id?: string;
    };
}

export interface GroupCreateProps {
    offerings: ExamOffering[];
    classes: SchoolClass[];
    sections: Section[];
    selectedOfferingId?: string;
}

export interface GroupEditProps {
    group: ExamGroup;
    offerings: ExamOffering[];
    classes: SchoolClass[];
    sections: Section[];
}

// Papers
export interface PaperIndexProps {
    papers: ExamPaper[];
    groups: ExamGroup[];
    subjects: Subject[];
    filters?: {
        group_id?: string;
    };
}

export interface PaperCreateProps {
    groups: ExamGroup[];
    subjects: Subject[];
    selectedGroupId?: string;
}

export interface PaperEditProps {
    paper: ExamPaper;
    groups: ExamGroup[];
    subjects: Subject[];
}

// Registrations
export interface RegistrationIndexProps {
    registrations: ExamStudentRegistration[];
    groups: ExamGroup[];
    group?: ExamGroup;
    filters?: {
        group_id?: string;
    };
}

// Marking
export interface MarkingSelectProps {
    exams: Exam[];
    offerings: ExamOffering[];
    groups: ExamGroup[];
    papers: ExamPaper[];
}

export interface MarkSheetProps {
    paper: ExamPaper;
}
