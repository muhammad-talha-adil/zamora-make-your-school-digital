<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Select for Marking" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                    Marking - Select Options
                </h1>
                <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                    Choose exam, campus, class and section to enter marks
                </p>
            </div>

            <!-- Pre-selected exam notice -->
            <div v-if="isExamPreSelected" class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    <Icon icon="info" class="inline h-4 w-4 mr-1" />
                    You are entering marks for a specific exam. The exam selection is locked.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-2">
                        <Label for="exam">Exam</Label>
                        <select
                            id="exam"
                            v-model="selected.examId"
                            :disabled="isExamPreSelected"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            :class="{ 'bg-gray-100 dark:bg-gray-700': isExamPreSelected }"
                        >
                            <option value="">Select Exam</option>
                            <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                                {{ exam.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="campus">Campus</Label>
                        <select
                            id="campus"
                            v-model="selected.campusId"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                        >
                            <option value="">All Campuses</option>
                            <option v-for="campus in campuses" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="class">Class</Label>
                        <select
                            id="class"
                            v-model="selected.classId"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                        >
                            <option value="">Select Class</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="section">Section</Label>
                        <select
                            id="section"
                            v-model="selected.sectionId"
                            :disabled="!selected.classId"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                        >
                            <option value="ALL">All Sections</option>
                            <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                                {{ section.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-start">
                    <Button 
                        @click="loadMarkingGrid" 
                        :disabled="!selected.examId || !selected.classId"
                    >
                        <Icon icon="edit-3" class="mr-1" />
                        Load Marking Grid
                    </Button>
                </div>
            </div>

            <!-- Inline Marking Grid Component -->
            <MarkingGridComponent
                v-if="showGrid"
                :exams="exams"
                :campuses="campuses"
                :classes="classes"
                :sections="sections || []"
                :grade-systems="gradeSystems || []"
                :filters="currentFilters"
                @save-complete="handleSaveComplete"
                @filters-reset="handleFiltersReset"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { reactive, computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import MarkingGridComponent from './MarkingGridComponent.vue';

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
    class_id: number;
}

interface GradeSystem {
    id: number;
    name: string;
    is_default: boolean;
}

interface Props {
    exams: Exam[];
    campuses: Campus[];
    classes: SchoolClass[];
    sections?: Section[];
    gradeSystems?: GradeSystem[];
    preSelectedExamId?: number | null;
}

const props = defineProps<Props>();

// Check if exam is pre-selected from URL (read-only mode)
const isExamPreSelected = computed(() => !!props.preSelectedExamId);

// Show/hide grid
const showGrid = ref(false);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Marking', href: '/exams/marking' },
];

const selected = reactive({
    examId: '' as string | number,
    campusId: '' as string | number,
    classId: '' as string | number,
    sectionId: 'ALL' as string | number,
});

// Current filters for the grid component
const currentFilters = computed(() => ({
    exam_id: selected.examId ? Number(selected.examId) : null,
    campus_id: selected.campusId ? Number(selected.campusId) : null,
    class_id: selected.classId ? Number(selected.classId) : null,
    section_id: selected.sectionId && selected.sectionId !== 'ALL' ? Number(selected.sectionId) : null,
    grade_system_id: props.gradeSystems?.find(gs => gs.is_default)?.id ?? null,
}));

// Auto-select exam if pre-selected from URL
watch(() => props.preSelectedExamId, (newVal) => {
    if (newVal) {
        selected.examId = String(newVal);
    }
}, { immediate: true });

const filteredSections = computed(() => {
    if (!selected.classId || !props.sections) return [];
    return props.sections.filter((s: Section) => s.class_id === Number(selected.classId));
});

const loadMarkingGrid = () => {
    if (selected.examId && selected.classId) {
        showGrid.value = true;
    }
};

const handleSaveComplete = () => {
    // Optionally handle save completion
    // For example, we could reload data or show a message
};

const handleFiltersReset = () => {
    // Reset filters to default values after save
    selected.campusId = '';
    selected.classId = '';
    selected.sectionId = 'ALL';
    showGrid.value = false;
};
</script>
