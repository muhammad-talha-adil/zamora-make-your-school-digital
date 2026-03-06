// Attendance Types for School Management System

export interface Campus {
    id: number;
    name: string;
}

export interface Session {
    id: number;
    name: string;
    is_active: boolean;
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

export interface AttendanceStatus {
    id: number;
    name: string;
    code: string;
    description?: string;
}

export interface User {
    id: number;
    name: string;
    email?: string;
    avatar?: string;
}

export interface Student {
    id: number;
    registration_no: string;
    admission_no: string;
    name: string;
    user?: User;
    current_enrollment?: StudentEnrollmentRecord;
    student_leaves?: StudentLeave[];
    existing_attendance?: {
        id: number;
        attendance_status_id: number;
        attendance_status_code?: string;
        leave_type_id?: number;
        check_in?: string;
        check_out?: string;
        remarks?: string;
        student_leave_id?: number;
    };
    has_existing_attendance: boolean;
}

export interface StudentEnrollmentRecord {
    id: number;
    student_id: number;
    campus_id: number;
    session_id: number;
    class_id: number;
    section_id: number;
    enrollment_date: string;
    leave_date?: string;
    campus?: Campus;
    session?: Session;
    class?: SchoolClass;
    section?: Section;
}

export interface StudentLeave {
    id: number;
    student_id: number;
    leave_type_id: number;
    start_date: string;
    end_date: string;
    reason: string;
    status: 'pending' | 'approved' | 'rejected';
    leave_type?: LeaveType;
}

export interface LeaveType {
    id: number;
    name: string;
}

export interface Holiday {
    id: number;
    title: string;
    start_date: string;
    end_date: string;
    is_national: boolean;
    campus_id?: number;
}

export interface Attendance {
    id: number;
    attendance_date: string;
    campus_id: number;
    session_id: number;
    class_id: number;
    section_id: number;
    taken_by: number;
    is_locked: boolean;
    created_at: string;
    updated_at: string;
    campus?: Campus;
    session?: Session;
    class?: SchoolClass;
    section?: Section;
    takenBy?: User;
    attendance_students?: AttendanceStudent[];
}

export interface AttendanceStudent {
    id: number;
    attendance_id: number;
    student_id: number;
    attendance_status_id: number;
    student_leave_id?: number;
    check_in?: string;
    check_out?: string;
    remarks?: string;
    student?: Student;
    attendance_status?: AttendanceStatus;
    student_leave?: StudentLeave;
    attendance?: Attendance;
}

export interface AttendanceSummary {
    present: number;
    absent: number;
    leave: number;
    late: number;
    total: number;
}

// Filter Types
export interface AttendanceFilters {
    campus_id?: string;
    session_id?: string;
    class_id?: string;
    section_id?: string;
    date?: string;
    locked?: string;
}

// Form Types
export interface AttendanceFormData {
    attendance_date: string;
    campus_id: number;
    session_id: number;
    class_id: number;
    section_id: number;
    attendances: StudentAttendanceFormData[];
}

export interface StudentAttendanceFormData {
    id?: number;
    student_id: number;
    attendance_status_id: number;
    leave_type_id?: number;
    check_in?: string;
    check_out?: string;
    remarks?: string;
}

// Props Types for Pages
export interface AttendanceIndexProps {
    attendances: PaginatedData<Attendance>;
    campuses: Campus[];
    sessions: Session[];
    classes: SchoolClass[];
    sections: Section[];
    attendanceStatuses: AttendanceStatus[];
    filters: AttendanceFilters;
}

export interface AttendanceCreateProps {
    campuses: Campus[];
    sessions: Session[];
    classes: SchoolClass[];
    sections: Section[];
    attendanceStatuses: AttendanceStatus[];
    leaveTypes: LeaveType[];
    students: Student[];
    selectedCampusId: number | null;
    selectedSessionId: number | null;
    selectedClassId: number | null;
    selectedSectionId: number | null | string;
    selectedDate: string;
    isSunday: boolean;
    holiday: {
        title: string;
        start_date: string;
        end_date: string;
        is_national: boolean;
        campus: string | null;
    } | null;
}

export interface AttendanceEditProps {
    attendance: Attendance;
    attendanceStudents: AttendanceStudent[];
    attendanceStatuses: AttendanceStatus[];
}

export interface AttendanceShowProps {
    attendance: Attendance;
    attendanceStudents: AttendanceStudent[];
    attendanceStatuses: AttendanceStatus[];
}

export interface AttendanceStudentReportProps {
    student: Student;
    attendanceRecords: AttendanceStudent[];
    stats: AttendanceSummary;
    month: number;
    year: number;
}

export interface AttendanceClassReportProps {
    class: SchoolClass | null;
    sections: Section[];
    summary: StudentClassAttendanceSummary[];
    month: number;
    year: number;
    classes: SchoolClass[];
    selectedClassId: number | null;
    selectedSectionId: number | null;
}

export interface StudentClassAttendanceSummary {
    student: Student;
    registration_no: string;
    name: string;
    present: number;
    absent: number;
    leave: number;
    late: number;
    total: number;
    guardian_info: string;
    enrollment_info: string;
}

export interface PaginatedData<T> {
    data: T[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    from: number;
    to: number;
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

// API Response Types
export interface GetStudentsApiResponse {
    students: Student[];
    date: string;
}

export interface CheckHolidayApiResponse {
    is_holiday: boolean;
    holiday: Holiday | null;
}
