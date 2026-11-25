<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Exam Papers / Date Sheet" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Exam Papers / Date Sheet
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        View and manage papers schedule for exams
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <FilterCard class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap">
                <div class="w-full sm:w-56 md:w-64">
                    <Label for="filter-exam" class="sr-only">Filter by Exam</Label>
                    <SearchableSelect
                        id="filter-exam"
                        v-model="filters.exam_id"
                        :options="examOptions"
                        placeholder="All Exams"
                        clearable
                    />
                </div>
                <div class="w-full sm:w-56 md:w-64">
                    <Label for="filter-class" class="sr-only">Filter by Class</Label>
                    <SearchableSelect
                        id="filter-class"
                        v-model="filters.class_id"
                        :options="classOptions"
                        placeholder="All Classes"
                        clearable
                    />
                </div>
            </FilterCard>

            <!-- Papers Table -->
            <div class="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    #
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Date
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Subject
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Exam
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Scope
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Target
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Time
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Marks
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(paper, paperIndex) in props.papers" :key="paper.id" class="transition-colors hover:bg-accent">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-muted-foreground">{{ paperIndex + 1 }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <Input v-if="editingId === paper.id" v-model="draft.paper_date" type="date" class="h-8 w-40" />
                                    <div v-else class="text-sm text-foreground">{{ formatDate(paper.paper_date) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-foreground">{{ paper.subject?.name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-muted-foreground">{{ paper.exam?.name }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            paper.scope_type === 'SCHOOL' ? 'bg-primary/10 text-primary' :
                                            paper.scope_type === 'CLASS' ? 'bg-primary/10 text-primary' :
                                            'bg-success/10 text-success'
                                        ]"
                                    >
                                        {{ paper.scope_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-muted-foreground">
                                        <span v-if="paper.scope_type === 'SCHOOL'">All School</span>
                                        <span v-else-if="paper.scope_type === 'CLASS'">{{ paper.class?.name }}</span>
                                        <span v-else>{{ paper.class?.name }} - {{ paper.section?.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <template v-if="editingId === paper.id">
                                        <div class="flex items-center gap-1">
                                            <Input v-model="draft.start_time" type="time" class="h-8 w-28" />
                                            <span>-</span>
                                            <Input v-model="draft.end_time" type="time" class="h-8 w-28" />
                                        </div>
                                    </template>
                                    <div v-else class="text-sm text-muted-foreground">{{ formatTime(paper.start_time) }} - {{ formatTime(paper.end_time) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div v-if="editingId === paper.id" class="flex items-center gap-1">
                                        <Input v-model="draft.total_marks" type="number" min="1" class="h-8 w-20" placeholder="Total" />
                                        <span>/</span>
                                        <Input v-model="draft.passing_marks" type="number" min="0" class="h-8 w-20" placeholder="Pass" />
                                    </div>
                                    <div v-else class="text-sm text-muted-foreground">{{ paper.total_marks }} / {{ paper.passing_marks }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            paper.status === 'cancelled' ? 'bg-destructive/10 text-destructive' :
                                            'bg-success/10 text-success'
                                        ]"
                                    >
                                        {{ paper.status === 'cancelled' ? 'Cancelled' : 'Scheduled' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        <template v-if="editingId === paper.id">
                                            <Button size="sm" :disabled="saving" @click="saveEdit(paper)" class="min-h-8">Save</Button>
                                            <Button variant="outline" size="sm" :disabled="saving" @click="editingId = null" class="min-h-8">Cancel</Button>
                                        </template>
                                        <template v-else>
                                            <Button variant="outline" size="sm" @click="startEdit(paper)" class="min-h-8">Edit</Button>
                                            <Button variant="outline" size="sm" @click="removePaper(paper)" class="min-h-8 text-destructive">Delete</Button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="props.papers.length === 0" class="text-center py-8 text-muted-foreground">
                No papers found.
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import FilterCard from '@/components/FilterCard.vue';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import { Input } from '@/components/ui/input';
import axios from 'axios';
import { ref } from 'vue';
import { alert } from '@/utils';
import { formatDate, formatTime } from '@/utils/format';

interface Exam {
    id: number;
    name: string;
}

interface SchoolClass {
    id: number;
    name: string;
}

interface Subject {
    id: number;
    name: string;
}

interface Paper {
    id: number;
    exam_id: number;
    subject_id: number;
    paper_date: string;
    start_time: string;
    end_time: string;
    total_marks: number;
    passing_marks: number;
    scope_type: string;
    class_id: number | null;
    section_id: number | null;
    campus_id: number | null;
    status: string;
    exam?: Exam;
    subject?: Subject;
    class?: SchoolClass;
    section?: { id: number; name: string };
    campus?: { id: number; name: string };
}

interface Props {
    papers: Paper[];
    exams: Exam[];
    campuses: Array<{id: number; name: string}>;
    classes: SchoolClass[];
    subjects: Subject[];
    filters?: {
        exam_id: string | number | null;
        class_id: string | number | null;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Papers', href: '/exams/papers' },
];

const filters = reactive({
    exam_id: props.filters?.exam_id || '',
    class_id: props.filters?.class_id || '',
});

const examOptions = computed(() => props.exams.map((exam) => ({ value: exam.id, label: exam.name })));
const classOptions = computed(() => props.classes.map((cls) => ({ value: cls.id, label: cls.name })));

const applyFilters = () => {
    const query: Record<string, string> = {};
    if (filters.exam_id) query.exam_id = String(filters.exam_id);
    if (filters.class_id) query.class_id = String(filters.class_id);

    router.visit(route('exam.papers.index-page', query), {
        preserveState: true,
    });
};

const editingId = ref<number | null>(null);
const saving = ref(false);
const draft = reactive({ paper_date: '', start_time: '', end_time: '', total_marks: '', passing_marks: '' });

const startEdit = (paper: Paper) => {
    editingId.value = paper.id;
    draft.paper_date = String(paper.paper_date).slice(0, 10);
    draft.start_time = String(paper.start_time).slice(0, 5);
    draft.end_time = String(paper.end_time).slice(0, 5);
    draft.total_marks = String(Number(paper.total_marks));
    draft.passing_marks = String(Number(paper.passing_marks));
};

const errorText = (error: any, fallback: string): string => {
    const errors = error.response?.data?.errors;
    if (errors) {
        return String(Object.values(errors).flat()[0]);
    }
    return error.response?.data?.message || fallback;
};

const saveEdit = async (paper: Paper) => {
    saving.value = true;
    try {
        await axios.put(route('exam.papers.update', paper.id), { ...draft });
        alert.success('Paper updated.');
        editingId.value = null;
        router.reload({ only: ['papers'], preserveScroll: true });
    } catch (error: any) {
        alert.warning(errorText(error, 'Could not update this paper. Please try again.'));
    } finally {
        saving.value = false;
    }
};

const removePaper = async (paper: Paper) => {
    const confirmed = await alert.confirm(`Delete the ${paper.subject?.name ?? 'paper'} paper? This cannot be undone.`, 'Delete Paper');
    if (!confirmed.isConfirmed) {
        return;
    }
    try {
        await axios.delete(route('exam.papers.destroy', paper.id));
        alert.success('Paper deleted.');
        router.reload({ only: ['papers'], preserveScroll: true });
    } catch (error: any) {
        alert.warning(errorText(error, 'Could not delete this paper. Please try again.'));
    }
};

watch(() => [filters.exam_id, filters.class_id], applyFilters);
</script>
