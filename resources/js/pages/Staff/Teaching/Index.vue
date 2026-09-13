<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

interface Lookup {
    id: number;
    name: string;
}

interface Assignment {
    id: number;
    is_class_teacher: boolean;
    periods_per_week?: number | null;
    staffProfile?: { id: number; user?: { name: string } | null } | null;
    schoolClass?: Lookup | null;
    section?: Lookup | null;
    subject?: Lookup | null;
}

interface Props {
    sessions: Lookup[];
    classes: Lookup[];
    subjects: Lookup[];
    filters: {
        session_id?: string;
        class_id?: string;
        section_id?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Teaching', href: route('staff.teaching.page') },
];

const selectClass = 'w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';

const filters = reactive({
    session_id: props.filters.session_id ?? (props.sessions[0]?.id ? String(props.sessions[0].id) : ''),
    class_id: props.filters.class_id ?? '',
    section_id: props.filters.section_id ?? '',
});

const sections = ref<Lookup[]>([]);
const assignments = ref<Assignment[]>([]);
const loading = ref(false);

const loadSections = async () => {
    sections.value = [];
    filters.section_id = '';

    if (!filters.class_id) return;

    const response = await axios.get(route('staff.teaching.sections'), { params: { class_id: filters.class_id } });
    sections.value = response.data.data;
};

const loadAssignments = async () => {
    if (!filters.session_id) return;

    loading.value = true;
    try {
        const response = await axios.get(route('staff.teaching.index'), { params: filters });
        assignments.value = response.data.data;
    } finally {
        loading.value = false;
    }
};

watch(() => filters.class_id, loadSections);
watch(filters, loadAssignments);

onMounted(loadAssignments);

/* ------------------------------------------------------------ who can teach */

const whoCanTeachSubjectId = ref('');
const whoCanTeachResults = ref<{ id: number; user?: { name: string } | null }[]>([]);
const whoCanTeachLoaded = ref(false);

const lookupWhoCanTeach = async () => {
    if (!whoCanTeachSubjectId.value) return;

    const response = await axios.get(route('staff.teaching.who-can-teach'), { params: { subject_id: whoCanTeachSubjectId.value } });
    whoCanTeachResults.value = response.data.data;
    whoCanTeachLoaded.value = true;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Teaching Assignments" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Teaching Assignments</h1>
                <p class="mt-1 text-sm text-muted-foreground">Who teaches which class — change it from a teacher's own profile.</p>
            </div>

            <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Session</label>
                        <select v-model="filters.session_id" :class="selectClass">
                            <option v-for="s in props.sessions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Class</label>
                        <select v-model="filters.class_id" :class="selectClass">
                            <option value="">All Classes</option>
                            <option v-for="c in props.classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Section</label>
                        <select v-model="filters.section_id" :class="selectClass" :disabled="sections.length === 0">
                            <option value="">All Sections</option>
                            <option v-for="s in sections" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Teacher</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Class / Section</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Periods/Week</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr v-for="assignment in assignments" :key="assignment.id" class="hover:bg-accent">
                            <td class="px-4 py-3">
                                <Link
                                    v-if="assignment.staffProfile"
                                    :href="route('staff.people.show', assignment.staffProfile.id)"
                                    class="font-medium text-foreground hover:underline"
                                >
                                    {{ assignment.staffProfile.user?.name || '-' }}
                                </Link>
                                <span v-if="assignment.is_class_teacher" class="ml-2 rounded-full bg-primary/10 px-2 py-0.5 text-xs text-primary">Class Teacher</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">{{ assignment.schoolClass?.name || '-' }} <span v-if="assignment.section">- {{ assignment.section.name }}</span></td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">{{ assignment.subject?.name || 'All subjects' }}</td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">{{ assignment.periods_per_week || '-' }}</td>
                        </tr>
                        <tr v-if="!loading && assignments.length === 0">
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-muted-foreground">No assignments for this filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                <h2 class="mb-3 text-lg font-semibold text-foreground">Who Can Cover a Subject</h2>
                <p class="mb-4 text-sm text-muted-foreground">The question a school asks when somebody rings in sick.</p>
                <div class="flex flex-wrap gap-2">
                    <select v-model="whoCanTeachSubjectId" :class="selectClass" style="max-width: 260px">
                        <option value="">Select subject</option>
                        <option v-for="s in props.subjects" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                    </select>
                    <Button @click="lookupWhoCanTeach"><Icon icon="search" class="h-4 w-4" />Find</Button>
                </div>
                <ul v-if="whoCanTeachLoaded" class="mt-4 space-y-1">
                    <li v-for="teacher in whoCanTeachResults" :key="teacher.id" class="text-sm">
                        <Link :href="route('staff.people.show', teacher.id)" class="text-primary hover:underline">{{ teacher.user?.name || '-' }}</Link>
                    </li>
                    <li v-if="whoCanTeachResults.length === 0" class="text-sm text-muted-foreground">Nobody is marked able to teach this subject.</li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
