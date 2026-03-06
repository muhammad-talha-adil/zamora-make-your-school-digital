<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Exam Results" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                    Exam Results
                </h1>
                <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                    View and manage exam results
                </p>
            </div>

            <!-- Exam Selection Panel -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                    <!-- Exam Selection -->
                    <div class="md:col-span-2 lg:col-span-1 space-y-2">
                        <Label for="exam">
                            Select Exam <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="exam"
                            v-model="filters.exam_id"
                            :disabled="isExamLocked"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed"
                            :class="{ 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700': isExamLocked }"
                        >
                            <option value="">Select Exam</option>
                            <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                                {{ exam.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Campus Filter -->
                    <div class="space-y-2">
                        <Label for="campus">Campus</Label>
                        <select
                            id="campus"
                            v-model="filters.campus_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="all">All Campuses</option>
                            <option v-for="campus in filterOptions.campuses" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Class Filter -->
                    <div class="space-y-2">
                        <Label for="class">Class</Label>
                        <select
                            id="class"
                            v-model="filters.class_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="all">All Classes</option>
                            <option v-for="cls in filterOptions.classes" :key="cls.id" :value="cls.id">
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Section Filter -->
                    <div class="space-y-2">
                        <Label for="section">Section</Label>
                        <select
                            id="section"
                            v-model="filters.section_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="all">All Sections</option>
                            <option v-for="section in filterOptions.sections" :key="section.id" :value="section.id">
                                {{ section.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Student Search -->
                    <div class="space-y-2">
                        <Label for="student-search">Search Student</Label>
                        <div class="relative">
                            <input
                                id="student-search"
                                v-model="filters.search"
                                placeholder="Search by name..."
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                @keyup.enter="searchResults"
                            />
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <Button 
                        @click="searchResults" 
                        :disabled="!filters.exam_id || loading"
                    >
                        <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        {{ loading ? 'Searching...' : 'Search Results' }}
                    </Button>

                    <Button 
                        v-if="hasSearched"
                        variant="secondary"
                        @click="resetFilters"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset
                    </Button>
                </div>

                <!-- Selected Exam Notice -->
                <div v-if="isExamLocked && selectedExamName" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <Icon icon="info" class="inline h-4 w-4 mr-1" />
                        Viewing results for: <strong>{{ selectedExamName }}</strong>
                    </p>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-sm text-red-800 dark:text-red-300">{{ error }}</p>
            </div>

            <!-- Loading State -->
            <div v-if="loading && !initialLoad" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                <svg class="animate-spin h-8 w-8 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-gray-600 dark:text-gray-400">Loading results...</p>
            </div>

            <!-- Results Section -->
            <div v-else-if="hasSearched && !error">
                <!-- Toppers Section -->
                <div v-if="toppers.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-100 dark:bg-gray-900 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <Icon icon="award" class="h-5 w-5 text-yellow-500" />
                            Top 5 Toppers
                        </h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                            <div 
                                v-for="(topper, index) in toppers" 
                                :key="topper.id"
                                class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 border border-gray-200 dark:border-gray-700"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <div 
                                        class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold text-white"
                                        :class="getTopperBadgeClass(index)"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    <Icon v-if="index === 0" icon="award" class="h-4 w-4 text-yellow-500" />
                                </div>
                                <div class="font-medium text-gray-900 dark:text-white text-sm truncate">
                                    {{ topper.student }}
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ topper.class?.name }} - {{ topper.section?.name || 'N/A' }}
                                </div>
                                <div class="mt-2 flex items-baseline gap-1">
                                    <span class="text-lg font-bold" :class="getScoreColorClass(topper.percentage)">
                                        {{ topper.percentage }}%
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        ({{ topper.total_obtained }}/{{ topper.total_max_marks }})
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Results Table -->
                <div v-if="results.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            All Results
                        </h3>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Total: {{ pagination.total }} student{{ pagination.total !== 1 ? 's' : '' }}
                        </span>
                    </div>
                    
                    <!-- Mobile Card View -->
                    <div class="block lg:hidden">
                        <div v-for="(result, index) in results" :key="result.id" class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-start mb-2">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ (pagination.from || 0) + index }}. {{ result.student }}
                                </div>
                                <span 
                                    class="px-2 py-1 rounded text-xs font-medium"
                                    :class="getGradeClass(result.overallGradeItem?.grade_letter)"
                                >
                                    {{ result.overallGradeItem?.grade_letter || 'N/A' }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <div class="flex flex-wrap gap-2">
                                    <span v-if="result.campus" class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">
                                        {{ result.campus.name }}
                                    </span>
                                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">
                                        {{ result.class?.name }}
                                    </span>
                                    <span v-if="result.section" class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">
                                        {{ result.section.name }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span>Marks: {{ result.total_obtained }}/{{ result.total_max_marks }}</span>
                                    <span class="font-semibold" :class="getScoreColorClass(result.percentage)">
                                        {{ result.percentage }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider w-16">
                                        Rank
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Student
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Campus
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Class
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Section
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Total Marks
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Obtained
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Percentage
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Grade
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                <tr 
                                    v-for="(result, index) in results" 
                                    :key="result.id" 
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                    :class="{ 'bg-yellow-50 dark:bg-yellow-900/10': isTopper(index) }"
                                >
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center justify-center w-7 h-7 rounded-full text-sm font-bold"
                                            :class="getRankClass(index)"
                                        >
                                            {{ (pagination.from || 0) + index + 1 }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ result.student }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ result.campus?.name || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ result.class?.name || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ result.section?.name || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ result.total_max_marks }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ result.total_obtained }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <div class="text-sm font-semibold" :class="getScoreColorClass(result.percentage)">
                                            {{ result.percentage }}%
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span 
                                            class="px-2 py-1 rounded text-xs font-medium"
                                            :class="getGradeClass(result.overallGradeItem?.grade_letter)"
                                        >
                                            {{ result.overallGradeItem?.grade_letter || 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span 
                                            class="px-2 py-1 rounded text-xs font-medium"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': result.status === 'draft',
                                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': result.status === 'published',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400': result.status === 'locked',
                                            }"
                                        >
                                            {{ result.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.last_page > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-3">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <Button
                                v-for="link in pagination.links"
                                :key="link.label"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                :disabled="!link.url"
                                @click="handlePageChange(link.url)"
                                class="min-w-8"
                            >
                                <span v-html="link.label"></span>
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Empty Results -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                    <Icon icon="inbox" class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Results Found</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        No exam results found for the selected filters. Please try different criteria or select a different exam.
                    </p>
                </div>
            </div>

            <!-- Initial State - Before Search -->
            <div v-else-if="!hasSearched && !loading" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                <Icon icon="file-text" class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Select an Exam</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Choose an exam from the dropdown above and click "Search Results" to view the exam results.
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import Icon from '@/components/Icon.vue'

interface Exam {
    id: number;
    name: string;
}

interface Campus {
    id: number;
    name: string;
}

interface SchoolClass {
    id: number;
    name: string;
}

interface Section {
    id: number;
    name: string;
}

interface PaginationLink {
    url: number | null;
    label: string;
    active: boolean;
}

interface Pagination {
    data: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

interface Props {
    exams: Exam[];
    campuses: Campus[];
    classes: SchoolClass[];
    sections: Section[];
    preSelectedExamId?: number | null;
}

const props = defineProps<Props>();

// Breadcrumb items
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Results', href: '/exams/results' },
]

// Computed properties
const isExamLocked = computed(() => !!props.preSelectedExamId);

const selectedExamName = computed(() => {
    if (!filters.exam_id) return '';
    const exam = props.exams.find(e => e.id === Number(filters.exam_id));
    return exam?.name || '';
});

// State
const loading = ref(false);
const initialLoad = ref(true);
const hasSearched = ref(false);
const error = ref('');

const results = ref<Array<{
    id: number;
    student?: string;
    student_id?: number;
    campus?: { id: number; name: string };
    class?: { id: number; name: string };
    section?: { id: number; name: string };
    total_max_marks: number;
    total_obtained: number;
    percentage: number;
    overallGradeItem?: { grade_letter: string };
    status: string;
    exam_id?: number;
}>>([]);

const toppers = ref<Array<{
    id: number;
    student?: string;
    class?: { name: string };
    section?: { name: string };
    total_max_marks: number;
    total_obtained: number;
    percentage: number;
    overallGradeItem?: { grade_letter: string };
}>>([]);

const pagination = ref<Pagination>({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: null,
    to: null,
    links: [],
});

const filterOptions = reactive({
    campuses: [] as Campus[],
    classes: [] as SchoolClass[],
    sections: [] as Section[],
});

const filters = reactive({
    exam_id: '' as string | number,
    campus_id: 'all',
    class_id: 'all',
    section_id: 'all',
    search: '',
});

// Watch for exam selection changes to load filter options
watch(() => filters.exam_id, async (newExamId) => {
    if (newExamId) {
        await loadFilterOptions(newExamId);
    } else {
        // Reset filter options when no exam is selected
        filterOptions.campuses = props.campuses;
        filterOptions.classes = props.classes;
        filterOptions.sections = props.sections;
    }
});

// Load filter options for the selected exam
const loadFilterOptions = async (examId: string | number) => {
    try {
        const response = await axios.get('/exams/results/filter-options', {
            params: { exam_id: examId }
        });
        filterOptions.campuses = response.data.campuses || [];
        filterOptions.classes = response.data.classes || [];
        filterOptions.sections = response.data.sections || [];
    } catch (err) {
        console.error('Error loading filter options:', err);
        // Fall back to default options
        filterOptions.campuses = props.campuses;
        filterOptions.classes = props.classes;
        filterOptions.sections = props.sections;
    }
};

// Check if index is a topper (first 5)
const isTopper = (index: number) => {
    const globalIndex = (pagination.value.from || 0) + index;
    return globalIndex < 5;
};

// Search results
const searchResults = async (page: number = 1) => {
    if (!filters.exam_id) {
        error.value = 'Please select an exam';
        return;
    }

    loading.value = true;
    error.value = '';
    
    try {
        const response = await axios.get('/exams/results/list', {
            params: {
                exam_id: filters.exam_id,
                campus_id: filters.campus_id === 'all' ? null : filters.campus_id,
                class_id: filters.class_id === 'all' ? null : filters.class_id,
                section_id: filters.section_id === 'all' ? null : filters.section_id,
                search: filters.search || null,
                page: page,
                per_page: 15,
            }
        });

        if (response.data.success) {
            results.value = response.data.data || [];
            toppers.value = response.data.toppers || [];
            pagination.value = response.data.pagination || {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
                from: null,
                to: null,
                links: [],
            };
            hasSearched.value = true;
            
            if (results.value.length === 0) {
                error.value = '';
            }
        } else {
            error.value = response.data.message || 'Failed to load results';
            results.value = [];
            toppers.value = [];
        }
    } catch (err: any) {
        console.error('Error fetching results:', err);
        error.value = err.response?.data?.message || err.response?.data?.error || 'Failed to load results. Please try again.';
        results.value = [];
        toppers.value = [];
        hasSearched.value = true;
    } finally {
        loading.value = false;
        initialLoad.value = false;
    }
};

// Handle page change
const handlePageChange = (page: number | null) => {
    if (page && page !== pagination.value.current_page) {
        searchResults(page);
    }
};

// Reset filters and results
const resetFilters = () => {
    filters.exam_id = props.preSelectedExamId ? String(props.preSelectedExamId) : '';
    filters.campus_id = 'all';
    filters.class_id = 'all';
    filters.section_id = 'all';
    filters.search = '';
    results.value = [];
    toppers.value = [];
    pagination.value = {
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: null,
        to: null,
        links: [],
    };
    hasSearched.value = false;
    error.value = '';
};

// Helper functions for styling
const getTopperBadgeClass = (index: number) => {
    const classes = [
        'bg-yellow-500',
        'bg-gray-400',
        'bg-orange-500',
        'bg-gray-500',
        'bg-gray-600',
    ];
    return classes[index] || 'bg-gray-400';
};

const getRankClass = (index: number) => {
    const globalIndex = (pagination.value.from || 0) + index;
    if (globalIndex === 0) return 'bg-yellow-500 text-white';
    if (globalIndex === 1) return 'bg-gray-400 text-white';
    if (globalIndex === 2) return 'bg-orange-500 text-white';
    if (globalIndex < 5) return 'bg-gray-500 text-white';
    return 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
};

const getScoreColorClass = (percentage: number) => {
    if (percentage >= 90) return 'text-green-600 dark:text-green-400';
    if (percentage >= 80) return 'text-blue-600 dark:text-blue-400';
    if (percentage >= 70) return 'text-yellow-600 dark:text-yellow-400';
    if (percentage >= 60) return 'text-orange-600 dark:text-orange-400';
    return 'text-red-600 dark:text-red-400';
};

const getGradeClass = (grade: string | undefined) => {
    if (!grade) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    
    const gradeUpper = grade.toUpperCase();
    if (gradeUpper === 'A+' || gradeUpper === 'A') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
    if (gradeUpper === 'B+' || gradeUpper === 'B') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
    if (gradeUpper === 'C+' || gradeUpper === 'C') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    if (gradeUpper === 'D') return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';
    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
};

// Initialize on mount
onMounted(async () => {
    // Initialize filter options with all available data
    filterOptions.campuses = props.campuses;
    filterOptions.classes = props.classes;
    filterOptions.sections = props.sections;

    // If exam is pre-selected from URL, auto-select and search
    if (props.preSelectedExamId) {
        filters.exam_id = String(props.preSelectedExamId);
        await loadFilterOptions(props.preSelectedExamId);
        await searchResults();
    }
    initialLoad.value = false;
});
</script>
