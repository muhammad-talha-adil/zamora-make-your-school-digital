<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import TablePagination from '@/components/tables/TablePagination.vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import Icon from '@/components/Icon.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleTrigger, CollapsibleContent } from '@/components/ui/collapsible';
import { ref, computed, onMounted } from 'vue';

interface ChildOption {
    id: number;
    name: string | null;
    class?: string | null;
    section?: string | null;
    avatar?: string | null;
}

interface ExamResult {
    id: number;
    status: string;
    result_status: string | null;
    exam?: { name: string; examType?: { name: string } | null; id: number } | null;
    class?: { name: string } | null;
    section?: { name: string } | null;
    overallGradeItem?: { grade_letter: string } | null;
    percentage?: number | null;
    subjectResults?: { subject: { name: string }; marks_obtained: number | string; max_marks: number | string; grade?: string }[];
}

interface UpcomingPaper {
    id: number;
    exam: string | null;
    subject: string | null;
    paper_date: string | null;
    start_time: string | null;
    end_time: string | null;
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
    upcomingPapers: UpcomingPaper[];
}

const props = defineProps<Props>();

const formatDate = (date: string | null): string => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
};

const resultConfig = {
    pass: { label: 'Pass', variant: 'success' as const, icon: 'check-circle' },
    fail: { label: 'Fail', variant: 'destructive' as const, icon: 'x-circle' },
} as const;

const getResultConfig = (status: string | null) => {
    return resultConfig[status as keyof typeof resultConfig] ?? { label: status ?? '—', variant: 'secondary' as const, icon: 'help-circle' };
};

const classLabel = (result: ExamResult): string => {
    return [result.class?.name, result.section?.name].filter(Boolean).join(' - ');
};

const gradeColorClass = (grade: string | null | undefined): string => {
    if (!grade) return 'text-muted-foreground';
    const g = grade.toUpperCase();
    if (['A+', 'A', 'A-'].includes(g)) return 'text-success';
    if (['B+', 'B', 'B-'].includes(g)) return 'text-primary';
    if (['C+', 'C', 'C-'].includes(g)) return 'text-warning';
    if (['D', 'E', 'F'].includes(g)) return 'text-destructive';
    return 'text-foreground';
};

const breadcrumbs = computed(() => [
    { title: 'Exams', href: route('portal.exams.index') },
]);

const listVisible = ref(false);
const cardsVisible = ref(false);

onMounted(() => {
    requestAnimationFrame(() => {
        listVisible.value = true;
        setTimeout(() => cardsVisible.value = true, 60);
    });
});
</script>

<template>
<PortalLayout :breadcrumbs="breadcrumbs" :current-child="props.student" :children="props.students">
    <template #default>
        <Head title="Exam Results" />
        <div class="space-y-6 animate-fade-slide-up">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-foreground">Exam Results</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Published results for {{ props.student.name }}.
                    </p>
                </div>
            </div>

            <!-- Upcoming Papers -->
            <Card v-if="props.upcomingPapers.length > 0" v-show="listVisible" class="animate-fade-slide-up stagger-1 card-interactive">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-lg flex items-center gap-2">
                            <Icon icon="calendar-clock" class="text-primary" :size="20" />
                            Upcoming Papers
                        </CardTitle>
                        <span class="text-sm text-muted-foreground">{{ props.upcomingPapers.length }} paper(s)</span>
                    </div>
                </CardHeader>
                <CardContent class="pt-0">
                    <div class="space-y-2">
                        <Link
                            v-for="paper in props.upcomingPapers"
                            :key="paper.id"
                            :href="route('portal.exams.show', paper.id)"
                            target="_blank"
                            rel="noopener"
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-lg border border-border p-3 hover:bg-accent/50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-primary/10">
                                    <Icon icon="book-open" class="text-primary" :size="18" />
                                </div>
                                <div>
                                    <p class="font-medium text-foreground">{{ paper.subject }}</p>
                                    <p class="text-xs text-muted-foreground">{{ paper.exam }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <Icon icon="calendar" :size="14" />
                                    {{ formatDate(paper.paper_date) }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <Icon icon="clock" :size="14" />
                                    {{ paper.start_time }} – {{ paper.end_time }}
                                </span>
                                <Icon icon="chevron-right" class="w-4 h-4 text-muted-foreground/50" />
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty State -->
            <div v-if="props.results.data.length === 0" v-show="listVisible" class="animate-fade-slide-up stagger-2">
                <Card class="bg-muted/50 border-dashed">
                    <CardContent class="py-16 text-center">
                        <Icon icon="clipboard-list" class="h-16 w-16 mx-auto text-muted-foreground/50 mb-4" />
                        <h3 class="text-lg font-semibold text-foreground mb-1">No published results yet</h3>
                        <p class="text-sm text-muted-foreground mb-6">
                            Results will appear here once they are published by the school.
                        </p>
                        <div class="flex items-center justify-center gap-2 text-sm text-muted-foreground">
                            <Icon icon="info" :size="16" />
                            <span>Check back after exams are graded.</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Results List -->
            <template v-else>
                <!-- Mobile Card View -->
                <div v-show="cardsVisible" class="block lg:hidden space-y-3 animate-fade-slide-up stagger-2">
                    <Card
                        v-for="result in props.results.data"
                        :key="result.id"
                        class="card-interactive overflow-hidden"
                    >
                        <CardContent class="p-4 space-y-3">
                            <div class="flex flex-wrap gap-2 justify-between items-start">
                                <div class="min-w-0">
                                    <p class="font-medium text-foreground truncate">{{ result.exam?.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ result.exam?.examType?.name }} · {{ classLabel(result) }}
                                    </p>
                                </div>
                                <Badge v-if="result.result_status" :variant="getResultConfig(result.result_status).variant" class="gap-1 shrink-0">
                                    <Icon :icon="getResultConfig(result.result_status).icon" :size="11" />
                                    {{ getResultConfig(result.result_status).label }}
                                </Badge>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t">
                                <span class="text-sm text-muted-foreground">Grade</span>
                                <span class="text-2xl font-bold" :class="gradeColorClass(result.overallGradeItem?.grade_letter)">
                                    {{ result.overallGradeItem?.grade_letter ?? '—' }}
                                </span>
                            </div>

                            <div v-if="result.percentage != null" class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Percentage</span>
                                <span class="font-semibold text-foreground">{{ result.percentage }}%</span>
                            </div>

                            <Collapsible v-if="result.subjectResults && result.subjectResults.length > 0" class="w-full">
                                <CollapsibleTrigger class="w-full justify-start text-left px-0 py-1 text-sm font-medium text-primary hover:underline flex items-center gap-1">
                                    <Icon icon="chevron-down" :size="14" class="transition-transform duration-200" />
                                    Subject Breakdown
                                </CollapsibleTrigger>
                                <CollapsibleContent class="pt-2 space-y-2">
                                    <div v-for="subject in result.subjectResults" :key="subject.subject.name" class="flex items-center justify-between text-sm py-1 border-t border-border/50">
                                        <span class="text-muted-foreground">{{ subject.subject.name }}</span>
                                        <span class="font-medium text-foreground">{{ subject.marks_obtained }} / {{ subject.max_marks }}</span>
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>

                            <a :href="route('portal.exams.show', result.id)" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline w-full justify-center py-2">
                                View Result Card
                                <Icon icon="arrow-right" :size="14" />
                            </a>
                        </CardContent>
                    </Card>
                </div>

                <!-- Desktop Table View -->
                <div v-show="listVisible" class="hidden lg:block animate-fade-slide-up stagger-2">
                    <Card class="overflow-hidden">
                        <CardContent class="p-0">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-border">
                                    <thead class="bg-muted">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Exam</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Class</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Grade</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Percentage</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border bg-card">
                                        <tr
                                            v-for="result in props.results.data"
                                            :key="result.id"
                                            class="transition-colors hover:bg-accent/50"
                                        >
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">{{ result.exam?.name }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ classLabel(result) }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <Badge v-if="result.result_status" :variant="getResultConfig(result.result_status).variant" class="gap-1">
                                                    <Icon :icon="getResultConfig(result.result_status).icon" :size="11" />
                                                    {{ getResultConfig(result.result_status).label }}
                                                </Badge>
                                                <span v-else class="text-xs text-muted-foreground">—</span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm" :class="gradeColorClass(result.overallGradeItem?.grade_letter)">
                                                <span class="font-bold text-lg">{{ result.overallGradeItem?.grade_letter ?? '—' }}</span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ result.percentage != null ? `${result.percentage}%` : '—' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                                <a :href="route('portal.exams.show', result.id)" target="_blank" rel="noopener" class="text-sm font-medium text-primary hover:underline flex items-center gap-1 justify-end">
                                                    View
                                                    <Icon icon="arrow-right" :size="12" />
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <TablePagination
                    :pagination="props.results"
                    :show-per-page-selector="false"
                    use-links
                    class="animate-fade-slide-up stagger-3"
                />
            </template>
        </div>
    </template>
</PortalLayout>
</template>