<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';

export interface TimetablePeriodRow {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    is_break: boolean;
}

export interface TimetableEntryRow {
    id: number;
    period_id: number;
    weekday: number;
    class_id: number;
    section_id: number | null;
    subject_id: number;
    staff_profile_id: number;
    subject: string | null;
    teacher: string | null;
    class: string | null;
    section: string | null;
    room: string | null;
}

interface Props {
    periods: TimetablePeriodRow[];
    entries: TimetableEntryRow[];
    /** Empty cells become "add" buttons and filled ones become editable. */
    editable?: boolean;
    /** Show the class/section in each cell instead of the teacher (a teacher's own week). */
    showClass?: boolean;
}

const props = withDefaults(defineProps<Props>(), { editable: false, showClass: false });

const emit = defineEmits<{
    (e: 'add', slot: { weekday: number; period_id: number }): void;
    (e: 'edit', entry: TimetableEntryRow): void;
}>();

const dayNames: Record<number, string> = { 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat', 7: 'Sun' };

// Monday to Saturday always; Sunday only when a lesson is placed on it.
const days = computed(() => {
    const list = [1, 2, 3, 4, 5, 6];
    if (props.entries.some((entry) => entry.weekday === 7)) list.push(7);
    return list;
});

const entriesAt = (weekday: number, periodId: number) =>
    props.entries.filter((entry) => entry.weekday === weekday && entry.period_id === periodId);
</script>

<template>
    <div class="overflow-x-auto rounded-2xl border border-border bg-card shadow-sm">
        <table class="min-w-[720px] w-full border-collapse text-sm">
            <thead class="bg-muted">
                <tr>
                    <th class="w-32 px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Period</th>
                    <th
                        v-for="day in days"
                        :key="day"
                        class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                        {{ dayNames[day] }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr v-for="period in props.periods" :key="period.id" :class="period.is_break ? 'bg-muted/40' : ''">
                    <th scope="row" class="px-3 py-3 text-left align-top font-medium text-foreground">
                        <div>{{ period.name }}</div>
                        <div class="text-xs font-normal text-muted-foreground">{{ period.start_time }} - {{ period.end_time }}</div>
                    </th>

                    <td
                        v-if="period.is_break"
                        :colspan="days.length"
                        class="px-3 py-3 text-center text-xs uppercase tracking-wider text-muted-foreground"
                    >
                        Break
                    </td>

                    <template v-else>
                        <td v-for="day in days" :key="day" class="h-20 min-w-28 px-2 py-2 align-top">
                            <button
                                v-for="entry in entriesAt(day, period.id)"
                                :key="entry.id"
                                type="button"
                                :disabled="!props.editable"
                                class="block w-full rounded-lg border border-primary/20 bg-primary/10 px-2 py-1.5 text-left transition-colors enabled:hover:bg-primary/20 disabled:cursor-default"
                                @click="emit('edit', entry)"
                            >
                                <span class="block font-medium text-foreground">{{ entry.subject }}</span>
                                <span class="block text-xs text-muted-foreground">
                                    {{ props.showClass ? [entry.class, entry.section].filter(Boolean).join(' - ') : entry.teacher }}
                                </span>
                                <span v-if="entry.room" class="block text-xs text-muted-foreground">Room {{ entry.room }}</span>
                            </button>

                            <button
                                v-if="props.editable && entriesAt(day, period.id).length === 0"
                                type="button"
                                :aria-label="`Add lesson on ${dayNames[day]}, ${period.name}`"
                                class="flex h-full min-h-14 w-full items-center justify-center rounded-lg border border-dashed border-border text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                                @click="emit('add', { weekday: day, period_id: period.id })"
                            >
                                <Icon icon="plus" :size="16" />
                            </button>
                        </td>
                    </template>
                </tr>

                <tr v-if="props.periods.length === 0">
                    <td :colspan="days.length + 1" class="px-4 py-10 text-center text-sm text-muted-foreground">
                        No periods have been set up for this campus and session yet.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
