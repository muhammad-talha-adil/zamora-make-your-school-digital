<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Student Attendance Report" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Student Attendance Report
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        {{ props.student.name }} - {{ monthNames[props.month] }} {{ props.year }}
                    </p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-green-600">{{ props.stats.present }}</div>
                    <div class="text-sm text-gray-500">Present</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-red-600">{{ props.stats.absent }}</div>
                    <div class="text-sm text-gray-500">Absent</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-yellow-600">{{ props.stats.leave }}</div>
                    <div class="text-sm text-gray-500">On Leave</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-blue-600">{{ props.stats.late }}</div>
                    <div class="text-sm text-gray-500">Late</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-2xl font-bold text-gray-600">{{ props.stats.total }}</div>
                    <div class="text-sm text-gray-500">Total Days</div>
                </div>
            </div>

            <!-- Month Selector -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Month</label>
                        <select v-model="selectedMonth" @change="loadReport" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                            <option v-for="(name, index) in monthNames" :key="index + 1" :value="index + 1">{{ name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year</label>
                        <select v-model="selectedYear" @change="loadReport" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                            <option v-for="year in yearRange" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Attendance Calendar -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Day</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            <tr v-for="day in calendarDays" :key="day.date" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ formatDate(day.date) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ day.dayName }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusClass(day.status?.attendance_status?.code)]">
                                        {{ day.status?.attendance_status?.name || 'Not Marked' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ day.status?.remarks || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AttendanceStudentReportProps } from '@/types/attendance';

const props = defineProps<AttendanceStudentReportProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Attendance', href: '/attendance' },
    { title: 'Student Report', href: '#' },
];

const monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const selectedMonth = ref(props.month);
const selectedYear = ref(props.year);

const yearRange = computed(() => {
    const years = [];
    for (let year = props.year - 5; year <= props.year + 1; year++) {
        years.push(year);
    }
    return years;
});

const calendarDays = computed(() => {
    const days = [];
    const daysInMonth = new Date(selectedYear.value, selectedMonth.value, 0).getDate();
    const attendanceMap = new Map(props.attendanceRecords.map((ar) => [ar.attendance?.attendance_date, ar]));
    for (let day = 1; day <= daysInMonth; day++) {
        const date = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dateObj = new Date(date);
        days.push({ date, dayName: dateObj.toLocaleDateString('en-US', { weekday: 'short' }), status: attendanceMap.get(date) });
    }
    return days;
});

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const getStatusClass = (code?: string): string => {
    switch (code) {
        case 'P': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'A': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'L': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'LT': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const loadReport = () => {
    router.visit(route('attendance.student-report', { student: props.student.id }), {
        data: { month: selectedMonth.value, year: selectedYear.value },
        preserveState: true,
    });
};
</script>
