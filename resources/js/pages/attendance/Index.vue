<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Attendance" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Attendance Management
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Track and manage student attendance records
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button @click="router.visit(route('attendance.create'))">
                        <Icon icon="check-circle" class="mr-1" />
                        Mark Attendance
                    </Button>
                    <Button variant="outline" @click="router.visit(route('attendance.class-report'))">
                        <Icon icon="file-text" class="mr-1" />
                        Class Report
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <AttendanceFilters
                :campuses="props.campuses"
                :sessions="props.sessions"
                :classes="props.classes"
                :sections="props.sections"
                :filters="props.filters"
            />

            <!-- Prompt to Load Data -->
            <div v-if="!hasSearched" class="bg-primary/10 border border-primary/40 rounded-lg p-8 text-center">
                <Icon icon="search" class="h-12 w-12 mx-auto text-primary mb-3" />
                <h3 class="text-lg font-semibold text-foreground mb-2">Select Filters and Click Load</h3>
                <p class="text-muted-foreground">Choose the required filters above and click the "Load" button to view attendance records.</p>
            </div>

            <!-- Attendance Table -->
            <AttendanceTable
                v-else
                :attendances="props.attendances"
                @lock="handleLock"
                @unlock="handleUnlock"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
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

// Check if 'load' parameter exists in URL - if yes, show data
const hasSearched = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('load') === '1';
});

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
