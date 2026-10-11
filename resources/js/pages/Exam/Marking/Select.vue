<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Select for Marking" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">
                    Marking - Select Options
                </h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Choose exam, campus, class and section to enter marks
                </p>
            </div>

            <!-- Pre-selected exam notice -->
            <div v-if="isExamPreSelected" class="p-3 bg-primary/10 border border-primary/40 rounded-lg">
                <p class="text-sm text-primary">
                    <Icon icon="info" class="inline h-4 w-4 mr-1" />
                    You are entering marks for a specific exam. The exam selection is locked.
                </p>
            </div>

            <div class="bg-card rounded-lg border border-border p-6">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-2">
                        <Label for="exam">Exam</Label>
                        <SearchableSelect
                            id="exam"
                            v-model="selected.examId"
                            :options="examOptions"
                            :disabled="isExamPreSelected"
                            placeholder="Select Exam"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="campus">Campus</Label>
                        <SearchableSelect
                            id="campus"
                            v-model="selected.campusId"
                            :options="campusOptions"
                            placeholder="All Campuses"
                            clearable
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="class">Class</Label>
                        <SearchableSelect
                            id="class"
                            v-model="selected.classId"
                            :options="classOptions"
                            placeholder="Select Class"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="section">Section</Label>
                        <SearchableSelect
                            id="section"
                            v-model="selected.sectionId"
                            :options="sectionOptions"
                            :disabled="!selected.classId"
                            placeholder="All Sections"
                        />
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
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
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

const examOptions = computed(() => props.exams.map((exam) => ({ value: exam.id, label: exam.name })));
const campusOptions = computed(() => props.campuses.map((campus) => ({ value: campus.id, label: campus.name })));
const classOptions = computed(() => props.classes.map((cls) => ({ value: cls.id, label: cls.name })));
const sectionOptions = computed(() => [
    { value: 'ALL', label: 'All Sections' },
    ...filteredSections.value.map((section) => ({ value: section.id, label: section.name })),
]);

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
