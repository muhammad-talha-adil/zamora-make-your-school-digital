import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import type { 
    Attendance, 
    AttendanceStudent, 
    AttendanceStatus, 
    Student,
    StudentAttendanceFormData 
} from '@/types/attendance';

// Composable for managing attendance state and actions
export function useAttendance() {
    const isLoading = ref(false);
    const isSubmitting = ref(false);

    // Calculate statistics from attendance students
    const calculateStats = (students: AttendanceStudent[]) => {
        return {
            present: students.filter((s) => s.attendance_status?.code === 'P').length,
            absent: students.filter((s) => s.attendance_status?.code === 'A').length,
            leave: students.filter((s) => s.attendance_status?.code === 'L').length,
            late: students.filter((s) => s.attendance_status?.code === 'LT').length,
            total: students.length,
        };
    };

    // Get status color class
    const getStatusClass = (code?: string): string => {
        switch (code) {
            case 'P': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
            case 'A': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
            case 'L': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
            case 'LT': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
            default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        }
    };

    // Format date helper
    const formatDate = (dateString: string, format: 'short' | 'long' = 'short'): string => {
        const date = new Date(dateString);
        if (format === 'long') {
            return date.toLocaleDateString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    };

    // Mark all students with a specific status
    const markAllStatus = (
        attendances: StudentAttendanceFormData[], 
        statuses: AttendanceStatus[], 
        statusCode: string
    ): StudentAttendanceFormData[] => {
        const status = statuses.find((s) => s.code === statusCode);
        if (!status) return attendances;
        
        return attendances.map((a) => ({
            ...a,
            attendance_status_id: status.id,
        }));
    };

    // Lock attendance record
    const lockAttendance = async (attendance: Attendance) => {
        try {
            isLoading.value = true;
            await router.post(route('attendance.lock', { attendance: attendance.id }), {}, {
                onSuccess: () => router.reload(),
                onError: () => {
                    // Error handled by flash messages
                },
            });
        } finally {
            isLoading.value = false;
        }
    };

    // Unlock attendance record
    const unlockAttendance = async (attendance: Attendance) => {
        try {
            isLoading.value = true;
            await router.post(route('attendance.unlock', { attendance: attendance.id }), {}, {
                onSuccess: () => router.reload(),
                onError: () => {
                    // Error handled by flash messages
                },
            });
        } finally {
            isLoading.value = false;
        }
    };

    // Check if date is a holiday (would call API)
    const checkHoliday = async (date: string, campusId?: number): Promise<boolean> => {
        try {
            const response = await fetch(route('attendance.api.check-holiday', {
                date,
                campus_id: campusId,
            }));
            const data = await response.json();
            return data.is_holiday || false;
        } catch {
            return false;
        }
    };

    // Initialize form data from students
    const initializeFormData = (students: Student[], existingData?: AttendanceStudent[]): StudentAttendanceFormData[] => {
        if (existingData && existingData.length > 0) {
            return existingData.map((as) => ({
                id: as.id,
                student_id: as.student_id,
                attendance_status_id: as.attendance_status_id,
                check_in: as.check_in || '',
                check_out: as.check_out || '',
                remarks: as.remarks || '',
            }));
        }
        
        return students.map((student) => ({
            student_id: student.id,
            attendance_status_id: 0,
            check_in: '',
            check_out: '',
            remarks: '',
        }));
    };

    return {
        isLoading,
        isSubmitting,
        calculateStats,
        getStatusClass,
        formatDate,
        markAllStatus,
        lockAttendance,
        unlockAttendance,
        checkHoliday,
        initializeFormData,
    };
}

// Export attendance status codes for reference
export const ATTENDANCE_STATUS_CODES = {
    PRESENT: 'P',
    ABSENT: 'A',
    LEAVE: 'L',
    LATE: 'LT',
} as const;

export type AttendanceStatusCode = typeof ATTENDANCE_STATUS_CODES[keyof typeof ATTENDANCE_STATUS_CODES];
