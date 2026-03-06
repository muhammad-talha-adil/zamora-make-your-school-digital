<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Exams" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Exam Management
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Manage exams and their configurations
                    </p>
                </div>
                <ExamForm
                    :exam-types="props.examTypes"
                    :sessions="props.sessions"
                    trigger="Create Exam"
                    @saved="fetchExams"
                />
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap" role="search" aria-label="Exam filters">
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-session" class="sr-only">Filter by Session</Label>
                    <select
                        id="filter-session"
                        v-model="filters.session_id"
                        @change="applyFilters"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Sessions</option>
                        <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                            {{ session.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-type" class="sr-only">Filter by Type</Label>
                    <select
                        id="filter-type"
                        v-model="filters.exam_type_id"
                        @change="applyFilters"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Types</option>
                        <option v-for="type in props.examTypes" :key="type.id" :value="type.id">
                            {{ type.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="search-exam" class="sr-only">Search by Exam Name</Label>
                    <Input
                        id="search-exam"
                        v-model="filters.search"
                        @input="onSearchInput"
                        placeholder="Search exams..."
                        class="min-h-10 md:min-h-11"
                    />
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="block lg:hidden space-y-3">
                <div
                    v-for="(exam, index) in examsData"
                    :key="exam.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Sr# {{ index + 1 }}</div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ exam.name }}</div>
                            <div class="text-xs text-gray-500">{{ exam.exam_type?.name }}</div>
                        </div>
                        <div class="flex items-center gap-1">
                            <select
                                v-model="exam.status"
                                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1 text-xs font-medium"
                            >
                                <option value="scheduled">Scheduled</option>
                                <option value="active">Active</option>
                                <option value="marking">Marking</option>
                                <option value="published">Published</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <Button
                                v-if="savingStatus === exam.id"
                                variant="ghost"
                                size="sm"
                                class="h-7 w-7 p-0"
                                disabled
                            >
                                <Icon icon="loader" class="h-3 w-3 animate-spin" />
                            </Button>
                            <Button
                                v-else
                                variant="secondary"
                                size="sm"
                                class="h-7 px-2"
                                @click="saveStatus(exam)"
                            >
                                <Icon icon="save" class="h-3 w-3" />
                            </Button>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <Icon icon="calendar" class="h-4 w-4" />
                            <span>{{ exam.start_date_formatted }} - {{ exam.end_date_formatted }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Icon icon="clock" class="h-4 w-4" />
                            <span>{{ exam.session?.name }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-2 overflow-x-auto">
                        <!-- <Button variant="outline" size="sm" @click="router.visit(route('exam.show-page', exam.id))" class="shrink-0">
                            <Icon icon="eye" class="mr-1" />View
                        </Button> -->
                        <Button 
                            variant="outline" 
                            size="sm" 
                            @click="router.visit(route('exam.papers.create-page', { exam_id: exam.id }))" 
                            class="shrink-0 w-28"
                            :disabled="isAddPaperDisabled(exam)"
                            :class="{ 'opacity-50 cursor-not-allowed': isAddPaperDisabled(exam) }"
                        >
                            <Icon icon="file-plus" class="mr-1" />Add Paper
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            @click="router.visit(route('exam.marking.select-page', { exam_id: exam.id }))" 
                            class="shrink-0 w-28"
                            :disabled="isMarkingDisabled(exam) || !exam.paper_count || exam.paper_count === 0"
                            :class="{ 'opacity-50 cursor-not-allowed': isMarkingDisabled(exam) || !exam.paper_count || exam.paper_count === 0 }"
                        >
                            <Icon icon="edit-3" class="mr-1" />Marking
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            @click="router.visit(route('exam.results.index-page', { exam_id: exam.id }))" 
                            class="shrink-0 w-28"
                            :disabled="isResultDisabled(exam)"
                            :class="{ 'opacity-50 cursor-not-allowed': isResultDisabled(exam) }"
                        >
                            <Icon icon="bar-chart-2" class="mr-1" />Results
                        </Button>
                        <ExamForm
                            :exam="exam"
                            :exam-types="props.examTypes"
                            :sessions="props.sessions"
                            trigger="Edit"
                            variant="outline"
                            size="sm"
                            class="shrink-0 w-28"
                            :disabled="isEditDisabled(exam)"
                            @saved="fetchExams"
                        />
                    </div>
                </div>
                <div v-if="examsData.length === 0" class="text-center py-8 text-gray-500">
                    No exams found.
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300 w-12">
                                    Sr#
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Exam Name
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Type
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Session
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Dates
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(exam, index) in examsData" :key="exam.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ index + 1 }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ exam.name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ exam.exam_type?.name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ exam.session?.name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ exam.start_date_formatted }} - {{ exam.end_date_formatted }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <select
                                            v-model="exam.status"
                                            class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1 text-xs font-medium"
                                        >
                                            <option value="scheduled">Scheduled</option>
                                            <option value="active">Active</option>
                                            <option value="marking">Marking</option>
                                            <option value="published">Published</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                        <Button
                                            v-if="savingStatus === exam.id"
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0"
                                            disabled
                                        >
                                            <Icon icon="loader" class="h-3 w-3 animate-spin" />
                                        </Button>
                                        <Button
                                            v-else
                                            variant="secondary"
                                            size="sm"
                                            class="h-7 px-2"
                                            @click="saveStatus(exam)"
                                        >
                                            <Icon icon="save" class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        <!-- <Button variant="outline" size="sm" @click="router.visit(route('exam.show-page', exam.id))" class="min-h-8">
                                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                        </Button> -->
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            @click="router.visit(route('exam.papers.create-page', { exam_id: exam.id }))" 
                                            class="min-h-8 w-28"
                                            :disabled="isAddPaperDisabled(exam)"
                                            :class="{ 'opacity-50 cursor-not-allowed': isAddPaperDisabled(exam) }"
                                        >
                                            <Icon icon="file-plus" class="mr-1 h-3 w-3" />Add Paper
                                        </Button>
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            @click="router.visit(route('exam.marking.select-page', { exam_id: exam.id }))" 
                                            class="min-h-8 w-28"
                                            :disabled="isMarkingDisabled(exam) || !exam.paper_count || exam.paper_count === 0"
                                            :class="{ 'opacity-50 cursor-not-allowed': isMarkingDisabled(exam) || !exam.paper_count || exam.paper_count === 0 }"
                                        >
                                            <Icon icon="edit-3" class="mr-1 h-3 w-3" />Marking
                                        </Button>
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            @click="router.visit(route('exam.results.index-page', { exam_id: exam.id }))" 
                                            class="min-h-8 w-28"
                                            :disabled="isResultDisabled(exam)"
                                            :class="{ 'opacity-50 cursor-not-allowed': isResultDisabled(exam) }"
                                        >
                                            <Icon icon="bar-chart-2" class="mr-1 h-3 w-3" />Results
                                        </Button>
                                        <ExamForm
                                            :exam="exam"
                                            :exam-types="props.examTypes"
                                            :sessions="props.sessions"
                                            trigger="Edit"
                                            variant="outline"
                                            size="sm"
                                            class="w-28"
                                            :disabled="isEditDisabled(exam)"
                                            @saved="fetchExams"
                                        />
                                    </div>
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
import { reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import ExamForm from '@/components/forms/ExamForm.vue';
import type { ExamIndexProps, Exam } from '@/types/exam';

const props = defineProps<ExamIndexProps>();

// Local state for exams (real-time updates)
const examsData = ref<Exam[]>(props.exams);

// Status saving state
const savingStatus = ref<number | null>(null);

// Watch for prop changes and update local state
watch(() => props.exams, (newExams) => {
    examsData.value = newExams;
}, { deep: true });

/**
 * Check if Add Paper button should be disabled
 * Rules:
 * - When marking status is enabled (status = 'marking'): Disable
 * - When exam status is 'published': Disable
 * - When exam status is 'completed': Disable
 * - When exam status is 'cancelled': Disable
 */
const isAddPaperDisabled = (exam: Exam): boolean => {
    return exam.status === 'marking' || 
           exam.status === 'published' || 
           exam.status === 'completed' || 
           exam.status === 'cancelled';
};

/**
 * Check if Marking button should be disabled
 * Rules:
 * - When exam status is 'scheduled' or 'active': Disable
 * - When exam status is 'completed': Disable
 * - When exam status is 'cancelled': Disable
 */
const isMarkingDisabled = (exam: Exam): boolean => {
    return exam.status === 'scheduled' || 
           exam.status === 'active' || 
           exam.status === 'completed' || 
           exam.status === 'cancelled';
};

/**
 * Check if Result button should be disabled
 * Rules:
 * - When exam status is 'scheduled' or 'active': Disable
 * - When marking status is enabled (status = 'marking'): Disable
 * - When exam status is 'completed': Disable
 * - When exam status is 'cancelled': Disable
 * - Also disable if no results exist
 */
const isResultDisabled = (exam: Exam): boolean => {
    return exam.status === 'scheduled' || 
           exam.status === 'active' || 
           exam.status === 'marking' || 
           exam.status === 'completed' || 
           exam.status === 'cancelled' ||
           !exam.result_count || 
           exam.result_count === 0;
};

/**
 * Check if Edit button should be disabled
 * Rules:
 * - When exam status is 'published': Disable
 * - When exam status is 'completed': Disable
 * Note: Edit is enabled for 'cancelled' status per requirements
 */
const isEditDisabled = (exam: Exam): boolean => {
    return exam.status === 'published' || 
           exam.status === 'completed';
};

// Fetch exams from API (real-time update without page reload)
const fetchExams = () => {
    const params = new URLSearchParams();
    if (filters.session_id) params.append('session_id', filters.session_id);
    if (filters.exam_type_id) params.append('exam_type_id', filters.exam_type_id);
    if (filters.search) params.append('search', filters.search);

    axios.get(route('exam.index') + `?${params.toString()}`).then((response) => {
        examsData.value = response.data.data || response.data;
    });
};

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Exam List', href: '/exams' },
];

const filters = reactive({
    session_id: props.filters?.session_id || '',
    exam_type_id: props.filters?.exam_type_id || '',
    search: props.filters?.search || '',
});

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const onSearchInput = () => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
    searchDebounceTimer = window.setTimeout(() => {
        applyFilters();
    }, 300);
};

const buildQueryString = () => {
    const params = new URLSearchParams();
    if (filters.session_id) params.append('session_id', filters.session_id);
    if (filters.exam_type_id) params.append('exam_type_id', filters.exam_type_id);
    if (filters.search) params.append('search', filters.search);
    return params.toString();
};

/**
 * Save exam status
 */
const saveStatus = async (exam: Exam) => {
    savingStatus.value = exam.id;
    const previousStatus = exam.status;
    
    try {
        const response = await axios.patch(route('exam.status', exam.id), {
            status: exam.status,
        });
        
        if (response.data) {
            // Only update the status field, keeping all other data intact
            const index = examsData.value.findIndex(e => e.id === exam.id);
            if (index !== -1) {
                examsData.value[index].status = response.data.data?.status || exam.status;
            }
            // Show success toast
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'success', message: 'Status updated successfully!' }
            }));
        }
    } catch (error: any) {
        console.error('Error saving status:', error);
        // Revert the status on error
        const index = examsData.value.findIndex(e => e.id === exam.id);
        if (index !== -1) {
            examsData.value[index].status = previousStatus;
        }
        // Show error toast
        const errorMessage = error.response?.data?.message || 'Failed to update status';
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { type: 'error', message: errorMessage }
        }));
    } finally {
        savingStatus.value = null;
    }
};

const applyFilters = () => {
    // Update URL and fetch exams without full page reload
    const queryString = buildQueryString();
    const newUrl = route('exam.index-page') + (queryString ? `?${queryString}` : '');
    window.history.pushState({}, '', newUrl);
    fetchExams();
};
</script>
