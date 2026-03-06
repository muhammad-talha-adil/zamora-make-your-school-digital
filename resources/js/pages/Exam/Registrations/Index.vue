<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Exam Registrations" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Exam Registrations
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Manage student registrations for exam groups
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap">
                <div class="w-full sm:w-56 md:w-64">
                    <Label for="filter-group" class="sr-only">Filter by Group</Label>
                    <select
                        id="filter-group"
                        v-model="filters.group_id"
                        @change="applyFilters"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Groups</option>
                        <option v-for="group in props.groups" :key="group.id" :value="group.id">
                            {{ group.exam_offering?.exam?.name }} - {{ group.class?.name }} {{ group.section?.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Group Info -->
            <div v-if="props.group" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <Icon icon="info" class="h-5 w-5 text-blue-500" />
                    <div>
                        <p class="font-medium text-blue-900 dark:text-blue-200">
                            {{ props.group.exam_offering?.exam?.name }} - {{ props.group.class?.name }} {{ props.group.section?.name }}
                        </p>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            {{ props.group.exam_offering?.campus?.name }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <Button size="sm" @click="generateFromEnrollments">
                        <Icon icon="refresh-cw" class="mr-1" />
                        Generate from Enrollments
                    </Button>
                </div>
            </div>

            <!-- Registrations Table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Roll No
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Student
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Registration No
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="registration in props.registrations" :key="registration.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ registration.roll_no_snapshot || '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ registration.student?.user?.name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ registration.student?.registration_no }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            registration.status === 'registered' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                        ]"
                                    >
                                        {{ registration.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        <Button 
                                            v-if="registration.status === 'registered'" 
                                            variant="outline" 
                                            size="sm" 
                                            @click="withdrawRegistration(registration.id)"
                                            class="min-h-8 text-red-600"
                                        >
                                            <Icon icon="user-minus" class="mr-1 h-3 w-3" />Withdraw
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="props.registrations.length === 0" class="text-center py-8 text-gray-500">
                No registrations found.
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import type { RegistrationIndexProps } from '@/types/exam';

const props = defineProps<RegistrationIndexProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Registrations', href: '/exams/registrations' },
];

const filters = reactive({
    group_id: props.filters?.group_id || '',
});

const applyFilters = () => {
    router.visit(route('exam.registrations.index-page') + `?group_id=${filters.group_id}`, {
        preserveState: true,
    });
};

const generateFromEnrollments = () => {
    if (!filters.group_id) return;
    router.post(route('exam.registrations.generate', { groupId: filters.group_id }), {}, {
        onSuccess: () => router.reload(),
    });
};

const withdrawRegistration = (id: number) => {
    router.patch(route('exam.registrations.withdraw', { id }), {}, {
        onSuccess: () => router.reload(),
    });
};
</script>
