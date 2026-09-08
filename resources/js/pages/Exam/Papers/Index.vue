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
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap">
                <div class="w-full sm:w-56 md:w-64">
                    <Label for="filter-exam" class="sr-only">Filter by Exam</Label>
                    <select
                        id="filter-exam"
                        v-model="filters.exam_id"
                        @change="applyFilters"
                        class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Exams</option>
                        <option v-for="exam in props.exams" :key="exam.id" :value="exam.id">
                            {{ exam.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-56 md:w-64">
                    <Label for="filter-class" class="sr-only">Filter by Class</Label>
                    <select
                        id="filter-class"
                        v-model="filters.class_id"
                        @change="applyFilters"
                        class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Classes</option>
                        <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                            {{ cls.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Papers Table -->
            <div class="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
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
                            <tr v-for="paper in props.papers" :key="paper.id" class="transition-colors hover:bg-accent">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-foreground">{{ paper.paper_date }}</div>
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
                                    <div class="text-sm text-muted-foreground">{{ paper.start_time }} - {{ paper.end_time }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-muted-foreground">{{ paper.total_marks }} / {{ paper.passing_marks }}</div>
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
                                        <Button variant="outline" size="sm" @click="router.visit(route('exam.papers.edit-page', paper.id))" class="min-h-8">
                                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                        </Button>
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
import { reactive } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';

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

const applyFilters = () => {
    const query: Record<string, string> = {};
    if (filters.exam_id) query.exam_id = String(filters.exam_id);
    if (filters.class_id) query.class_id = String(filters.class_id);
    
    router.visit(route('exam.papers.index-page', query), {
        preserveState: true,
    });
};
</script>
