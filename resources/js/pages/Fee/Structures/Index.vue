<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';

interface FeeStructure {
    id: number;
    title: string;
    session: { id: number; name: string };
    campus: { id: number; name: string };
    class?: { id: number; name: string };
    section?: { id: number; name: string };
    status: string;
    effective_from: string;
    effective_to?: string;
    items_count: number;
}

interface Props {
    structures: FeeStructure[];
    sessions: Array<{ id: number; name: string }>;
    campuses: Array<{ id: number; name: string }>;
    classes: Array<{ id: number; name: string }>;
    sections: Array<{ id: number; name: string; class_id: number }>;
    filters?: {
        session_id?: string;
        campus_id?: string;
        class_id?: string;
        section_id?: string;
        status?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const structuresData = ref<FeeStructure[]>(props.structures);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Fee Structures', href: '/fee/structures' },
];

const filters = reactive({
    session_id: props.filters?.session_id || '',
    campus_id: props.filters?.campus_id || '',
    class_id: props.filters?.class_id || '',
    section_id: props.filters?.section_id || '',
    status: props.filters?.status || '',
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

const applyFilters = () => {
    const params = new URLSearchParams();
    if (filters.session_id) params.append('session_id', filters.session_id);
    if (filters.campus_id) params.append('campus_id', filters.campus_id);
    if (filters.class_id) params.append('class_id', filters.class_id);
    if (filters.section_id) params.append('section_id', filters.section_id);
    if (filters.status) params.append('status', filters.status);
    if (filters.search) params.append('search', filters.search);
    
    const queryString = params.toString();
    const newUrl = route('fee.structures.index') + (queryString ? `?${queryString}` : '');
    window.history.pushState({}, '', newUrl);
    fetchStructures();
};

const fetchStructures = () => {
    const params = new URLSearchParams();
    if (filters.session_id) params.append('session_id', filters.session_id);
    if (filters.campus_id) params.append('campus_id', filters.campus_id);
    if (filters.class_id) params.append('class_id', filters.class_id);
    if (filters.section_id) params.append('section_id', filters.section_id);
    if (filters.status) params.append('status', filters.status);
    if (filters.search) params.append('search', filters.search);

    axios.get(route('fee.structures.index') + `?${params.toString()}`).then((response) => {
        structuresData.value = response.data.data || response.data;
    });
};

const getStatusColor = (status: string) => {
    const colors = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        draft: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return colors[status] || colors.inactive;
};

// Filter sections based on selected class
const filteredSections = computed(() => {
    if (!filters.class_id) return [];
    return props.sections.filter(s => s.class_id === Number(filters.class_id));
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Structures" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Fee Structures
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Manage fee structures for different classes and sessions
                    </p>
                </div>
                <Button @click="router.visit(route('fee.structures.create'))">
                    <Icon icon="plus" class="mr-2 h-4 w-4" />
                    Create Structure
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap">
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
                    <Label for="filter-campus" class="sr-only">Filter by Campus</Label>
                    <select
                        id="filter-campus"
                        v-model="filters.campus_id"
                        @change="applyFilters"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Campuses</option>
                        <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                            {{ campus.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-class" class="sr-only">Filter by Class</Label>
                    <select
                        id="filter-class"
                        v-model="filters.class_id"
                        @change="filters.section_id = ''; applyFilters();"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Classes</option>
                        <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                            {{ cls.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-section" class="sr-only">Filter by Section</Label>
                    <select
                        id="filter-section"
                        v-model="filters.section_id"
                        @change="applyFilters"
                        :disabled="!filters.class_id"
                        :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11', { 'opacity-50': !filters.class_id }]"
                    >
                        <option value="">All Sections</option>
                        <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                            {{ section.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-status" class="sr-only">Filter by Status</Label>
                    <select
                        id="filter-status"
                        v-model="filters.status"
                        @change="applyFilters"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="search-structure" class="sr-only">Search</Label>
                    <Input
                        id="search-structure"
                        v-model="filters.search"
                        @input="onSearchInput"
                        placeholder="Search structures..."
                        class="min-h-10 md:min-h-11"
                    />
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="block lg:hidden space-y-3">
                <div
                    v-for="structure in structuresData"
                    :key="structure.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ structure.title }}</div>
                            <div class="text-xs text-gray-500">{{ structure.session.name }}</div>
                        </div>
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(structure.status)]">
                            {{ structure.status }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div>Campus: {{ structure.campus.name }}</div>
                        <div v-if="structure.class">Class: {{ structure.class.name }}<span v-if="structure.section"> / {{ structure.section.name }}</span><span v-else> / All Sections</span></div>
                        <div v-else>All Classes / All Sections</div>
                        <div>Items: {{ structure.items_count }}</div>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <Button variant="outline" size="sm" @click="router.visit(route('fee.structures.show', structure.id))">
                            <Icon icon="eye" class="mr-1" />View
                        </Button>
                        <Button variant="outline" size="sm" @click="router.visit(route('fee.structures.edit', structure.id))">
                            <Icon icon="edit" class="mr-1" />Edit
                        </Button>
                    </div>
                </div>
                <div v-if="structuresData.length === 0" class="text-center py-8 text-gray-500">
                    No fee structures found.
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Sr#
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Title
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Session
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Campus
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Class / Section
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Items
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(structure, index) in structuresData" :key="structure.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ index + 1 }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ structure.title }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ structure.session.name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ structure.campus.name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ structure.class?.name || 'All Classes' }}
                                        <span v-if="structure.section"> / {{ structure.section.name }}</span>
                                        <span v-else-if="structure.class"> / All Sections</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(structure.status)]">
                                        {{ structure.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ structure.items_count }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex gap-2 justify-end">
                                        <Button variant="outline" size="sm" @click="router.visit(route('fee.structures.show', structure.id))">
                                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                        </Button>
                                        <Button variant="outline" size="sm" @click="router.visit(route('fee.structures.edit', structure.id))">
                                            <Icon icon="edit" class="mr-1 h-3 w-3" />Edit
                                        </Button>
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
