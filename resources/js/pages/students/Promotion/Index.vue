<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';

interface Session {
    id: number;
    name: string;
}

interface SchoolClassOption {
    id: number;
    name: string;
    level: number | null;
}

interface Section {
    id: number;
    name: string;
}

interface PreviewStudent {
    student_id: number;
    name: string | null;
    admission_no: string | null;
    percentage: number | null;
    grade: string | null;
    result_status: string | null;
    year_complete: boolean;
    suggested_outcome: 'promoted' | 'detained' | 'promoted_on_condition';
}

interface Props {
    sessions: Session[];
    classes: SchoolClassOption[];
    filters: {
        session_id?: string;
        class_id?: string;
        section_id?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Students', href: route('students.index') },
    { title: 'Promotion', href: '#' },
];

const outcomeOptions: Array<{ value: PreviewStudent['suggested_outcome']; label: string }> = [
    { value: 'promoted', label: 'Promoted' },
    { value: 'detained', label: 'Detained' },
    { value: 'promoted_on_condition', label: 'Promoted on condition' },
];

// The section-being-promoted filters.
const filters = reactive({
    session_id: props.filters?.session_id ?? '',
    class_id: props.filters?.class_id ?? '',
    section_id: props.filters?.section_id ?? '',
});

const fromSections = ref<Section[]>([]);
const fromSectionsLoading = ref(false);

const fetchSections = async (classId: string, target: 'from' | 'to') => {
    if (!classId) {
        if (target === 'from') {
            fromSections.value = [];
        } else {
            toSections.value = [];
        }
        return;
    }

    if (target === 'from') {
        fromSectionsLoading.value = true;
    } else {
        toSectionsLoading.value = true;
    }

    try {
        const response = await axios.get(route('students.sections-by-class'), {
            params: { class_id: classId },
        });

        if (target === 'from') {
            fromSections.value = response.data;
        } else {
            toSections.value = response.data;
        }
    } catch {
        alert.error('Could not load sections for that class.');
    } finally {
        if (target === 'from') {
            fromSectionsLoading.value = false;
        } else {
            toSectionsLoading.value = false;
        }
    }
};

watch(
    () => filters.class_id,
    (classId) => {
        filters.section_id = '';
        fetchSections(classId, 'from');
    },
    { immediate: true }
);

// Where the section is promoted to.
const target = reactive({
    to_session_id: '',
    to_class_id: '',
    to_section_id: '',
    starting_on: '',
});

const suggestedClass = ref<SchoolClassOption | null>(null);
const toSections = ref<Section[]>([]);
const toSectionsLoading = ref(false);

watch(
    () => target.to_class_id,
    (classId) => {
        target.to_section_id = '';
        fetchSections(classId, 'to');
    }
);

// The preview sheet: who is in the section, and what the system suggests.
const previewLoading = ref(false);
const hasPreviewed = ref(false);
const students = ref<PreviewStudent[]>([]);
const included = reactive<Record<number, boolean>>({});
const outcomes = reactive<Record<number, PreviewStudent['suggested_outcome']>>({});

const fetchPreview = async () => {
    if (!filters.session_id || !filters.class_id) {
        alert.error('Please select a session and a class.');
        return;
    }

    previewLoading.value = true;

    try {
        const response = await axios.get(route('students.promotion.preview'), {
            params: {
                session_id: filters.session_id,
                class_id: filters.class_id,
                section_id: filters.section_id || null,
            },
        });

        const data = response.data.data;
        suggestedClass.value = data.suggested_class;
        students.value = data.students;

        if (!target.to_class_id && data.suggested_class) {
            target.to_class_id = String(data.suggested_class.id);
        }

        Object.keys(included).forEach((key) => delete included[Number(key)]);
        Object.keys(outcomes).forEach((key) => delete outcomes[Number(key)]);

        data.students.forEach((student: PreviewStudent) => {
            included[student.student_id] = true;
            outcomes[student.student_id] = student.suggested_outcome;
        });

        hasPreviewed.value = true;
    } catch (error: any) {
        alert.error(error?.response?.data?.message ?? 'Could not load the preview.');
    } finally {
        previewLoading.value = false;
    }
};

const includedCount = computed(() => Object.values(included).filter(Boolean).length);

const toggleAll = (value: boolean) => {
    students.value.forEach((student) => {
        included[student.student_id] = value;
    });
};

const outcomeLabel = (value: string | null): string => {
    return outcomeOptions.find((option) => option.value === value)?.label ?? value ?? '—';
};

const resultBadgeClass = (status: string | null): string => {
    switch (status) {
        case 'fail':
            return 'bg-destructive/10 text-destructive';
        case 'pass':
            return 'bg-success/10 text-success';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

// Running the promotion.
const isPromoting = ref(false);

const runPromotion = () => {
    if (!target.to_session_id) {
        alert.error('Please select the session students are being promoted into.');
        return;
    }

    const selected = students.value.filter((student) => included[student.student_id]);

    if (selected.length === 0) {
        alert.error('Select at least one student to promote.');
        return;
    }

    isPromoting.value = true;

    router.post(
        route('students.promotion.run'),
        {
            to_session_id: target.to_session_id,
            to_class_id: target.to_class_id || null,
            to_section_id: target.to_section_id || null,
            starting_on: target.starting_on || null,
            students: selected.map((student) => ({
                student_id: student.student_id,
                outcome: outcomes[student.student_id],
            })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                hasPreviewed.value = false;
                students.value = [];
            },
            onFinish: () => {
                isPromoting.value = false;
            },
        }
    );
};

// Undoing a promotion, a student at a time.
const revertForm = reactive({
    session_id: '',
    student_ids: '',
});
const isReverting = ref(false);

const runRevert = () => {
    const ids = revertForm.student_ids
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean)
        .map(Number);

    if (!revertForm.session_id || ids.length === 0) {
        alert.error('Enter the session that was promoted into and at least one student id.');
        return;
    }

    isReverting.value = true;

    router.post(
        route('students.promotion.revert'),
        {
            session_id: revertForm.session_id,
            student_ids: ids,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isReverting.value = false;
            },
        }
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Promote Students" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">
                    Promote Students
                </h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Move a class or section into the next session, based on each student's annual result.
                </p>
            </div>

            <!-- Section to promote -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-base font-semibold text-foreground mb-4">1. Choose the section</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <Label for="session_id">Academic Session *</Label>
                        <select
                            id="session_id"
                            v-model="filters.session_id"
                            class="mt-1 block w-full rounded-md border border-border bg-card text-foreground px-3 py-2"
                        >
                            <option value="">Select Session</option>
                            <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                                {{ session.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <Label for="class_id">Class *</Label>
                        <select
                            id="class_id"
                            v-model="filters.class_id"
                            class="mt-1 block w-full rounded-md border border-border bg-card text-foreground px-3 py-2"
                        >
                            <option value="">Select Class</option>
                            <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <Label for="section_id">Section</Label>
                        <select
                            id="section_id"
                            v-model="filters.section_id"
                            :disabled="!filters.class_id || fromSectionsLoading || fromSections.length === 0"
                            :class="[
                                'mt-1 block w-full rounded-md border border-border bg-card text-foreground px-3 py-2',
                                (!filters.class_id || fromSections.length === 0) ? 'cursor-not-allowed opacity-50' : '',
                            ]"
                        >
                            <option value="">{{ fromSections.length > 0 ? 'All Sections' : 'No Sections' }}</option>
                            <option v-for="section in fromSections" :key="section.id" :value="section.id">
                                {{ section.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <Button type="button" :disabled="previewLoading" @click="fetchPreview">
                        <Icon v-if="previewLoading" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="search" class="mr-2 h-4 w-4" />
                        {{ previewLoading ? 'Loading...' : 'Preview' }}
                    </Button>
                </div>
            </div>

            <!-- Preview + promote -->
            <div v-if="hasPreviewed" class="bg-card rounded-lg border border-border p-6 space-y-6">
                <div>
                    <h2 class="text-base font-semibold text-foreground mb-4">2. Where they're going</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <Label for="to_session_id">Into Session *</Label>
                            <select
                                id="to_session_id"
                                v-model="target.to_session_id"
                                class="mt-1 block w-full rounded-md border border-border bg-card text-foreground px-3 py-2"
                            >
                                <option value="">Select Session</option>
                                <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                                    {{ session.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <Label for="to_class_id">Into Class</Label>
                            <select
                                id="to_class_id"
                                v-model="target.to_class_id"
                                class="mt-1 block w-full rounded-md border border-border bg-card text-foreground px-3 py-2"
                            >
                                <option value="">{{ suggestedClass ? suggestedClass.name : 'Select Class' }}</option>
                                <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>
                            <p v-if="suggestedClass" class="mt-1 text-xs text-muted-foreground">
                                Suggested: {{ suggestedClass.name }}
                            </p>
                        </div>

                        <div>
                            <Label for="to_section_id">Into Section</Label>
                            <select
                                id="to_section_id"
                                v-model="target.to_section_id"
                                :disabled="!target.to_class_id || toSections.length === 0"
                                :class="[
                                    'mt-1 block w-full rounded-md border border-border bg-card text-foreground px-3 py-2',
                                    (!target.to_class_id || toSections.length === 0) ? 'cursor-not-allowed opacity-50' : '',
                                ]"
                            >
                                <option value="">{{ toSections.length > 0 ? 'Any Section' : 'No Sections' }}</option>
                                <option v-for="section in toSections" :key="section.id" :value="section.id">
                                    {{ section.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <Label for="starting_on">Starting On</Label>
                            <Input id="starting_on" v-model="target.starting_on" type="date" class="mt-1" />
                        </div>
                    </div>
                </div>

                <div>
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-base font-semibold text-foreground">
                            3. Confirm each student ({{ includedCount }} of {{ students.length }} selected)
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" size="sm" @click="toggleAll(true)">
                                Select All
                            </Button>
                            <Button type="button" variant="outline" size="sm" @click="toggleAll(false)">
                                Clear All
                            </Button>
                        </div>
                    </div>

                    <div v-if="students.length === 0" class="rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                        No students found for that class and section.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-3 py-2 w-10"></th>
                                    <th class="px-3 py-2">Student</th>
                                    <th class="px-3 py-2">Admission No.</th>
                                    <th class="px-3 py-2">Result</th>
                                    <th class="px-3 py-2">Percentage</th>
                                    <th class="px-3 py-2">Outcome</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="student in students" :key="student.student_id" class="hover:bg-accent">
                                    <td class="px-3 py-2">
                                        <input
                                            type="checkbox"
                                            v-model="included[student.student_id]"
                                            class="w-4 h-4 text-primary rounded"
                                            :aria-label="`Include ${student.name}`"
                                        />
                                    </td>
                                    <td class="px-3 py-2 font-medium text-foreground">{{ student.name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-muted-foreground">{{ student.admission_no ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="resultBadgeClass(student.result_status)"
                                        >
                                            {{ student.result_status ?? 'Pending' }}
                                        </span>
                                        <span v-if="!student.year_complete" class="ml-1 text-xs text-muted-foreground">
                                            (unmarked)
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-muted-foreground">
                                        {{ student.percentage !== null ? `${student.percentage}%` : '—' }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <select
                                            v-model="outcomes[student.student_id]"
                                            :disabled="!included[student.student_id]"
                                            class="rounded-md border border-border bg-card text-foreground px-2 py-1 text-sm disabled:opacity-50"
                                        >
                                            <option v-for="option in outcomeOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </option>
                                        </select>
                                        <p v-if="outcomes[student.student_id] !== student.suggested_outcome" class="mt-1 text-xs text-muted-foreground">
                                            Suggested: {{ outcomeLabel(student.suggested_outcome) }}
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end border-t border-border pt-6">
                    <Button type="button" variant="outline" @click="router.visit(route('students.index'))">
                        Cancel
                    </Button>
                    <Button type="button" :disabled="isPromoting || includedCount === 0" @click="runPromotion">
                        <Icon v-if="isPromoting" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="arrow-up-circle" class="mr-2 h-4 w-4" />
                        {{ isPromoting ? 'Promoting...' : `Promote ${includedCount} Student${includedCount !== 1 ? 's' : ''}` }}
                    </Button>
                </div>
            </div>

            <!-- Revert a past run -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-base font-semibold text-foreground mb-1">Undo a promotion</h2>
                <p class="text-xs text-muted-foreground mb-4">
                    Reverts specific students back out of a session they were promoted into by mistake.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                    <div>
                        <Label for="revert_session_id">Session Promoted Into *</Label>
                        <select
                            id="revert_session_id"
                            v-model="revertForm.session_id"
                            class="mt-1 block w-full rounded-md border border-border bg-card text-foreground px-3 py-2"
                        >
                            <option value="">Select Session</option>
                            <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                                {{ session.name }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <Label for="revert_student_ids">Student IDs (comma separated) *</Label>
                        <Input
                            id="revert_student_ids"
                            v-model="revertForm.student_ids"
                            type="text"
                            placeholder="e.g. 12, 45, 78"
                            class="mt-1"
                        />
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <Button type="button" variant="destructive" :disabled="isReverting" @click="runRevert">
                        <Icon v-if="isReverting" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="undo" class="mr-2 h-4 w-4" />
                        {{ isReverting ? 'Reverting...' : 'Undo Promotion' }}
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
