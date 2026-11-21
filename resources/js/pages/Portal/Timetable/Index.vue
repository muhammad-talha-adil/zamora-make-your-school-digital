<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import PortalLayout from '@/layouts/PortalLayout.vue';
import TimetableGrid, { type TimetableEntryRow, type TimetablePeriodRow } from '@/components/timetable/TimetableGrid.vue';

interface ChildOption {
    id: number;
    name: string | null;
}

interface Props {
    student: ChildOption;
    students: ChildOption[];
    periods: TimetablePeriodRow[];
    entries: TimetableEntryRow[];
}

const props = defineProps<Props>();

const breadcrumbs = [{ title: 'Timetable', href: route('portal.timetable.index') }];
</script>

<template>
    <PortalLayout :breadcrumbs="breadcrumbs" :current-child="props.student" :children="props.students">
        <Head title="Timetable" />
        <div class="space-y-6 animate-fade-slide-up">
            <div>
                <h1 class="text-2xl font-bold text-foreground md:text-3xl">Timetable</h1>
                <p class="mt-1 text-sm text-muted-foreground">The weekly class timetable for {{ props.student.name }}.</p>
            </div>

            <TimetableGrid :periods="props.periods" :entries="props.entries" />
        </div>
    </PortalLayout>
</template>
