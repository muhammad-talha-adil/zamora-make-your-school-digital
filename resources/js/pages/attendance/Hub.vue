<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { AttendanceIndexProps, AttendanceCreateProps, AttendanceClassReportProps } from '@/types/attendance';
import AttendanceListPanel from './panels/AttendanceListPanel.vue';
import MarkAttendancePanel from './panels/MarkAttendancePanel.vue';
import ClassReportPanel from './panels/ClassReportPanel.vue';
import LeavePanel from './panels/LeavePanel.vue';

interface LeaveTypeOption {
    id: number;
    name: string;
}

interface StudentOption {
    id: number;
    name: string;
    registration_no?: string | null;
}

interface Props {
    listData: AttendanceIndexProps | null;
    createData: AttendanceCreateProps | null;
    classReportData: AttendanceClassReportProps | null;
    leaveData: {
        leaveTypes: LeaveTypeOption[];
        students: StudentOption[];
        canViewPending: boolean;
        canDecide: boolean;
        defaultStudentId: number | null;
    } | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Student', href: '/students' },
    { title: 'Attendance', href: '/attendance' },
];

const tabs = computed(() => [
    { id: 'list', label: 'Attendance List', visible: props.listData !== null },
    { id: 'mark', label: 'Mark Attendance', visible: props.createData !== null },
    { id: 'reports', label: 'Student Reports', visible: props.classReportData !== null },
    { id: 'leave', label: 'Leave', visible: props.leaveData !== null },
].filter((tab) => tab.visible));

const activeTab = ref(tabs.value[0]?.id ?? 'list');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Attendance" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">
                    Attendance
                </h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Mark, review and report on student attendance, and manage leave applications
                </p>
            </div>

            <!-- Tabs -->
            <div class="border-b border-border overflow-x-auto overflow-hidden">
                <nav class="-mb-px flex space-x-4 md:space-x-8 min-w-0">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            activeTab === tab.id
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Tabs are kept mounted (v-show) rather than unmounted (v-if) so
                 switching tabs never discards a tab's filters or in-progress
                 form state, matching the Fee Settings / School Profile pattern. -->

            <!-- Attendance List Tab -->
            <div v-if="listData" v-show="activeTab === 'list'">
                <AttendanceListPanel v-bind="listData" />
            </div>

            <!-- Mark Attendance Tab -->
            <div v-if="createData" v-show="activeTab === 'mark'">
                <MarkAttendancePanel v-bind="createData" />
            </div>

            <!-- Student Reports Tab -->
            <div v-if="classReportData" v-show="activeTab === 'reports'">
                <ClassReportPanel v-bind="classReportData" />
            </div>

            <!-- Leave Tab -->
            <div v-if="leaveData" v-show="activeTab === 'leave'">
                <LeavePanel v-bind="leaveData" />
            </div>
        </div>
    </AppLayout>
</template>
