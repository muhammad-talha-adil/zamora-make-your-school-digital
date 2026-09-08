<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Attendance Details" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Attendance Details
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        {{ formatDate(props.attendance.attendance_date) }} - {{ props.attendance.class?.name }} {{ props.attendance.section?.name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button v-if="!props.attendance.is_locked" variant="outline" @click="router.visit(route('attendance.edit', { attendance_date: props.attendance.attendance_date, class_id: props.attendance.class_id, section_id: props.attendance.section_id }))">
                        <Icon icon="edit" class="mr-1" />
                        Edit
                    </Button>
                    <Button v-if="!props.attendance.is_locked" variant="destructive" @click="handleLock">
                        <Icon icon="lock" class="mr-1" />
                        Lock
                    </Button>
                    <Button v-else variant="outline" @click="handleUnlock">
                        <Icon icon="unlock" class="mr-1" />
                        Unlock
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="text-2xl font-bold text-success">{{ stats.present }}</div>
                    <div class="text-sm text-muted-foreground">Present</div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="text-2xl font-bold text-destructive">{{ stats.absent }}</div>
                    <div class="text-sm text-muted-foreground">Absent</div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="text-2xl font-bold text-warning">{{ stats.leave }}</div>
                    <div class="text-sm text-muted-foreground">On Leave</div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="text-2xl font-bold text-primary">{{ stats.late }}</div>
                    <div class="text-sm text-muted-foreground">Late</div>
                </div>
            </div>

            <!-- Students Table -->
            <div class="bg-card rounded-lg border border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Admission No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Check In</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Check Out</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="attendanceStudent in props.attendanceStudents" :key="attendanceStudent.id" class="transition-colors hover:bg-accent">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                            <span class="text-primary font-medium">{{ attendanceStudent.student?.name?.charAt(0) || 'S' }}</span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-foreground">{{ attendanceStudent.student?.name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ attendanceStudent.student?.registration_no }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ attendanceStudent.student?.admission_no }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusClass(attendanceStudent.attendance_status?.code)]">
                                        {{ attendanceStudent.attendance_status?.name }} ({{ attendanceStudent.attendance_status?.code }})
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ attendanceStudent.check_in || '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ attendanceStudent.check_out || '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ attendanceStudent.remarks || '-' }}
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
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import type { AttendanceShowProps } from '@/types/attendance';

const props = defineProps<AttendanceShowProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Attendance', href: '/attendance' },
    { title: 'Attendance Details', href: '#' },
];

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
};

const stats = computed(() => {
    const students = props.attendanceStudents;
    return {
        present: students.filter((s) => s.attendance_status?.code === 'P').length,
        absent: students.filter((s) => s.attendance_status?.code === 'A').length,
        leave: students.filter((s) => s.attendance_status?.code === 'L').length,
        late: students.filter((s) => s.attendance_status?.code === 'LT').length,
    };
});

const getStatusClass = (code?: string): string => {
    switch (code) {
        case 'P': return 'bg-success/10 text-success';
        case 'A': return 'bg-destructive/10 text-destructive';
        case 'L': return 'bg-warning/10 text-warning';
        case 'LT': return 'bg-primary/10 text-primary';
        default: return 'bg-muted text-foreground';
    }
};

const handleLock = () => {
    router.post(route('attendance.lock', { attendance: props.attendance.id }), {}, { onSuccess: () => router.reload() });
};

const handleUnlock = () => {
    router.post(route('attendance.unlock', { attendance: props.attendance.id }), {}, { onSuccess: () => router.reload() });
};
</script>
