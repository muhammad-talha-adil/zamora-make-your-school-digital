<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';
import { tableActionButtonClass } from '@/utils/table-actions';

interface FeeStructure {
    id: number;
    title: string;
    session: { id: number; name: string } | null;
    campus: { id: number; name: string } | null;
    class?: { id: number; name: string } | null;
    section?: { id: number; name: string } | null;
    status: string;
    effective_from: string;
    effective_to?: string;
    items_count: number;
    is_default?: boolean;
}

interface Props {
    structures: {
        data: FeeStructure[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
    };
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

const structuresData = ref<FeeStructure[]>(props.structures?.data || []);
const pagination = ref(props.structures || { data: [], links: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1, per_page: 10 });
const perPage = ref(props.structures?.per_page || 10);
const isLoading = ref(false);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Fee Structures', href: '/fee/structures' },
];

const filterSession = ref(props.filters?.session_id || '');
const filterCampus = ref(props.filters?.campus_id || '');
const filterClass = ref(props.filters?.class_id || '');
const filterSection = ref(props.filters?.section_id || '');
const filterStatus = ref(props.filters?.status || '');
const searchQuery = ref(props.filters?.search || '');

const perPageOptions = [
    { id: 10, name: '10' },
    { id: 25, name: '25' },
    { id: 50, name: '50' },
    { id: 100, name: '100' },
];

const filteredSections = computed(() => {
    if (!filterClass.value) {
        return [];
    }

    return props.sections.filter((section) => section.class_id === Number(filterClass.value));
});

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const fetchStructures = (page = 1) => {
    const params: Record<string, string | number> = {
        per_page: perPage.value,
        page: page,
    };

    if (filterSession.value) params.session_id = filterSession.value;
    if (filterCampus.value) params.campus_id = filterCampus.value;
    if (filterClass.value) params.class_id = filterClass.value;
    if (filterSection.value) params.section_id = filterSection.value;
    if (filterStatus.value) params.status = filterStatus.value;
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();

    isLoading.value = true;

    axios.get(route('fee.structures.index'), {
        params,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    }).then((response) => {
        structuresData.value = response.data?.data || [];
        pagination.value = response.data || { data: [], links: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1, per_page: 10 };
    }).catch((error) => {
        console.error('Failed to fetch fee structures:', error);
        alert.error('Failed to load fee structures. Please try again.');
    }).finally(() => {
        isLoading.value = false;
    });
};

watch([filterSession, filterCampus, filterStatus], () => {
    fetchStructures(1);
});

watch(filterClass, () => {
    filterSection.value = '';
    fetchStructures(1);
});

watch(filterSection, () => {
    fetchStructures(1);
});

watch(searchQuery, () => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = window.setTimeout(() => {
        fetchStructures(1);
    }, 300);
});

watch(perPage, () => {
    fetchStructures(1);
});

// Watch for props changes (e.g., after create/update/delete)
watch(() => props.structures, (newStructures) => {
    structuresData.value = newStructures?.data || [];
    pagination.value = newStructures || { data: [], links: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1, per_page: 10 };
}, { deep: true });

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        draft: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    };

    return colors[status] || colors.inactive;
};

const toggleStatus = (structure: FeeStructure) => {
    const actionText = structure.status === 'active' ? 'deactivate' : 'activate';
    const confirmButtonText = structure.status === 'active' ? 'Yes, deactivate it!' : 'Yes, activate it!';
    
    alert
        .confirm(
            `Are you sure you want to ${actionText} "${structure.title}"?`,
            actionText.charAt(0).toUpperCase() + actionText.slice(1) + ' Fee Structure',
            confirmButtonText,
        )
        .then((result) => {
            if (result.isConfirmed) {
                const actionRoute = structure.status === 'active'
                    ? route('fee.structures.deactivate', structure.id)
                    : route('fee.structures.activate', structure.id);

                axios.patch(actionRoute, {}, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then((response) => {
                    const updatedStructure = response.data.structure;

                    structuresData.value = structuresData.value.map((item) =>
                        item.id === updatedStructure.id ? updatedStructure : item,
                    );
                    
                    alert.success(`Fee structure ${actionText}d successfully!`);
                }).catch(() => {
                    alert.error('Failed to update status. Please try again.');
                });
            }
        });
};

const deleteStructure = (structure: FeeStructure) => {
    alert
        .confirm(
            `Are you sure you want to delete "${structure.title}"?`,
            'Delete Fee Structure',
            'Yes, delete it!',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.delete(route('fee.structures.destroy', structure.id), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then(() => {
                    alert.success('Fee structure deleted successfully!');
                    fetchStructures();
                }).catch(() => {
                    alert.error('Failed to delete fee structure. Please try again.');
                });
            }
        });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Structures" />

        <div class="space-y-6 p-4 md:p-6">
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

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="space-y-2">
                        <Label for="filter-campus">Campus</Label>
                        <select
                            id="filter-campus"
                            v-model="filterCampus"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-session">Session</Label>
                        <select
                            id="filter-session"
                            v-model="filterSession"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                            <option value="">All Sessions</option>
                            <option v-for="session in props.sessions" :key="session.id" :value="String(session.id)">
                                {{ session.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-class">Class</Label>
                        <select
                            id="filter-class"
                            v-model="filterClass"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                            <option value="">All Classes</option>
                            <option v-for="cls in props.classes" :key="cls.id" :value="String(cls.id)">
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-section">Section</Label>
                        <select
                            id="filter-section"
                            v-model="filterSection"
                            :disabled="!filterClass"
                            :class="[
                                'w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white',
                                !filterClass ? 'opacity-50 cursor-not-allowed' : '',
                            ]"
                        >
                            <option value="">All Sections</option>
                            <option v-for="section in filteredSections" :key="section.id" :value="String(section.id)">
                                {{ section.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-status">Status</Label>
                        <select
                            id="filter-status"
                            v-model="filterStatus"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    <div class="space-y-2 md:col-span-5">
                        <Label for="search-structure">Search</Label>
                        <Input
                            id="search-structure"
                            v-model="searchQuery"
                            placeholder="Search structures..."
                        />
                    </div>
                </div>
            </div>

            <div class="block lg:hidden space-y-3">
                <div
                    v-for="(structure, index) in structuresData"
                    :key="structure.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3"
                >
                    <div class="flex justify-between items-start gap-3">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Sr# {{ ((pagination.from || 1) - 1) + index + 1 }}</div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ structure.title }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ structure.session?.name || '-' }}</div>
                        </div>
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(structure.status)]">
                            {{ structure.status }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div>Campus: {{ structure.campus?.name || '-' }}</div>
                        <div>
                            Class:
                            {{ structure.class?.name || 'All Classes' }}
                            <span v-if="structure.section"> / {{ structure.section.name }}</span>
                            <span v-else-if="structure.class"> / All Sections</span>
                        </div>
                        <div>Items: {{ structure.items_count }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :class="structure.status === 'active' ? tableActionButtonClass.deactivate : tableActionButtonClass.activate"
                            @click="toggleStatus(structure)"
                        >
                            {{ structure.status === 'active' ? 'Inactive' : 'Active' }}
                        </Button>
                        <Button variant="outline" size="sm" :class="tableActionButtonClass.view" @click="router.visit(route('fee.structures.show', structure.id))">
                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                        </Button>
                        <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" @click="router.visit(route('fee.structures.edit', structure.id))">
                            <Icon icon="edit" class="mr-1 h-3 w-3" />Edit
                        </Button>
                        <Button variant="outline" size="sm" :class="tableActionButtonClass.delete" @click="deleteStructure(structure)">
                            Delete
                        </Button>
                    </div>
                </div>
                <div v-if="isLoading" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Loading fee structures...
                </div>
                <div v-else-if="structuresData.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No fee structures found.
                </div>
            </div>

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
                            <tr
                                v-for="(structure, index) in structuresData"
                                :key="structure.id"
                                class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ ((pagination.from || 1) - 1) + index + 1 }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ structure.title }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ structure.session?.name || '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ structure.campus?.name || '-' }}</div>
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
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :class="structure.status === 'active' ? tableActionButtonClass.deactivate : tableActionButtonClass.activate"
                                            @click="toggleStatus(structure)"
                                        >
                                            {{ structure.status === 'active' ? 'Inactive' : 'Active' }}
                                        </Button>
                                        <Button variant="outline" size="sm" :class="tableActionButtonClass.view" @click="router.visit(route('fee.structures.show', structure.id))">
                                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                        </Button>
                                        <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" @click="router.visit(route('fee.structures.edit', structure.id))">
                                            <Icon icon="edit" class="mr-1 h-3 w-3" />Edit
                                        </Button>
                                        <Button variant="outline" size="sm" :class="tableActionButtonClass.delete" @click="deleteStructure(structure)">
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="isLoading" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Loading fee structures...
                </div>
                <div v-else-if="structuresData.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No fee structures found.
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center pt-4">
                <div class="flex items-center gap-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                    </div>
                    <select v-model="perPage" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 w-20">
                        <option v-for="option in perPageOptions" :key="option.id" :value="option.id">
                            {{ option.name }}
                        </option>
                    </select>
                </div>
                <div class="flex gap-1">
                    <Button
                        v-for="link in pagination.links"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="link.url ? fetchStructures(parseInt(link.url.match(/page=(\d+)/)?.[1] || '1')) : null"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
