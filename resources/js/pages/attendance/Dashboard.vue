<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Attendance Dashboard" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Attendance Dashboard
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Overview of today's attendance status
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button @click="router.visit(route('attendance.create'))">
                        <Icon icon="check-circle" class="mr-1" />
                        Mark Attendance
                    </Button>
                    <Button variant="outline" @click="router.visit(route('attendance.index'))">
                        <Icon icon="list" class="mr-1" />
                        View All
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <select
                        v-model="filters.campus_id"
                        @change="applyFilters"
                        class="w-full sm:w-48 rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                    >
                        <option value="">All Campuses</option>
                        <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                            {{ campus.name }}
                        </option>
                    </select>
                    <select
                        v-model="filters.session_id"
                        @change="applyFilters"
                        class="w-full sm:w-48 rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                    >
                        <option value="">All Sessions</option>
                        <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                            {{ session.name }}
                        </option>
                    </select>
                    <Button @click="applyFilters">
                        <Icon icon="filter" class="mr-1" />
                        Apply
                    </Button>
                </div>
            </div>

            <!-- Today's Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Students -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total Students</p>
                            <p class="text-2xl font-bold text-foreground">{{ props.todayStats.total_students }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                            <Icon icon="users" class="h-6 w-6 text-primary" />
                        </div>
                    </div>
                </div>

                <!-- Attendance Rate -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Attendance Rate</p>
                            <p class="text-2xl font-bold text-success">{{ props.todayStats.attendance_percentage }}%</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-success/10 flex items-center justify-center">
                            <Icon icon="trending-up" class="h-6 w-6 text-success" />
                        </div>
                    </div>
                    <div v-if="props.yesterdayStats.attendance_percentage > 0" class="mt-2 text-xs">
                        <span :class="props.todayStats.attendance_percentage >= props.yesterdayStats.attendance_percentage ? 'text-success' : 'text-destructive'">
                            {{ props.todayStats.attendance_percentage >= props.yesterdayStats.attendance_percentage ? '+' : '' }}{{ (props.todayStats.attendance_percentage - props.yesterdayStats.attendance_percentage).toFixed(1) }}%
                        </span>
                        vs yesterday
                    </div>
                </div>

                <!-- Present -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Present</p>
                            <p class="text-2xl font-bold text-success">{{ props.todayStats.present }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-success/10 flex items-center justify-center">
                            <Icon icon="user-check" class="h-6 w-6 text-success" />
                        </div>
                    </div>
                </div>

                <!-- Absent -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Absent</p>
                            <p class="text-2xl font-bold text-destructive">{{ props.todayStats.absent }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-destructive/10 flex items-center justify-center">
                            <Icon icon="user-x" class="h-6 w-6 text-destructive" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Attendance Trend -->
            <div class="bg-card rounded-lg border border-border p-4">
                <h3 class="text-lg font-semibold text-foreground mb-4">Attendance Rate (Last 7 Days)</h3>
                <LineChart
                    v-if="weeklyTrendPoints.length > 0"
                    :points="weeklyTrendPoints"
                    :format-value="(value) => `${value}%`"
                    color-var="--primary"
                />
            </div>

            <!-- Second Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Classes Progress -->
                <div class="bg-card rounded-lg border border-border p-4 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Classes Progress</h3>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-1">
                            <div class="flex flex-wrap gap-2 justify-between text-sm mb-1">
                                <span class="text-muted-foreground">Classes with Attendance</span>
                                <span class="font-medium text-foreground">{{ props.classesWithAttendance }} / {{ props.totalClasses }}</span>
                            </div>
                            <div class="w-full bg-muted rounded-full h-2">
                                <div 
                                    class="bg-primary h-2 rounded-full transition-all" 
                                    :style="{ width: `${(props.classesWithAttendance / props.totalClasses) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Class Summary Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Class</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Present</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Absent</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Leave</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Late</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="cls in props.classSummaries" :key="cls.id" class="hover:bg-accent">
                                    <td class="px-3 py-2 text-sm font-medium text-foreground">
                                        {{ cls.class_name }} - {{ cls.section_name }}
                                    </td>
                                    <td class="px-3 py-2 text-sm text-success">{{ cls.present }}</td>
                                    <td class="px-3 py-2 text-sm text-destructive">{{ cls.absent }}</td>
                                    <td class="px-3 py-2 text-sm text-warning">{{ cls.leave }}</td>
                                    <td class="px-3 py-2 text-sm text-warning">{{ cls.late }}</td>
                                    <td class="px-3 py-2">
                                        <span :class="cls.is_locked ? 'bg-destructive/10 text-destructive' : 'bg-success/10 text-success'" class="px-2 py-0.5 text-xs font-medium rounded-full">
                                            {{ cls.is_locked ? 'Locked' : 'Open' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="props.classSummaries.length === 0">
                                    <td colspan="6" class="px-3 py-4 text-center text-sm text-muted-foreground">
                                        No attendance records for today
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Upcoming Holidays -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Upcoming Holidays</h3>
                    <div class="space-y-3">
                        <div 
                            v-for="holiday in props.upcomingHolidays" 
                            :key="holiday.id"
                            class="flex items-start gap-3 p-2 rounded-lg bg-muted"
                        >
                            <Icon icon="calendar" class="h-5 w-5 text-destructive mt-0.5" />
                            <div>
                                <p class="text-sm font-medium text-foreground">{{ holiday.title }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDateRange(holiday.start_date, holiday.end_date) }}
                                </p>
                                <span v-if="holiday.is_national" class="text-xs text-primary">National</span>
                            </div>
                        </div>
                        <div v-if="props.upcomingHolidays.length === 0" class="text-center text-sm text-muted-foreground py-4">
                            No upcoming holidays
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="bg-card rounded-lg border border-border p-4">
                <h3 class="text-lg font-semibold text-foreground mb-4">Recent Attendance Records</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Class</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Section</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="record in props.recentAttendances" :key="record.id" class="hover:bg-accent">
                                <td class="px-4 py-2 text-sm text-foreground">
                                    {{ formatDate(record.attendance_date) }}
                                </td>
                                <td class="px-4 py-2 text-sm text-muted-foreground">
                                    {{ record.class?.name || 'N/A' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-muted-foreground">
                                    {{ record.section?.name || 'All' }}
                                </td>
                                <td class="px-4 py-2">
                                    <span :class="record.is_locked ? 'bg-destructive/10 text-destructive' : 'bg-success/10 text-success'" class="px-2 py-0.5 text-xs font-medium rounded-full">
                                        {{ record.is_locked ? 'Locked' : 'Open' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="props.recentAttendances.length === 0">
                                <td colspan="4" class="px-4 py-4 text-center text-sm text-muted-foreground">
                                    No recent attendance records
                                </td>
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
import { reactive, computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import LineChart from '@/components/charts/LineChart.vue';
import type { BreadcrumbItem } from '@/types';

interface Props {
    todayStats: {
        total_students: number;
        present: number;
        absent: number;
        leave: number;
        late: number;
        attendance_percentage: number;
        total_classes: number;
    };
    yesterdayStats: {
        total_students: number;
        present: number;
        absent: number;
        leave: number;
        late: number;
        attendance_percentage: number;
        total_classes: number;
    };
    classSummaries: Array<{
        id: number;
        class_name: string;
        section_name: string;
        total: number;
        present: number;
        absent: number;
        leave: number;
        late: number;
        is_locked: boolean;
        taken_by: string;
        attendance_date: string;
    }>;
    upcomingHolidays: Array<{
        id: number;
        title: string;
        start_date: string;
        end_date: string;
        is_national: boolean;
    }>;
    recentAttendances: Array<{
        id: number;
        attendance_date: string;
        class?: { name: string };
        section?: { name: string };
        is_locked: boolean;
    }>;
    totalClasses: number;
    classesWithAttendance: number;
    campuses: Array<{ id: number; name: string }>;
    sessions: Array<{ id: number; name: string }>;
    selectedCampusId: number | null;
    selectedSessionId: number | null;
    today: string;
    weeklyTrend: Array<{ date: string; attendance_percentage: number }>;
}

const props = defineProps<Props>();

const weeklyTrendPoints = computed(() =>
    (props.weeklyTrend || []).map((point) => ({
        label: new Date(point.date).toLocaleDateString('en-US', { weekday: 'short' }),
        value: point.attendance_percentage,
    })),
);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Attendance', href: '/attendance' },
    { title: 'Dashboard', href: '/attendance/dashboard' },
];

const filters = reactive({
    campus_id: props.selectedCampusId || '',
    session_id: props.selectedSessionId || '',
});

const applyFilters = () => {
    router.visit(route('attendance.dashboard'), {
        data: {
            campus_id: filters.campus_id || undefined,
            session_id: filters.session_id || undefined,
        },
        preserveState: true,
    });
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateRange = (start: string, end: string) => {
    if (start === end) {
        return formatDate(start);
    }
    return `${formatDate(start)} - ${formatDate(end)}`;
};
</script>
