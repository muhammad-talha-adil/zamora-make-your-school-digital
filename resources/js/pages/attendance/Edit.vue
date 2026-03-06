<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Edit Attendance" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Edit Attendance
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        {{ formatDate(props.attendance.attendance_date) }} - {{ props.attendance.class?.name }} {{ props.attendance.section?.name }}
                    </p>
                </div>
                <Button variant="outline" @click="router.visit(route('attendance.show', { attendance_date: props.attendance.attendance_date, class_id: props.attendance.class_id, section_id: props.attendance.section_id }))">
                    <Icon icon="eye" class="mr-1" />
                    View Details
                </Button>
            </div>

            <!-- Students Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Admission No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Check In</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Check Out</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            <AttendanceFormRow
                                v-for="(attendanceStudent, index) in attendanceStudents"
                                :key="attendanceStudent.id"
                                :student="attendanceStudent.student!"
                                :statuses="props.attendanceStatuses"
                                v-model="formData.attendances[index]"
                                :disabled="props.attendance.is_locked"
                            />
                        </tbody>
                    </table>
                </div>

                <!-- Submit Button -->
                <div v-if="!props.attendance.is_locked" class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <Button @click="submitAttendance" :disabled="isSubmitting" class="w-full sm:w-auto">
                        <Icon v-if="isSubmitting" icon="loader" class="mr-1 animate-spin" />
                        {{ isSubmitting ? 'Updating...' : 'Update Attendance' }}
                    </Button>
                </div>
            </div>

            <!-- Locked Warning -->
            <div v-if="props.attendance.is_locked" class="bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg p-4">
                <div class="flex items-center gap-2">
                    <Icon icon="lock" class="h-5 w-5 text-red-600 dark:text-red-400" />
                    <span class="text-sm font-medium text-red-800 dark:text-red-200">
                        This attendance record is locked and cannot be modified.
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import AttendanceFormRow from '@/components/attendance/AttendanceFormRow.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import type { AttendanceEditProps, StudentAttendanceFormData } from '@/types/attendance';

const props = defineProps<AttendanceEditProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Attendance', href: '/attendance' },
    { title: 'Edit Attendance', href: '#' },
];

const isSubmitting = computed(() => (usePage().props as any).processing || false);

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
};

const attendanceStudents = computed(() => props.attendanceStudents || []);

const formData = reactive({
    attendance_id: props.attendance.id,
    attendances: (props.attendanceStudents || []).map((as) => ({
        id: as.id,
        student_id: as.student_id,
        attendance_status_id: as.attendance_status_id,
        check_in: as.check_in || '',
        check_out: as.check_out || '',
        remarks: as.remarks || '',
    })),
});

const submitAttendance = () => {
    router.put(route('attendance.update', { attendance: props.attendance.id }), formData, {
        onSuccess: () => router.visit(route('attendance.index')),
    });
};
</script>
