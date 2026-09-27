<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/layouts/PortalLayout.vue';
import Icon from '@/components/Icon.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Collapsible, CollapsibleTrigger, CollapsibleContent } from '@/components/ui/collapsible';
import { formatCurrency, formatDate } from '@/utils/format';
import { ref, computed, onMounted } from 'vue';

interface ChildOption {
    id: number;
    name: string | null;
    class?: string | null;
    section?: string | null;
    avatar?: string | null;
}

interface SubjectResult {
    subject: { name: string };
    marks_obtained: number | string;
    max_marks: number | string;
    grade?: string | null;
    percentage?: number | null;
}

interface ExamResultDetail {
    id: number;
    exam: { name: string; examType?: { name: string } | null; academic_year?: string } | null;
    class?: { name: string } | null;
    section?: { name: string } | null;
    status: string;
    result_status: string | null;
    overallGradeItem?: { grade_letter: string } | null;
    percentage?: number | null;
    total_marks_obtained?: number | string;
    total_max_marks?: number | string;
    rank?: number | null;
    subjectResults: SubjectResult[];
    student?: { name: string; roll_no?: string; admission_no?: string } | null;
}

interface Props {
    student: ChildOption;
    students: ChildOption[];
    result: ExamResultDetail;
}

const props = defineProps<Props>();

const resultConfig = {
    pass: { label: 'Pass', variant: 'success' as const, icon: 'check-circle' },
    fail: { label: 'Fail', variant: 'destructive' as const, icon: 'x-circle' },
} as const;

const getResultConfig = (status: string | null) => {
    return resultConfig[status as keyof typeof resultConfig] ?? { label: status ?? '—', variant: 'secondary' as const, icon: 'help-circle' };
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
    { title: props.result.exam?.name ?? 'Result', href: route('portal.exams.show', props.result.id) },
]);

const printResult = () => {
    window.print();
};

const detailVisible = ref(false);
const subjectsVisible = ref(false);

onMounted(() => {
    requestAnimationFrame(() => {
        detailVisible.value = true;
        setTimeout(() => subjectsVisible.value = true, 100);
    });
});
</script>

<template>
<PortalLayout :breadcrumbs="breadcrumbs" :current-child="props.student" :children="props.students">
    <template #default>
        <Head :title="`Result: ${props.result.exam?.name}`" />
        <div class="space-y-6 animate-fade-slide-up">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h1 class="text-2xl md:text-3xl font-bold text-foreground">{{ props.result.exam?.name }}</h1>
                        <Badge v-if="props.result.result_status" :variant="getResultConfig(props.result.result_status).variant" class="gap-1.5 text-sm">
                            <Icon :icon="getResultConfig(props.result.result_status).icon" :size="12" />
                            {{ getResultConfig(props.result.result_status).label }}
                        </Badge>
                        <Badge v-else-if="props.result.status" variant="secondary" class="gap-1.5 text-sm">
                            {{ props.result.status }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ props.result.exam?.examType?.name }} · {{ props.result.class?.name }} - {{ props.result.section?.name }} · {{ props.result.student?.name }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button variant="outline" @click="printResult" class="no-print">
                        <Icon icon="printer" :size="16" />
                        Print Report
                    </Button>
                    <Link :href="route('portal.exams.index')" class="btn-ghost no-print">
                        <Icon icon="arrow-left" :size="16" />
                        Back to Results
                    </Link>
                </div>
            </div>

            <!-- Main Result Card -->
            <Card v-show="detailVisible" class="animate-fade-slide-up overflow-hidden">
                <CardHeader class="pb-4 border-b bg-gradient-to-r from-primary/5 to-accent/5">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="md:col-span-2">
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Overall Grade</p>
                            <p class="text-5xl font-bold mt-1" :class="gradeColorClass(props.result.overallGradeItem?.grade_letter)">
                                {{ props.result.overallGradeItem?.grade_letter ?? '—' }}
                            </p>
                        </div>
                        <div class="text-center border-l border-border pl-4">
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Percentage</p>
                            <p class="text-3xl font-bold text-foreground mt-1">{{ props.result.percentage != null ? `${props.result.percentage}%` : '—' }}</p>
                        </div>
                        <div class="text-center border-l border-border pl-4">
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Total Marks</p>
                            <p class="text-3xl font-bold text-foreground mt-1">
                                {{ props.result.total_marks_obtained ?? '—' }} / {{ props.result.total_max_marks ?? '—' }}
                            </p>
                        </div>
                        <div class="text-center border-l border-border pl-4">
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Rank</p>
                            <p class="text-3xl font-bold text-foreground mt-1">{{ props.result.rank ?? '—' }}</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="pt-4 space-y-6">
                    <!-- Student Info -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-muted-foreground">Student</p>
                            <p class="font-medium text-foreground">{{ props.result.student?.name }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Roll No</p>
                            <p class="font-medium text-foreground">{{ props.result.student?.roll_no ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Admission No</p>
                            <p class="font-medium text-foreground">{{ props.result.student?.admission_no ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Exam Type</p>
                            <p class="font-medium text-foreground">{{ props.result.exam?.examType?.name ?? '—' }}</p>
                        </div>
                    </div>

                    <Separator />

                    <!-- Subject Breakdown -->
                    <div v-show="subjectsVisible" class="animate-fade-slide-up">
                        <div class="flex items-center justify-between mb-4">
                            <CardTitle class="text-lg">Subject Breakdown</CardTitle>
                            <span class="text-sm text-muted-foreground">{{ props.result.subjectResults.length }} subjects</span>
                        </div>
                        <div class="space-y-2">
                            <Collapsible
                                v-for="(subject, index) in props.result.subjectResults"
                                :key="index"
                                class="w-full"
                            >
                                <CollapsibleTrigger class="w-full justify-start text-left px-0 py-3 border-b border-border/50 last:border-0 flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-foreground">{{ subject.subject.name }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="font-semibold text-foreground">{{ subject.marks_obtained }} / {{ subject.max_marks }}</p>
                                        <p v-if="subject.percentage != null" class="text-xs text-muted-foreground">{{ subject.percentage }}%</p>
                                    </div>
                                    <Badge
                                        v-if="subject.grade"
                                        :class="gradeColorClass(subject.grade).replace('text-', 'bg-') + ' text-' + gradeColorClass(subject.grade).replace('text-', '') + '/10'"
                                        class="text-xs shrink-0 ml-2"
                                    >
                                        {{ subject.grade }}
                                    </Badge>
                                    <Icon icon="chevron-down" :size="16" class="text-muted-foreground transition-transform duration-200 shrink-0" />
                                </CollapsibleTrigger>
                                <CollapsibleContent class="pb-3">
                                    <div class="grid grid-cols-3 gap-4 text-sm pt-2 text-muted-foreground">
                                        <div>
                                            <p class="font-medium text-foreground">Marks Obtained</p>
                                            <p>{{ subject.marks_obtained }}</p>
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground">Max Marks</p>
                                            <p>{{ subject.max_marks }}</p>
                                        </div>
                                        <div v-if="subject.percentage != null">
                                            <p class="font-medium text-foreground">Percentage</p>
                                            <p>{{ subject.percentage }}%</p>
                                        </div>
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Summary Stats -->
            <div v-show="detailVisible" class="animate-fade-slide-up stagger-1 grid grid-cols-2 md:grid-cols-4 gap-3">
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-primary">{{ props.result.subjectResults.length }}</p>
                        <p class="text-xs text-muted-foreground">Subjects</p>
                    </CardContent>
                </Card>
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-success">{{ props.result.subjectResults.filter(s => s.percentage && s.percentage >= 40).length }}</p>
                        <p class="text-xs text-muted-foreground">Passed</p>
                    </CardContent>
                </Card>
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-warning">{{ props.result.subjectResults.filter(s => s.percentage && s.percentage < 40).length }}</p>
                        <p class="text-xs text-muted-foreground">Failed</p>
                    </CardContent>
                </Card>
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold" :class="gradeColorClass(props.result.overallGradeItem?.grade_letter)">
                            {{ props.result.overallGradeItem?.grade_letter ?? '—' }}
                        </p>
                        <p class="text-xs text-muted-foreground">Overall Grade</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Print Version -->
            <div class="print-only hidden" style="display: none;">
                @media print {
                    .no-print { display: none !important; }
                    .print-only { display: block !important; }
                    body { font-size: 11pt; }
                    .result-print { padding: 20px; max-width: 800px; margin: 0 auto; }
                }
                <div class="result-print">
                    <div class="text-center mb-6 border-b-2 pb-4">
                        <h2 class="text-2xl font-bold">{{ props.result.exam?.name }}</h2>
                        <p class="text-sm text-muted-foreground">{{ props.result.exam?.examType?.name }} · {{ props.result.exam?.academic_year }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                        <div><strong>Student:</strong> {{ props.result.student?.name }}</div>
                        <div><strong>Class:</strong> {{ props.result.class?.name }} - {{ props.result.section?.name }}</div>
                        <div><strong>Roll No:</strong> {{ props.result.student?.roll_no }}</div>
                        <div><strong>Grade:</strong> <span :class="gradeColorClass(props.result.overallGradeItem?.grade_letter) + ' font-bold'">{{ props.result.overallGradeItem?.grade_letter }}</span></div>
                    </div>
                    <table class="w-full border-collapse mb-4 text-sm">
                        <thead>
                            <tr class="border-b-2">
                                <th class="text-left p-2">Subject</th>
                                <th class="text-right p-2">Obtained</th>
                                <th class="text-right p-2">Max</th>
                                <th class="text-right p-2">%</th>
                                <th class="text-center p-2">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(subject, index) in props.result.subjectResults" :key="index" class="border-b">
                                <td class="p-2">{{ subject.subject.name }}</td>
                                <td class="p-2 text-right">{{ subject.marks_obtained }}</td>
                                <td class="p-2 text-right">{{ subject.max_marks }}</td>
                                <td class="p-2 text-right">{{ subject.percentage ?? '—' }}</td>
                                <td class="p-2 text-center" :class="gradeColorClass(subject.grade)">{{ subject.grade ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="text-right font-bold">Total: {{ props.result.total_marks_obtained }} / {{ props.result.total_max_marks }} ({{ props.result.percentage }}%)</div>
                </div>
            </div>
        </div>
    </template>
</PortalLayout>
</template>