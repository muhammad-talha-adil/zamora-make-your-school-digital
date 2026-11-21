<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import TimetableGrid, { type TimetableEntryRow, type TimetablePeriodRow } from '@/components/timetable/TimetableGrid.vue';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import type { BreadcrumbItem } from '@/types';

interface Props {
    sessions: { id: number; name: string | null; is_active: boolean }[];
    filters: { session_id: number | null };
    periods: TimetablePeriodRow[];
    entries: TimetableEntryRow[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Timetable', href: route('timetable.mine') },
];

const sessionOptions = computed(() => props.sessions.map((s) => ({ value: String(s.id), label: s.name ?? '' })));

const sessionId = computed({
    get: () => (props.filters.session_id ? String(props.filters.session_id) : ''),
    set: (value: string) => router.get(route('timetable.mine'), { session_id: value }, { preserveState: true, replace: true }),
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="My Timetable" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">My Timetable</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Your weekly lessons across every class you teach.</p>
                </div>
                <div class="w-full sm:w-64">
                    <SearchableSelect v-model="sessionId" :options="sessionOptions" placeholder="Select session" />
                </div>
            </div>

            <TimetableGrid :periods="props.periods" :entries="props.entries" show-class />
        </div>
    </AppLayout>
</template>
