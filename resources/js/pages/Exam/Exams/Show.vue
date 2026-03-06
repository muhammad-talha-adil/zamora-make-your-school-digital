<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Exam Details" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        {{ exam.name }}
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Exam Details and Configuration
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="router.visit(route('exam.edit-page', exam.id))">
                        <Icon icon="edit" class="mr-1" />
                        Edit
                    </Button>
                    <Button variant="outline" @click="router.visit(route('exam.index-page'))">
                        <Icon icon="arrow-left" class="mr-1" />
                        Back
                    </Button>
                </div>
            </div>

            <!-- Exam Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Exam Type</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ exam.exam_type?.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Session</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ exam.session?.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                        <span
                            :class="[
                                'px-2 py-1 text-xs font-medium rounded-full',
                                exam.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                exam.status === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                                exam.status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            ]"
                        >
                            {{ exam.status }}
                        </span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Start Date</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ exam.start_date }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">End Date</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ exam.end_date }}</p>
                    </div>
                </div>
            </div>

            <!-- Offerings Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Offerings</h2>
                <div v-if="exam.exam_offerings && exam.exam_offerings.length > 0" class="space-y-3">
                    <div
                        v-for="offering in exam.exam_offerings"
                        :key="offering.id"
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                    >
                        <div class="flex items-center gap-3">
                            <Icon icon="building" class="h-5 w-5 text-gray-500" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ offering.campus?.name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ offering.exam_groups?.length || 0 }} Groups</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="offering.is_published" class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Published</span>
                            <span v-if="offering.is_locked" class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Locked</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    No offerings created yet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import type { ExamShowProps } from '@/types/exam';

const props = defineProps<ExamShowProps>();

const exam = props.exam;

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: exam.name, href: `/exams/${exam.id}` },
];
</script>
