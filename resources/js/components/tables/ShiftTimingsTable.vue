<script setup lang="ts">
import ShiftTimingForm from '@/components/forms/ShiftTimingForm.vue';
import RowAction from '@/components/tables/RowAction.vue';
import RowActions from '@/components/tables/RowActions.vue';
import StatusToggle from '@/components/tables/StatusToggle.vue';
import { alert } from '@/utils';
import { router } from '@inertiajs/vue3';

interface Props {
    shiftTimings: any[];
    campuses: Array<{ id: number; name: string }>;
    classes: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const emit = defineEmits<{ saved: [] }>();

const handleSaved = () => emit('saved');

const classNames = (timing: any) => {
    if (!timing.class_ids || timing.class_ids.length === 0) {
        return 'All classes';
    }
    return props.classes
        .filter((c) => timing.class_ids.includes(c.id))
        .map((c) => c.name)
        .join(', ');
};

const toggleTimingActive = (timing: any) => {
    const actionText = timing.is_active ? 'deactivate' : 'activate';

    alert
        .confirm(`Are you sure you want to ${actionText} "${timing.name}"?`, actionText.charAt(0).toUpperCase() + actionText.slice(1) + ' Shift Timing')
        .then((result) => {
            if (result.isConfirmed) {
                router.post(`/attendance/settings/shift-timings/${timing.id}/toggle-active`, {}, {
                    preserveScroll: true,
                    onSuccess: () => alert.success(`Shift timing ${actionText}d successfully!`),
                    onError: () => alert.error('Failed to update status. Please try again.'),
                });
            }
        });
};

const deleteTiming = (timing: any) => {
    alert.confirm(`Are you sure you want to delete "${timing.name}"?`, 'Delete Shift Timing').then((result) => {
        if (result.isConfirmed) {
            router.delete(`/attendance/settings/shift-timings/${timing.id}`, {
                preserveScroll: true,
                onSuccess: () => alert.success('Shift timing deleted successfully!'),
                onError: () => alert.error('Failed to delete shift timing. Please try again.'),
            });
        }
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <p class="text-sm text-muted-foreground">
                Group classes under a shared check-in/check-out, grace and break time. A class with no timing of its own falls back to the campus's general one.
            </p>
            <ShiftTimingForm :campuses="campuses" :classes="classes" @saved="handleSaved" />
        </div>
        <div class="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Classes</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Check-in / Late After</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Break</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Check-out</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Period</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr v-for="timing in shiftTimings" :key="timing.id" class="transition-colors hover:bg-accent">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">
                                {{ timing.name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground max-w-xs">{{ classNames(timing) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                {{ timing.day_starts_at?.slice(0, 5) }} / {{ timing.late_after?.slice(0, 5) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                <template v-if="timing.break_starts_at">{{ timing.break_starts_at.slice(0, 5) }} – {{ timing.break_ends_at?.slice(0, 5) }}</template>
                                <template v-else>—</template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ timing.day_ends_at?.slice(0, 5) || '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ timing.starts_on }} – {{ timing.ends_on }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <StatusToggle :active="timing.is_active" @toggle="toggleTimingActive(timing)" />
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <RowActions>
                                    <ShiftTimingForm :shift-timing="timing" :campuses="campuses" :classes="classes" @saved="handleSaved" />
                                    <RowAction kind="delete" @click="deleteTiming(timing)" />
                                </RowActions>
                            </td>
                        </tr>
                        <tr v-if="shiftTimings.length === 0">
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-muted-foreground">No shift timings configured yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
