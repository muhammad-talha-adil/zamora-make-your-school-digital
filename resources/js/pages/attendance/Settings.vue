<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

import LeaveTypesTable from '@/components/tables/LeaveTypesTable.vue';
import HolidaysTable from '@/components/tables/HolidaysTable.vue';
import PastHolidaysTable from '@/components/tables/PastHolidaysTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    leaveTypes: any;
    holidays: any;
    campuses: any;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Attendance',
        href: '/attendance',
    },
    {
        title: 'Settings',
        href: '/attendance/settings',
    },
];

const activeTab = ref('leave-types');
const showPastHolidays = ref(false);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Attendance Settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Attendance Settings
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage leave types and holidays for attendance.
                    </p>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 overflow-x-auto">
                        <button
                            @click="activeTab = 'leave-types'"
                            :class="[
                                activeTab === 'leave-types'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Leave Types
                        </button>
                        <button
                            @click="activeTab = 'holidays'"
                            :class="[
                                activeTab === 'holidays'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Holidays
                        </button>
                    </nav>
                </div>

                <!-- Leave Types Tab -->
                <div v-if="activeTab === 'leave-types'">
                    <LeaveTypesTable :leave-types="leaveTypes" />
                </div>

                <!-- Holidays Tab -->
                <div v-if="activeTab === 'holidays'">
                    <template v-if="!showPastHolidays">
                        <HolidaysTable 
                            :holidays="holidays" 
                            :campuses="campuses" 
                            @show-past="showPastHolidays = true"
                        />
                    </template>
                    <template v-else>
                        <PastHolidaysTable @back="showPastHolidays = false" />
                    </template>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
