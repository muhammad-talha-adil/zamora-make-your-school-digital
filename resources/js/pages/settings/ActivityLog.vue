<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';

interface ActivityRow {
    id: number;
    log_name: string | null;
    description: string;
    event: string | null;
    subject_type: string | null;
    subject_id: number | null;
    causer_name: string | null;
    changes: Record<string, unknown> | null;
    created_at: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    activities: {
        data: ActivityRow[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        subject_type?: string;
        causer_id?: string;
        from?: string;
        to?: string;
    };
    subjectTypes: Record<string, string>;
    users: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Activity Log',
        href: '/settings/activity-log',
    },
];

const subjectType = ref(props.filters.subject_type ?? '');
const causerId = ref(props.filters.causer_id ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

const applyFilters = () => {
    router.get(
        route('activity-log.index'),
        {
            subject_type: subjectType.value || undefined,
            causer_id: causerId.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const resetFilters = () => {
    subjectType.value = '';
    causerId.value = '';
    from.value = '';
    to.value = '';
    router.get(route('activity-log.index'), {}, { preserveState: true, preserveScroll: true });
};

const formatDate = (value: string | null): string => (value ? new Date(value).toLocaleString() : '-');

const describe = (description: string): string => description.charAt(0).toUpperCase() + description.slice(1);

const eventStyles: Record<string, string> = {
    created: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    login: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    updated: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    deleted: 'bg-destructive/10 text-destructive',
};

const eventBadgeClass = (event: string | null): string => eventStyles[event ?? ''] ?? 'bg-muted text-muted-foreground';

const formatValue = (value: unknown): string => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return typeof value === 'object' ? JSON.stringify(value) : String(value);
};

/**
 * `changes` comes from Spatie's `attribute_changes`: `{ attributes, old }` for
 * an update, or just the new values for a create. Turned into readable
 * "field: old -> new" lines instead of the raw JSON blob.
 */
const changeLines = (changes: Record<string, unknown> | null): Array<{ field: string; old?: string; new: string }> => {
    if (!changes) {
        return [];
    }

    const after = (changes.attributes ?? changes) as Record<string, unknown>;
    const before = (changes.old ?? {}) as Record<string, unknown>;

    return Object.keys(after).map((field) => ({
        field,
        old: changes.old ? formatValue(before[field]) : undefined,
        new: formatValue(after[field]),
    }));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Activity Log" />

        <SettingsLayout>
            <div class="space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <HeadingSmall
                        title="Activity Log"
                        description="Who did what, when - across fee, staff, student, exam and inventory records."
                    />
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-destructive/10 px-2.5 py-0.5 text-xs font-medium text-destructive">
                        <Icon icon="shield-alert" class="h-3.5 w-3.5" />
                        Owner / Developer Only
                    </span>
                </div>

                <form @submit.prevent="applyFilters" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="grid gap-2">
                        <Label for="subject_type">Model</Label>
                        <select
                            id="subject_type"
                            v-model="subjectType"
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-primary"
                        >
                            <option value="">All</option>
                            <option v-for="(label, value) in props.subjectTypes" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="causer_id">User</Label>
                        <select
                            id="causer_id"
                            v-model="causerId"
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-primary"
                        >
                            <option value="">All</option>
                            <option v-for="user in props.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="from">From</Label>
                        <input
                            id="from"
                            v-model="from"
                            type="date"
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-primary"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="to">To</Label>
                        <input
                            id="to"
                            v-model="to"
                            type="date"
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-primary"
                        />
                    </div>

                    <div class="flex items-end gap-2">
                        <Button type="submit">Filter</Button>
                        <Button type="button" variant="ghost" @click="resetFilters">Reset</Button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-md border border-border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">When</th>
                                <th class="px-4 py-2">Action</th>
                                <th class="px-4 py-2">Description</th>
                                <th class="px-4 py-2">Record</th>
                                <th class="px-4 py-2">By</th>
                                <th class="px-4 py-2">What changed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="props.activities.data.length === 0">
                                <td colspan="7" class="px-4 py-6 text-center text-muted-foreground">No activity recorded yet.</td>
                            </tr>
                            <tr v-for="(activity, activityIndex) in props.activities.data" :key="activity.id" class="border-t border-border align-top">
                                <td class="whitespace-nowrap px-4 py-2 text-muted-foreground">{{ ((props.activities.from || 1) - 1) + activityIndex + 1 }}</td>
                                <td class="whitespace-nowrap px-4 py-2 text-muted-foreground">{{ formatDate(activity.created_at) }}</td>
                                <td class="whitespace-nowrap px-4 py-2">
                                    <span
                                        v-if="activity.event"
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                        :class="eventBadgeClass(activity.event)"
                                    >
                                        {{ activity.event }}
                                    </span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-2">{{ describe(activity.description) }}</td>
                                <td class="whitespace-nowrap px-4 py-2">
                                    <span v-if="activity.subject_type">{{ activity.subject_type }} #{{ activity.subject_id }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-2">{{ activity.causer_name ?? 'System' }}</td>
                                <td class="px-4 py-2">
                                    <ul v-if="changeLines(activity.changes).length" class="space-y-0.5">
                                        <li v-for="change in changeLines(activity.changes)" :key="change.field" class="text-xs">
                                            <span class="font-medium text-foreground">{{ change.field }}:</span>
                                            <span class="text-muted-foreground">
                                                <template v-if="change.old !== undefined">{{ change.old }} &rarr; </template>{{ change.new }}
                                            </span>
                                        </li>
                                    </ul>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="props.activities.links.length > 3" class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-muted-foreground">
                        Showing {{ props.activities.from ?? 0 }}-{{ props.activities.to ?? 0 }} of {{ props.activities.total }}
                    </p>
                    <div class="flex flex-wrap gap-1">
                        <Button
                            v-for="(link, index) in props.activities.links"
                            :key="index"
                            :disabled="!link.url"
                            :variant="link.active ? 'default' : 'ghost'"
                            size="sm"
                            @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
