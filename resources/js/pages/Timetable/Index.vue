<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import TimetableGrid, { type TimetableEntryRow, type TimetablePeriodRow } from '@/components/timetable/TimetableGrid.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import type { BreadcrumbItem } from '@/types';

interface Lookup {
    id: number;
    name: string | null;
}

interface SectionOption {
    key: string;
    class_id: number;
    section_id: number | null;
    label: string;
}

interface Props {
    sessions: (Lookup & { is_active: boolean })[];
    sectionOptions: SectionOption[];
    filters: { session_id: number | null; class_id: number | null; section_id: number | null };
    periods: TimetablePeriodRow[];
    entries: TimetableEntryRow[];
    canManage: boolean;
    subjects: Lookup[];
    teachers: Lookup[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Timetable', href: route('timetable.index') },
];

const weekdays = [
    { value: '1', label: 'Monday' },
    { value: '2', label: 'Tuesday' },
    { value: '3', label: 'Wednesday' },
    { value: '4', label: 'Thursday' },
    { value: '5', label: 'Friday' },
    { value: '6', label: 'Saturday' },
    { value: '7', label: 'Sunday' },
];

const sessionOptions = computed(() => props.sessions.map((s) => ({ value: String(s.id), label: s.name ?? '' })));
const sectionSelectOptions = computed(() => props.sectionOptions.map((o) => ({ value: o.key, label: o.label })));
const subjectOptions = computed(() => props.subjects.map((s) => ({ value: String(s.id), label: s.name ?? '' })));
const teacherOptions = computed(() => props.teachers.map((t) => ({ value: String(t.id), label: t.name ?? '' })));
const periodOptions = computed(() =>
    props.periods.filter((p) => !p.is_break).map((p) => ({ value: String(p.id), label: `${p.name} (${p.start_time} - ${p.end_time})` })),
);

const sessionId = computed({
    get: () => (props.filters.session_id ? String(props.filters.session_id) : ''),
    set: (value: string) => reload({ session_id: value }),
});

const sectionKey = computed({
    get: () => `${props.filters.class_id ?? ''}:${props.filters.section_id ?? ''}`,
    set: (key: string) => {
        const option = props.sectionOptions.find((o) => o.key === key);
        if (option) reload({ class_id: option.class_id, section_id: option.section_id ?? '' });
    },
});

const reload = (changes: Record<string, string | number>) => {
    router.get(
        route('timetable.index'),
        { session_id: props.filters.session_id ?? '', class_id: props.filters.class_id ?? '', section_id: props.filters.section_id ?? '', ...changes },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

/* ---------------------------------------------------------------- dialog */

const dialogOpen = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    class_id: '',
    section_id: '',
    subject_id: '',
    staff_profile_id: '',
    period_id: '',
    weekday: '',
    room: '',
});

const openFor = (slot: { weekday: number; period_id: number }) => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.class_id = String(props.filters.class_id ?? '');
    form.section_id = String(props.filters.section_id ?? '');
    form.weekday = String(slot.weekday);
    form.period_id = String(slot.period_id);
    dialogOpen.value = true;
};

const openEntry = (entry: TimetableEntryRow) => {
    editingId.value = entry.id;
    form.clearErrors();
    form.class_id = String(entry.class_id);
    form.section_id = entry.section_id ? String(entry.section_id) : '';
    form.subject_id = String(entry.subject_id);
    form.staff_profile_id = String(entry.staff_profile_id);
    form.period_id = String(entry.period_id);
    form.weekday = String(entry.weekday);
    form.room = entry.room ?? '';
    dialogOpen.value = true;
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    if (editingId.value) {
        form.put(route('timetable.entries.update', editingId.value), options);
    } else {
        form.post(route('timetable.entries.store'), options);
    }
};

const remove = () => {
    if (!editingId.value) return;

    router.delete(route('timetable.entries.destroy', editingId.value), {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Class Timetable" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Class Timetable</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ props.canManage ? 'Pick a lesson slot to add or change it.' : 'The weekly timetable of your classes.' }}
                    </p>
                </div>
                <Button v-if="props.canManage" as-child variant="outline">
                    <Link :href="route('timetable.periods.index')"><Icon icon="clock" :size="16" class="mr-2" />Periods</Link>
                </Button>
            </div>

            <div class="grid gap-4 rounded-2xl border border-border bg-card p-5 shadow-sm md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-muted-foreground">Session</label>
                    <SearchableSelect v-model="sessionId" :options="sessionOptions" placeholder="Select session" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-muted-foreground">Class / Section</label>
                    <SearchableSelect v-model="sectionKey" :options="sectionSelectOptions" placeholder="Select class and section" />
                </div>
            </div>

            <div
                v-if="props.sectionOptions.length === 0"
                class="rounded-2xl border border-dashed border-border bg-muted/50 p-10 text-center text-sm text-muted-foreground"
            >
                No classes are available to you yet.
            </div>

            <TimetableGrid v-else :periods="props.periods" :entries="props.entries" :editable="props.canManage" @add="openFor" @edit="openEntry" />
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-[480px]">
                <form class="space-y-4" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>{{ editingId ? 'Edit lesson' : 'Add lesson' }}</DialogTitle>
                        <DialogDescription>A teacher or section cannot be in two lessons at the same time.</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Day</label>
                                <SearchableSelect v-model="form.weekday" :options="weekdays" placeholder="Day" />
                                <p v-if="form.errors.weekday" class="mt-1 text-xs text-destructive">{{ form.errors.weekday }}</p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Period</label>
                                <SearchableSelect v-model="form.period_id" :options="periodOptions" placeholder="Period" />
                                <p v-if="form.errors.period_id" class="mt-1 text-xs text-destructive">{{ form.errors.period_id }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Subject</label>
                            <SearchableSelect v-model="form.subject_id" :options="subjectOptions" placeholder="Select subject" />
                            <p v-if="form.errors.subject_id" class="mt-1 text-xs text-destructive">{{ form.errors.subject_id }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Teacher</label>
                            <SearchableSelect v-model="form.staff_profile_id" :options="teacherOptions" placeholder="Select teacher" />
                            <p v-if="form.errors.staff_profile_id" class="mt-1 text-xs text-destructive">{{ form.errors.staff_profile_id }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Room (optional)</label>
                            <Input v-model="form.room" placeholder="e.g. 12 or Lab 1" maxlength="40" />
                            <p v-if="form.errors.room" class="mt-1 text-xs text-destructive">{{ form.errors.room }}</p>
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <Button v-if="editingId" type="button" variant="destructive" @click="remove">Remove</Button>
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
