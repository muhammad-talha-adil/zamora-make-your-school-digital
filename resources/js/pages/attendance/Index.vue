<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Attendance" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Attendance Management
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Track and manage student attendance records
                    </p>
                </div>
                <Button @click="router.visit(route('attendance.create'))">
                    <Icon icon="check-circle" class="mr-1" />
                    Mark Attendance
                </Button>
                <Button variant="outline" @click="router.visit(route('attendance.class-report'))">
                    <Icon icon="file-text" class="mr-1" />
                    Class Report
                </Button>
            </div>

            <!-- Filters -->
            <AttendanceFilters
                :campuses="props.campuses"
                :sessions="props.sessions"
                :classes="props.classes"
                :sections="props.sections"
                :filters="props.filters"
            />

            <!-- Attendance Table -->
            <AttendanceTable
                :attendances="props.attendances"
                @lock="handleLock"
                @unlock="handleUnlock"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import AttendanceFilters from '@/components/attendance/AttendanceFilters.vue';
import AttendanceTable from '@/components/attendance/AttendanceTable.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import type { AttendanceIndexProps } from '@/types/attendance';

const props = defineProps<AttendanceIndexProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Attendance', href: '/attendance' },
    { title: 'Attendance List', href: '/attendance' },
];

const handleLock = (attendance: { id: number }) => {
    router.post(route('attendance.lock', { attendance: attendance.id }), {}, {
        onSuccess: () => router.reload(),
    });
};

const handleUnlock = (attendance: { id: number }) => {
    router.post(route('attendance.unlock', { attendance: attendance.id }), {}, {
        onSuccess: () => router.reload(),
    });
};
</script>
