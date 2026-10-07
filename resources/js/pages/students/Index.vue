<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Students" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Students
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Manage student admissions and records
                    </p>
                </div>
                <Button @click="router.visit(route('students.create'))">
                    <Icon icon="plus" class="mr-1" />
                    New Admission
                </Button>
            </div>

            <!-- Filters -->
            <FilterCard>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-3" role="search" aria-label="Student filters">
                    <div>
                        <Label for="filter-campus" class="sr-only">Filter by Campus</Label>
                        <select
                            id="filter-campus"
                            v-model="cascadeCampusId"
                            @change="applyFilters"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
                        >
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <Label for="filter-class" class="sr-only">Filter by Class</Label>
                        <select
                            id="filter-class"
                            v-model="cascadeClassId"
                            @change="applyFilters"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
                        >
                            <option value="">All Classes</option>
                            <option v-for="cls in availableClasses" :key="cls.id" :value="cls.id">
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <Label for="filter-section" class="sr-only">Filter by Section</Label>
                        <select
                            id="filter-section"
                            v-model="cascadeSectionId"
                            @change="applyFilters"
                            :disabled="!cascadeClassId"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <option value="">All Sections</option>
                            <option v-for="section in availableSections" :key="section.id" :value="section.id">
                                {{ section.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <Label for="filter-gender" class="sr-only">Filter by Gender</Label>
                        <select
                            id="filter-gender"
                            v-model="filters.gender_id"
                            @change="applyFilters"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
                        >
                            <option value="">All Genders</option>
                            <option v-for="gender in props.genders" :key="gender.id" :value="gender.id">
                                {{ gender.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <Label for="filter-status" class="sr-only">Filter by Status</Label>
                        <select
                            id="filter-status"
                            v-model="filters.status"
                            @change="applyFilters"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
                        >
                            <option value="">All Status</option>
                            <option v-for="status in props.statuses" :key="status.id" :value="status.id">
                                {{ status.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 relative">
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
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        aria-label="Clear search"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex flex-wrap gap-2 md:gap-3 mt-3">
                    <Button
                        variant="outline"
                        :disabled="!cascadeClassId"
                        :title="!cascadeClassId ? 'Select a class to enable bulk ID card generation' : undefined"
                        @click="printBulkIdCards"
                    >
                        <Icon icon="id-card" class="mr-1" />
                        Bulk ID Cards
                    </Button>
                    <Button
                        v-if="selectedStudents.length > 0"
                        variant="outline"
                        @click="printSelectedIdCards"
                    >
                        <Icon icon="id-card" class="mr-1" />
                        Print Selected ID Cards ({{ selectedStudents.length }})
                    </Button>
                    <Button
                        v-if="selectedStudents.length > 0"
                        variant="outline"
                        @click="exportSelected"
                    >
                        <Icon icon="printer" class="mr-1" />
                        Export Selected ({{ selectedStudents.length }})
                    </Button>
                </div>
            </FilterCard>

            <!-- Mobile Card View -->
            <div class="block lg:hidden space-y-3">
                <div
                    v-for="student in props.tableStudents.data"
                    :key="student.id"
                    class="relative bg-card rounded-lg border border-border p-4 space-y-2"
                >
                    <input
                        type="checkbox"
                        :checked="isSelected(student.id)"
                        @change="toggleStudent(student.id)"
                        class="absolute top-3 right-3 w-4 h-4 rounded"
                        aria-label="Select student"
                    />
                    <div class="flex flex-wrap gap-2 justify-between items-start pr-6">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                <span class="text-primary font-medium">{{ student.serial }}</span>
                            </div>
                            <div>
                                <div class="font-medium text-foreground">{{ student.user?.name || 'N/A' }}</div>
                                <div class="text-xs text-muted-foreground">{{ student.registration_no }}</div>
                            </div>
                        </div>
                        <StatusToggle
                            :active="student.student_status?.name === 'Active'"
                            :active-label="student.student_status?.name || 'Unknown'"
                            :inactive-label="student.student_status?.name || 'Unknown'"
                            class="shrink-0"
                            @toggle="openStatusModal(student)"
                        />
                    </div>
                    <div class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
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
                    <!-- Card view keeps labelled buttons: on a phone there is
                         no hover to reveal an icon's meaning. -->
                    <div class="flex gap-2 pt-2">
                        <Button variant="outline" size="sm" @click="router.visit(route('students.show', student.id))" class="flex-1">
                            <Icon icon="eye" class="mr-1" />View
                        </Button>
                        <Button variant="outline" size="sm" @click="router.visit(route('students.edit', student.id))" class="flex-1">
                            <Icon icon="pencil" class="mr-1" />Edit
                        </Button>
                        <Button variant="outline" size="sm" @click="printStudent(student)" class="flex-1">
                            <Icon icon="printer" class="mr-1" />Print
                        </Button>
                    </div>
                </div>
                <div v-if="props.tableStudents.data.length === 0" class="text-center py-8 text-muted-foreground">
                    No students found.
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th scope="col" class="px-2 py-3 text-center text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    <input
                                        type="checkbox"
                                        :checked="selectAll"
                                        @change="toggleSelectAll"
                                        class="w-4 h-4 rounded"
                                    />
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase w-16">
                                    #
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Student
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Admission No
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Campus / Class / Section
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Gender
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Guardians
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Status
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="student in props.tableStudents.data" :key="student.id" class="transition-colors hover:bg-accent">
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected(student.id)"
                                        @change="toggleStudent(student.id)"
                                        class="w-4 h-4 rounded"
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">
                                    {{ student.serial }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                            <span class="text-primary font-medium">{{ student.user?.name?.charAt(0) || 'S' }}</span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-foreground">{{ student.user?.name || 'N/A' }}</div>
                                            <div class="text-xs text-muted-foreground">{{ student.registration_no }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-muted-foreground">{{ student.admission_no }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-muted-foreground">{{ getEnrollment(student).campus?.name || 'N/A' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ getEnrollment(student).class?.name || 'N/A' }} - {{ getEnrollment(student).section?.name || 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-muted-foreground">{{ student.gender?.name || '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div v-if="getPrimaryGuardian(student)" class="text-sm text-muted-foreground">
                                        {{ getPrimaryGuardian(student).guardian?.user?.name || getPrimaryGuardian(student).relation?.name || 'Guardian' }}
                                    </div>
                                    <div v-if="getPrimaryGuardian(student)?.guardian?.phone" class="text-xs text-muted-foreground">
                                        {{ getPrimaryGuardian(student).guardian.phone }}
                                    </div>
                                    <span v-if="!getPrimaryGuardian(student)" class="text-xs text-muted-foreground">No guardians</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <StatusToggle
                                        :active="student.student_status?.name === 'Active'"
                                        :active-label="student.student_status?.name || 'Unknown'"
                                        :inactive-label="student.student_status?.name || 'Unknown'"
                                        @toggle="openStatusModal(student)"
                                    />
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <RowActions>
                                        <RowAction kind="view" :href="route('students.show', student.id)" />
                                        <RowAction kind="edit" :href="route('students.edit', student.id)" />
                                        <RowAction kind="print" @click="printStudent(student)" />
                                        <!--
                                            The card the child carries, and the
                                            certificate they cannot be admitted
                                            anywhere else without. Both print
                                            from records that already existed;
                                            nothing had ever printed them.
                                        -->
                                        <RowAction
                                            kind="custom"
                                            icon="IdCard"
                                            label="ID card"
                                            @click="printIdCard(student)"
                                        />
                                        <RowAction
                                            kind="custom"
                                            icon="FileCheck"
                                            label="Leaving certificate"
                                            @click="printLeavingCertificate(student)"
                                        />
                                    </RowActions>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <TablePagination
                :pagination="props.tableStudents"
                :show-per-page-selector="false"
                use-links
            />
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
import { computed, reactive, ref, watch } from 'vue';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import FilterCard from '@/components/FilterCard.vue';
import { useCascadingAcademicSelect } from '@/composables/useCascadingAcademicSelect';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import RowAction from '@/components/tables/RowAction.vue';
import RowActions from '@/components/tables/RowActions.vue';
import TablePagination from '@/components/tables/TablePagination.vue';
import StatusToggle from '@/components/tables/StatusToggle.vue';
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
        class_id: number;
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
    gender_id: props.filters?.gender_id || '',
    status: props.filters?.status || '',
    search: props.filters?.search || '',
});

// Campus → Class → Section cascade (#47/#64/#82/#99/#100): narrows the
// Section options to the selected Class's sections, and clears a lower
// filter whenever the one above it changes, matching the pattern already
// used on other pages (e.g. Fee/Vouchers/Generate.vue, students/Create.vue).
// The cascade refs below are bound directly to the Campus/Class/Section
// filter selects and are the source of truth for `filters.*`.
const {
    selectedCampusId: cascadeCampusId,
    selectedClassId: cascadeClassId,
    selectedSectionId: cascadeSectionId,
    availableClasses,
    availableSections,
} = useCascadingAcademicSelect({
    campuses: computed(() => props.campuses),
    classes: computed(() => props.classes),
    sections: computed(() => props.sections),
    sessions: computed(() => []),
    initialCampusId: props.filters?.campus_id,
    initialClassId: props.filters?.class_id,
    initialSectionId: props.filters?.section_id,
    sessionStorageKey: 'students-index-filters',
});

const buildQueryString = () => {
    const params = new URLSearchParams();
    if (cascadeCampusId.value) params.append('campus_id', String(cascadeCampusId.value));
    if (cascadeClassId.value) params.append('class_id', String(cascadeClassId.value));
    if (cascadeSectionId.value) params.append('section_id', String(cascadeSectionId.value));
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

// Row selection (checkbox pattern from Fee/Vouchers/Index.vue)
const selectedStudents = ref<number[]>([]);
const selectAll = ref(false);

// Toggle select all
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedStudents.value = props.tableStudents.data.map((student) => student.id);
    } else {
        selectedStudents.value = [];
    }
};

// Toggle single student
const toggleStudent = (studentId: number) => {
    const index = selectedStudents.value.indexOf(studentId);
    if (index === -1) {
        selectedStudents.value.push(studentId);
    } else {
        selectedStudents.value.splice(index, 1);
    }

    selectAll.value = props.tableStudents.data.length > 0 && selectedStudents.value.length === props.tableStudents.data.length;
};

// Check if student is selected
const isSelected = (studentId: number) => {
    return selectedStudents.value.includes(studentId);
};

// Reset selection whenever the visible page of students changes (filters, pagination, reload)
watch(() => props.tableStudents.data, () => {
    selectedStudents.value = [];
    selectAll.value = false;
}, { deep: false });

/** ID cards for exactly the children checked on this page, ignoring filters. */
const printSelectedIdCards = () => {
    if (selectedStudents.value.length === 0) return;

    const params = new URLSearchParams();
    selectedStudents.value.forEach((id) => params.append('student_ids[]', String(id)));

    window.open(`${route('students.id-cards')}?${params.toString()}`, '_blank');
};

/** The roll, exported as CSV, scoped to exactly the children checked. */
const exportSelected = () => {
    if (selectedStudents.value.length === 0) return;

    const params = new URLSearchParams();
    selectedStudents.value.forEach((id) => params.append('ids[]', String(id)));

    window.open(`${route('students.export')}?${params.toString()}`, '_blank');
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

// Print student admission form
const printStudent = (student: { id: number; registration_no: string }) => {
    window.open(route('students.print', student.id), '_blank');
};

/** The child's ID card. The photograph has been stored since admission. */
const printIdCard = (student: { id: number }) => {
    window.open(`${route('students.id-cards')}?student_ids[]=${student.id}`, '_blank');
};

/**
 * ID cards for a whole class (and section, if one is also selected) at once —
 * the same filters already on this page, sent straight to the print view.
 */
const printBulkIdCards = () => {
    if (!cascadeClassId.value) return;

    const params = new URLSearchParams();
    params.append('class_id', String(cascadeClassId.value));
    if (cascadeSectionId.value) params.append('section_id', String(cascadeSectionId.value));

    window.open(`${route('students.id-cards')}?${params.toString()}`, '_blank');
};

/**
 * The School Leaving Certificate.
 *
 * Refused, plainly, for a child who has not been marked as having left — it
 * reads the leaving record rather than inventing one.
 */
const printLeavingCertificate = (student: { id: number }) => {
    window.open(route('students.leaving-certificate', student.id), '_blank');
};

// Get primary guardian or first guardian if no primary
const getPrimaryGuardian = (student: any) => {
    const guardians = student.student_guardians || [];
    if (!guardians.length) return null;
    // Return primary guardian if exists
    return guardians.find((g: any) => g.pivot?.is_primary) || guardians[0];
};
</script>
