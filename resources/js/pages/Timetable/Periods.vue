<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import { Switch } from '@/components/ui/switch';
import type { BreadcrumbItem } from '@/types';

interface PeriodRow {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    sort_order: number;
    is_break: boolean;
    is_active: boolean;
    entries_count: number;
}

interface Props {
    periods: PeriodRow[];
    campuses: { id: number; name: string }[];
    sessions: { id: number; name: string; is_active: boolean }[];
    filters: { campus_id: number | null; session_id: number | null };
    canManage: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Timetable', href: route('timetable.index') },
    { title: 'Periods', href: route('timetable.periods.index') },
];

const campusOptions = computed(() => props.campuses.map((c) => ({ value: String(c.id), label: c.name })));
const sessionOptions = computed(() => props.sessions.map((s) => ({ value: String(s.id), label: s.name })));

const reload = (changes: Record<string, string>) => {
    router.get(
        route('timetable.periods.index'),
        { campus_id: props.filters.campus_id ?? '', session_id: props.filters.session_id ?? '', ...changes },
        { preserveState: true, replace: true },
    );
};

const campusId = computed({
    get: () => (props.filters.campus_id ? String(props.filters.campus_id) : ''),
    set: (value: string) => reload({ campus_id: value }),
});

const sessionId = computed({
    get: () => (props.filters.session_id ? String(props.filters.session_id) : ''),
    set: (value: string) => reload({ session_id: value }),
});

const dialogOpen = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    campus_id: '',
    session_id: '',
    name: '',
    start_time: '',
    end_time: '',
    sort_order: 0,
    is_break: false,
    is_active: true,
});

const openCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.campus_id = String(props.filters.campus_id ?? '');
    form.session_id = String(props.filters.session_id ?? '');
    form.sort_order = props.periods.length + 1;
    dialogOpen.value = true;
};

const openEdit = (period: PeriodRow) => {
    editingId.value = period.id;
    form.clearErrors();
    form.campus_id = String(props.filters.campus_id ?? '');
    form.session_id = String(props.filters.session_id ?? '');
    form.name = period.name;
    form.start_time = period.start_time;
    form.end_time = period.end_time;
    form.sort_order = period.sort_order;
    form.is_break = period.is_break;
    form.is_active = period.is_active;
    dialogOpen.value = true;
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => (dialogOpen.value = false) };

    if (editingId.value) {
        form.put(route('timetable.periods.update', editingId.value), options);
    } else {
        form.post(route('timetable.periods.store'), options);
    }
};

const remove = (period: PeriodRow) => {
    if (!confirm(`Remove "${period.name}"?`)) return;

    router.delete(route('timetable.periods.destroy', period.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Timetable Periods" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Periods</h1>
                    <p class="mt-1 text-sm text-muted-foreground">The slots of the school day, per campus and session.</p>
                </div>
                <div class="flex gap-2">
                    <Button as-child variant="outline">
                        <Link :href="route('timetable.index')">Back to timetable</Link>
                    </Button>
                    <Button v-if="props.canManage" @click="openCreate"><Icon icon="plus" :size="16" class="mr-2" />Add period</Button>
                </div>
            </div>

            <div class="grid gap-4 rounded-2xl border border-border bg-card p-5 shadow-sm md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-muted-foreground">Campus</label>
                    <SearchableSelect v-model="campusId" :options="campusOptions" placeholder="Select campus" :disabled="props.campuses.length <= 1" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-muted-foreground">Session</label>
                    <SearchableSelect v-model="sessionId" :options="sessionOptions" placeholder="Select session" />
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-border bg-card shadow-sm">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Lessons</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr v-for="period in props.periods" :key="period.id" class="hover:bg-accent/50">
                            <td class="px-4 py-3 text-sm text-muted-foreground">{{ period.sort_order }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-foreground">
                                {{ period.name }}
                                <span v-if="!period.is_active" class="ml-2 text-xs text-muted-foreground">(inactive)</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">{{ period.start_time }} - {{ period.end_time }}</td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">{{ period.is_break ? 'Break' : 'Lesson' }}</td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">{{ period.entries_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div v-if="props.canManage" class="flex justify-end gap-2">
                                    <Button size="sm" variant="outline" @click="openEdit(period)">Edit</Button>
                                    <Button size="sm" variant="outline" :disabled="period.entries_count > 0" @click="remove(period)">Remove</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="props.periods.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-muted-foreground">No periods yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-[480px]">
                <form class="space-y-4" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>{{ editingId ? 'Edit period' : 'Add period' }}</DialogTitle>
                        <DialogDescription>Mark a slot as a break to keep lessons out of it.</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-3">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Name</label>
                            <Input v-model="form.name" placeholder="e.g. Period 1" maxlength="60" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Starts</label>
                                <Input v-model="form.start_time" type="time" />
                                <p v-if="form.errors.start_time" class="mt-1 text-xs text-destructive">{{ form.errors.start_time }}</p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Ends</label>
                                <Input v-model="form.end_time" type="time" />
                                <p v-if="form.errors.end_time" class="mt-1 text-xs text-destructive">{{ form.errors.end_time }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Order</label>
                            <Input v-model.number="form.sort_order" type="number" min="0" max="100" />
                        </div>
                        <label class="flex items-center gap-3 text-sm text-foreground">
                            <Switch v-model="form.is_break" />
                            This is a break
                        </label>
                        <p v-if="form.errors.is_break" class="text-xs text-destructive">{{ form.errors.is_break }}</p>
                        <label class="flex items-center gap-3 text-sm text-foreground">
                            <Switch v-model="form.is_active" />
                            In use
                        </label>
                    </div>

                    <DialogFooter>
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
