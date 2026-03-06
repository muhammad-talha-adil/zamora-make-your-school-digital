<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Students" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Students
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Manage student admissions and records
                    </p>
                </div>
                <Button @click="router.visit(route('students.create'))">
                    <Icon icon="plus" class="mr-1" />
                    New Admission
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap" role="search" aria-label="Student filters">
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
                        @change="applyFilters"
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
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Sections</option>
                        <option v-for="section in props.sections" :key="section.id" :value="section.id">
                            {{ section.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-40">
                    <Label for="filter-gender" class="sr-only">Filter by Gender</Label>
                    <select
                        id="filter-gender"
                        v-model="filters.gender_id"
                        @change="applyFilters"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Genders</option>
                        <option v-for="gender in props.genders" :key="gender.id" :value="gender.id">
                            {{ gender.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-40">
                    <Label for="filter-status" class="sr-only">Filter by Status</Label>
                    <select
                        id="filter-status"
                        v-model="filters.status"
                        @change="applyFilters"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Status</option>
                        <option v-for="status in props.statuses" :key="status.id" :value="status.id">
                            {{ status.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-64 relative">
                    <Label for="search-students" class="sr-only">Search students</Label>
                    <Input
                        id="search-students"
                        v-model="filters.search"
                        type="text"
                        placeholder="Search by name, reg no, admission no..."
                        @input="handleSearch"
                        @keydown.enter.prevent="applyFilters"
                        class="w-full pr-8"
                        aria-label="Search students by name, registration number, or admission number"
                    />
                    <button
                        v-if="filters.search"
                        @click="clearSearch"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        aria-label="Clear search"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="block lg:hidden space-y-3">
                <div
                    v-for="student in props.tableStudents.data"
                    :key="student.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                <span class="text-blue-600 dark:text-blue-400 font-medium">{{ student.serial }}</span>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ student.user?.name || 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ student.registration_no }}</div>
                            </div>
                        </div>
                        <span
                            :class="[
                                'px-2 py-1 text-xs font-medium rounded-full shrink-0',
                                student.student_status?.name === 'Active'
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            ]"
                        >
                            {{ student.student_status?.name || 'Unknown' }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <Icon icon="building" class="h-4 w-4" />
                            <span>{{ getEnrollment(student).campus?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Icon icon="book" class="h-4 w-4" />
                            <span>{{ getEnrollment(student).class?.name || 'N/A' }} - {{ getEnrollment(student).section?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Icon icon="calendar" class="h-4 w-4" />
                            <span>{{ getEnrollment(student).session?.name || 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <Button variant="outline" size="sm" @click="router.visit(route('students.show', student.id))" class="flex-1">
                            <Icon icon="eye" class="mr-1" />View
                        </Button>
                        <Button variant="outline" size="sm" @click="router.visit(route('students.edit', student.id))" class="flex-1">
                            <Icon icon="edit" class="mr-1" />Edit
                        </Button>
                    </div>
                </div>
                <div v-if="props.tableStudents.data.length === 0" class="text-center py-8 text-gray-500">
                    No students found.
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300 w-16">
                                    #
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Student
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Admission No
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Campus / Class / Section
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Gender
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Guardians
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
                            <tr v-for="student in props.tableStudents.data" :key="student.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ student.serial }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                            <span class="text-blue-600 dark:text-blue-400 font-medium">{{ student.user?.name?.charAt(0) || 'S' }}</span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ student.user?.name || 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">{{ student.registration_no }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ student.admission_no }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ getEnrollment(student).campus?.name || 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ getEnrollment(student).class?.name || 'N/A' }} - {{ getEnrollment(student).section?.name || 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ student.gender?.name || '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div v-if="getPrimaryGuardian(student)" class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ getPrimaryGuardian(student).guardian?.user?.name || getPrimaryGuardian(student).relation?.name || 'Guardian' }}
                                    </div>
                                    <div v-if="getPrimaryGuardian(student)?.guardian?.phone" class="text-xs text-gray-500">
                                        {{ getPrimaryGuardian(student).guardian.phone }}
                                    </div>
                                    <span v-if="!getPrimaryGuardian(student)" class="text-xs text-gray-400">No guardians</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            student.student_status?.name === 'Active'
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                                : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                        ]"
                                    >
                                        {{ student.student_status?.name || 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        <Button variant="outline" size="sm" @click="router.visit(route('students.show', student.id))" class="min-h-8">
                                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                        </Button>
                                        <Button variant="outline" size="sm" @click="router.visit(route('students.edit', student.id))" class="min-h-8">
                                            <Icon icon="edit" class="mr-1 h-3 w-3" />Edit
                                        </Button>
                                        <Button variant="outline" size="sm" @click="openStatusModal(student)" class="min-h-8 text-red-600 hover:text-red-700">
                                            <Icon icon="trash-2" class="mr-1 h-3 w-3" />Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="props.tableStudents.links" class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="text-xs md:text-sm text-gray-600">
                    Showing {{ props.tableStudents.from }} to {{ props.tableStudents.to }} of {{ props.tableStudents.total }} entries
                </div>
                <div class="flex flex-wrap gap-1">
                    <Link
                        v-for="link in props.tableStudents.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-2 text-sm rounded-md transition-colors min-h-10 flex items-center justify-center',
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'
                        ]"
                        preserve-state
                    >
                        <span v-html="link.label"></span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Student Status Change Modal -->
        <StudentStatusModal
            :is-open="isStatusModalOpen"
            :student="selectedStudent"
            :statuses="props.statuses"
            @update:open="isStatusModalOpen = $event"
            @closed="handleStatusModalClosed"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import StudentStatusModal from '@/components/modals/StudentStatusModal.vue';

interface Props {
    tableStudents: {
        data: Array<{
            id: number;
            serial: number;
            user_id?: number;
            registration_no: string;
            admission_no: string;
            user?: {
                name: string;
            };
            currentEnrollment?: {
                campus?: {
                    name: string;
                };
                class?: {
                    name: string;
                };
                section?: {
                    name: string;
                };
                session?: {
                    name: string;
                };
            };
            gender?: {
                name: string;
            };
            student_status?: {
                name: string;
            };
            student_guardians?: Array<{
                id: number;
                pivot?: {
                    id: number;
                    relation_id: number;
                    is_primary: boolean;
                };
                guardian?: {
                    user?: {
                        name: string;
                    };
                    phone?: string;
                };
                relation?: {
                    name: string;
                };
            }>;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
    };
    campuses: Array<{
        id: number;
        name: string;
    }>;
    classes: Array<{
        id: number;
        name: string;
    }>;
    sections: Array<{
        id: number;
        name: string;
    }>;
    genders: Array<{
        id: number;
        name: string;
    }>;
    statuses: Array<{
        id: number;
        name: string;
    }>;
    filters?: {
        campus_id?: string;
        class_id?: string;
        section_id?: string;
        gender_id?: string;
        status?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Students',
        href: '/students',
    },
    {
        title: 'Student List',
        href: '/students',
    },
];

const filters = reactive({
    campus_id: props.filters?.campus_id || '',
    class_id: props.filters?.class_id || '',
    section_id: props.filters?.section_id || '',
    gender_id: props.filters?.gender_id || '',
    status: props.filters?.status || '',
    search: props.filters?.search || '',
});

const buildQueryString = () => {
    const params = new URLSearchParams();
    if (filters.campus_id) params.append('campus_id', filters.campus_id);
    if (filters.class_id) params.append('class_id', filters.class_id);
    if (filters.section_id) params.append('section_id', filters.section_id);
    if (filters.gender_id) params.append('gender_id', filters.gender_id);
    if (filters.status) params.append('status', filters.status);
    if (filters.search) params.append('search', filters.search);
    return params.toString();
};

const applyFilters = () => {
    router.visit(route('students.index') + `?${buildQueryString()}`, {
        preserveState: true,
    });
};

// Create debounced search function
const debouncedSearch = debounce(() => {
    router.visit(route('students.index') + `?${buildQueryString()}`, {
        preserveState: true,
    });
}, 300);

// Handler for search input
const handleSearch = () => {
    debouncedSearch();
};

// Clear search and reset
const clearSearch = () => {
    filters.search = '';
    applyFilters();
};

// Modal state
const isStatusModalOpen = ref(false);
const selectedStudent = ref<{ id: number; registration_no: string; user_id?: number } | null>(null);

const openStatusModal = (student: { id: number; registration_no: string; user_id?: number }) => {
    selectedStudent.value = student;
    isStatusModalOpen.value = true;
};

const handleStatusModalClosed = () => {
    selectedStudent.value = null;
    isStatusModalOpen.value = false;
    router.reload();
};

// Safe accessor for student enrollment with fallback to empty object
const getEnrollment = (student: any) => {
    return student.current_enrollment || {};
};

// Get primary guardian or first guardian if no primary
const getPrimaryGuardian = (student: any) => {
    const guardians = student.student_guardians || [];
    if (!guardians.length) return null;
    // Return primary guardian if exists
    return guardians.find((g: any) => g.pivot?.is_primary) || guardians[0];
};
</script>
