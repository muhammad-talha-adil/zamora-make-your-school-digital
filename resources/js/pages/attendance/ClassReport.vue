<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Class Attendance Report" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Class Attendance Report
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        View attendance summary for a class
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Class -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                        <select v-model="selectedClassId" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                            <option value="">Select Class</option>
                            <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>

                    <!-- Section -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section</label>
                        <select v-model="selectedSectionId" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                            <option value="">All Sections</option>
                            <option v-for="section in filteredSections" :key="section.id" :value="section.id">{{ section.name }}</option>
                        </select>
                    </div>

                    <!-- Month -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Month</label>
                        <select v-model="selectedMonth" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                            <option v-for="(name, index) in monthNames" :key="index + 1" :value="index + 1">{{ name }}</option>
                        </select>
                    </div>

                    <!-- Year -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year</label>
                        <select v-model="selectedYear" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                            <option v-for="year in yearRange" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>
                </div>

                <!-- Generate Report Button -->
                <div class="mt-4">
                    <Button @click="generateReport" :disabled="!canGenerate">
                        <Icon icon="file-text" class="mr-1" />
                        Generate Report
                    </Button>
                </div>
            </div>

            <!-- Report Results -->
            <div v-if="showReport" class="space-y-4">
                <!-- Summary Stats -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.length }}</div>
                        <div class="text-sm text-gray-500">Total Students</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-2xl font-bold text-green-600">{{ totalPresent }}</div>
                        <div class="text-sm text-gray-500">Present Days</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-2xl font-bold text-red-600">{{ totalAbsent }}</div>
                        <div class="text-sm text-gray-500">Absent Days</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-2xl font-bold text-yellow-600">{{ totalLeave }}</div>
                        <div class="text-sm text-gray-500">Leave Days</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-2xl font-bold text-blue-600">{{ attendancePercentage }}%</div>
                        <div class="text-sm text-gray-500">Attendance %</div>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Reg No</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Campus / Class / Section</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Guardian</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Present</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Absent</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Leave</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Late</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                <tr v-for="student in summary" :key="student.student.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                                <span class="text-blue-600 dark:text-blue-400 font-medium">{{ student.student.name.charAt(0) }}</span>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ student.student.name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ student.student.registration_no }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ student.enrollment_info || '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ student.guardian_info || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="text-green-600 font-medium">{{ student.present }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="text-red-600 font-medium">{{ student.absent }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="text-yellow-600 font-medium">{{ student.leave }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="text-blue-600 font-medium">{{ student.late }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span :class="getPercentageClass(student)">{{ getPercentage(student) }}%</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- No Data Message -->
            <div v-else-if="hasSearched" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                <Icon icon="file-text" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <p class="text-gray-600 dark:text-gray-400">No report data found for the selected criteria.</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import type { AttendanceClassReportProps } from '@/types/attendance';

const props = defineProps<AttendanceClassReportProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Attendance', href: '/attendance' },
    { title: 'Class Report', href: '#' },
];

const monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const selectedClassId = ref(props.selectedClassId || '');
const selectedSectionId = ref(props.selectedSectionId || '');
const selectedMonth = ref(props.month || new Date().getMonth() + 1);
const selectedYear = ref(props.year || new Date().getFullYear());
const hasSearched = ref(false);
const showReport = computed(() => props.summary && props.summary.length > 0);

const yearRange = computed(() => {
    const years = [];
    for (let year = props.year - 5; year <= props.year + 1; year++) {
        years.push(year);
    }
    return years;
});

const filteredSections = computed(() => {
    if (!selectedClassId.value) return [];
    return props.sections.filter((s) => s.class_id === Number(selectedClassId.value));
});

const canGenerate = computed(() => selectedClassId.value && selectedMonth.value && selectedYear.value);

const totalPresent = computed(() => props.summary.reduce((sum, s) => sum + s.present, 0));
const totalAbsent = computed(() => props.summary.reduce((sum, s) => sum + s.absent, 0));
const totalLeave = computed(() => props.summary.reduce((sum, s) => sum + s.leave, 0));

const attendancePercentage = computed(() => {
    if (!props.summary.length) return 0;
    const totalDays = props.summary.reduce((sum, s) => sum + s.total, 0);
    if (totalDays === 0) return 0;
    return Math.round((totalPresent.value / totalDays) * 100);
});

const generateReport = () => {
    if (!canGenerate.value) return;
    hasSearched.value = true;
    router.visit(route('attendance.class-report'), {
        data: {
            class_id: selectedClassId.value,
            section_id: selectedSectionId.value,
            month: selectedMonth.value,
            year: selectedYear.value,
        },
    });
};

const getPercentage = (student: any): number => {
    if (student.total === 0) return 0;
    return Math.round((student.present / student.total) * 100);
};

const getPercentageClass = (student: any): string => {
    const percentage = getPercentage(student);
    if (percentage >= 90) return 'text-green-600 font-medium';
    if (percentage >= 75) return 'text-blue-600 font-medium';
    if (percentage >= 60) return 'text-yellow-600 font-medium';
    return 'text-red-600 font-medium';
};
</script>
