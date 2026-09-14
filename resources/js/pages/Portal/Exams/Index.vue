<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';

interface ChildOption {
    id: number;
    name: string | null;
}

interface ExamResult {
    id: number;
    status: string;
    result_status: string | null;
    exam?: { name: string; examType?: { name: string } | null } | null;
    class?: { name: string } | null;
    section?: { name: string } | null;
    overallGradeItem?: { grade_letter: string } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
}

interface Props {
    student: ChildOption;
    students: ChildOption[];
    results: Paginated<ExamResult>;
}

const props = defineProps<Props>();

const resultClass = (status: string | null): string => {
    return (
        {
            pass: 'bg-success/10 text-success',
            fail: 'bg-destructive/10 text-destructive',
        }[status ?? ''] ?? 'bg-muted text-muted-foreground'
    );
};

const classLabel = (result: ExamResult): string => {
    return [result.class?.name, result.section?.name].filter(Boolean).join(' - ');
};
</script>

<template>
    <AppLayout>
        <Head title="My Exam Results" />

        <div class="space-y-4 md:space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">Exam Results</h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Published results for {{ props.student.name }}.
                    </p>
                </div>

                <label v-if="props.students.length > 1" class="w-full sm:w-auto">
                    <span class="sr-only">Choose child</span>
                    <select
                        :value="props.student.id"
                        class="w-full sm:w-auto rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                        @change="
                            (event) => router.visit(route('portal.exams.index', { student_id: (event.target as HTMLSelectElement).value }))
                        "
                    >
                        <option v-for="child in props.students" :key="child.id" :value="child.id">
                            {{ child.name }}
                        </option>
                    </select>
                </label>
            </div>

            <!-- Empty state -->
            <div v-if="props.results.data.length === 0" class="bg-card rounded-lg border border-border p-8 text-center text-muted-foreground">
                <Icon icon="clipboard-list" class="h-10 w-10 mx-auto mb-3 text-muted-foreground" />
                No published results yet.
            </div>

            <template v-else>
                <!-- Mobile Card View -->
                <div class="block lg:hidden space-y-3">
                    <div v-for="result in props.results.data" :key="result.id" class="bg-card rounded-lg border border-border p-4 space-y-3">
                        <div class="flex flex-wrap gap-2 justify-between items-start">
                            <div>
                                <div class="font-medium text-foreground">{{ result.exam?.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ result.exam?.examType?.name }} · {{ classLabel(result) }}</div>
                            </div>
                            <span v-if="result.result_status" class="px-2 py-1 text-xs font-medium rounded-full shrink-0" :class="resultClass(result.result_status)">
                                {{ result.result_status }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-sm pt-2 border-t border-border">
                            <span class="text-muted-foreground">Grade</span>
                            <span class="font-semibold text-foreground">{{ result.overallGradeItem?.grade_letter ?? '—' }}</span>
                        </div>

                        <a :href="route('portal.exams.show', result.id)" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                            View Result Card
                            <Icon icon="arrow-right" :size="14" />
                        </a>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Exam</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Class</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Result</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Grade</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="result in props.results.data" :key="result.id" class="transition-colors hover:bg-accent">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">{{ result.exam?.name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ classLabel(result) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span v-if="result.result_status" class="px-2 py-1 text-xs font-medium rounded-full" :class="resultClass(result.result_status)">
                                            {{ result.result_status }}
                                        </span>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ result.overallGradeItem?.grade_letter ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <a :href="route('portal.exams.show', result.id)" target="_blank" rel="noopener" class="text-sm font-medium text-primary hover:underline">
                                            View card
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <div v-if="props.results.links.length > 3" class="flex flex-wrap gap-1">
                <template v-for="link in props.results.links" :key="`${link.label}-${link.url ?? 'disabled'}`">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded px-3 py-1 text-sm border border-border"
                        :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'text-foreground'"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span v-else class="rounded px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
