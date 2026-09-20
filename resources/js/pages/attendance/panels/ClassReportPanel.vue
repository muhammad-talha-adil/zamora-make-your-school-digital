<template>
    <div class="space-y-4 md:space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">
                    Class Attendance Report
                </h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    View attendance summary for a class
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card rounded-lg border border-border p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Class -->
                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-1">Class</label>
                    <select v-model="selectedClassId" class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm">
                        <option value="">Select Class</option>
                        <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                    </select>
                </div>

                <!-- Section -->
                <div>
                    <label class="block text-sm font-medium text-muted-foreground mb-1">Section</label>
                    <select v-model="selectedSectionId" class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm">
                        <option value="">All Sections</option>
                        <option v-for="section in filteredSections" :key="section.id" :value="section.id">{{ section.name }}</option>
                    </select>
                </div>

                <!-- Month -->
                <div v-if="reportMode === 'month'">
                    <label class="block text-sm font-medium text-muted-foreground mb-1">Month</label>
                    <select v-model="selectedMonth" class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm">
                        <option v-for="(name, index) in monthNames" :key="index + 1" :value="index + 1">{{ name }}</option>
                    </select>
                </div>

                <!-- Year -->
                <div v-if="reportMode === 'month'">
                    <label class="block text-sm font-medium text-muted-foreground mb-1">Year</label>
                    <select v-model="selectedYear" class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm">
                        <option v-for="year in yearRange" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>

                <!-- Date range -->
                <template v-if="reportMode === 'range'">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">From</label>
                        <input v-model="dateFrom" type="date" class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">To</label>
                        <input v-model="dateTo" type="date" class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm" />
                    </div>
                </template>
            </div>

            <!-- Mode toggle + Generate Report Button -->
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <div class="flex rounded-md border border-border overflow-hidden text-sm">
                    <button
                        type="button"
                        @click="reportMode = 'month'"
                        :class="['px-3 py-1.5', reportMode === 'month' ? 'bg-primary text-primary-foreground' : 'bg-card text-muted-foreground']"
                    >
                        Month
                    </button>
                    <button
                        type="button"
                        @click="reportMode = 'range'"
                        :class="['px-3 py-1.5', reportMode === 'range' ? 'bg-primary text-primary-foreground' : 'bg-card text-muted-foreground']"
                    >
                        Date Range
                    </button>
                </div>
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
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="text-2xl font-bold text-foreground">{{ summary.length }}</div>
                    <div class="text-sm text-muted-foreground">Total Students</div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="text-2xl font-bold text-success">{{ totalPresent }}</div>
                    <div class="text-sm text-muted-foreground">Present Days</div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="text-2xl font-bold text-destructive">{{ totalAbsent }}</div>
                    <div class="text-sm text-muted-foreground">Absent Days</div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="text-2xl font-bold text-warning">{{ totalLeave }}</div>
                    <div class="text-sm text-muted-foreground">Leave Days</div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="text-2xl font-bold text-primary">{{ attendancePercentage }}%</div>
                    <div class="text-sm text-muted-foreground">Attendance %</div>
                </div>
            </div>

            <!-- Students Table -->
            <div class="bg-card rounded-lg border border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Reg No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Campus / Class / Section</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Guardian</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-muted-foreground uppercase">Present</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-muted-foreground uppercase">Absent</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-muted-foreground uppercase">Leave</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-muted-foreground uppercase">Late</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-muted-foreground uppercase">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(student, studentIndex) in summary" :key="student.student.id" class="transition-colors hover:bg-accent">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ studentIndex + 1 }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                            <span class="text-primary font-medium">{{ student.student.name.charAt(0) }}</span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-foreground">{{ student.student.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ student.student.registration_no }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ student.enrollment_info || '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ student.guardian_info || '-' }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="text-success font-medium">{{ student.present }}</span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="text-destructive font-medium">{{ student.absent }}</span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="text-warning font-medium">{{ student.leave }}</span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="text-primary font-medium">{{ student.late }}</span>
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
        <div v-else-if="hasSearched" class="bg-card rounded-lg border border-border p-8 text-center">
            <Icon icon="file-text" class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <p class="text-muted-foreground">No report data found for the selected criteria.</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { AttendanceClassReportProps } from '@/types/attendance';

const props = defineProps<AttendanceClassReportProps>();

const monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const selectedClassId = ref(props.selectedClassId || '');
const selectedSectionId = ref(props.selectedSectionId || '');
const selectedMonth = ref(props.month || new Date().getMonth() + 1);
const selectedYear = ref(props.year || new Date().getFullYear());
const reportMode = ref<'month' | 'range'>(props.dateFrom && props.dateTo ? 'range' : 'month');
const dateFrom = ref(props.dateFrom || '');
const dateTo = ref(props.dateTo || '');
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

const canGenerate = computed(() => {
    if (!selectedClassId.value) return false;
    return reportMode.value === 'range'
        ? !!dateFrom.value && !!dateTo.value
        : !!selectedMonth.value && !!selectedYear.value;
});

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

    const data: Record<string, unknown> = {
        class_id: selectedClassId.value,
        section_id: selectedSectionId.value,
    };

    if (reportMode.value === 'range') {
        data.date_from = dateFrom.value;
        data.date_to = dateTo.value;
    } else {
        data.month = selectedMonth.value;
        data.year = selectedYear.value;
    }

    router.visit(route('attendance.class-report'), { data });
};

const getPercentage = (student: any): number => {
    if (student.total === 0) return 0;
    return Math.round((student.present / student.total) * 100);
};

const getPercentageClass = (student: any): string => {
    const percentage = getPercentage(student);
    if (percentage >= 90) return 'text-success font-medium';
    if (percentage >= 75) return 'text-primary font-medium';
    if (percentage >= 60) return 'text-warning font-medium';
    return 'text-destructive font-medium';
};
</script>
