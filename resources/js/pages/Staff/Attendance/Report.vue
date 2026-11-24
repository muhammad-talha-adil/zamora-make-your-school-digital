<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Staff Attendance Report" />

        <div class="space-y-4 p-4 md:space-y-6 md:p-6">
            <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-lg font-bold text-foreground md:text-2xl">Staff Attendance Report</h1>
                    <p class="mt-1 text-xs text-muted-foreground md:text-sm">Present, absent, late and leave days for each member of staff.</p>
                </div>
                <Button as-child variant="outline">
                    <Link :href="route('staff.attendance.page')">Mark Attendance</Link>
                </Button>
            </div>

            <div class="rounded-lg border border-border bg-card p-4 md:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-muted-foreground">Month</label>
                        <SearchableSelect v-model="selectedMonth" :options="monthOptions" placeholder="Select month" @update:model-value="load" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-muted-foreground">Year</label>
                        <SearchableSelect v-model="selectedYear" :options="yearOptions" placeholder="Select year" @update:model-value="load" />
                    </div>
                </div>
            </div>

            <div v-if="rows.length > 0" class="overflow-hidden rounded-lg border border-border bg-card">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">#</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Staff</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-muted-foreground">Present</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-muted-foreground">Absent</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-muted-foreground">Late</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-muted-foreground">Leave</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-muted-foreground">Not marked</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(row, index) in rows" :key="row.id">
                                <td class="px-3 py-3 text-sm text-muted-foreground">{{ index + 1 }}</td>
                                <td class="px-3 py-3 text-sm text-foreground">
                                    <div class="font-medium">{{ row.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ row.employee_no }}<span v-if="row.designation"> &middot; {{ row.designation }}</span></div>
                                </td>
                                <td class="px-3 py-3 text-center text-sm text-foreground">{{ row.present }}</td>
                                <td class="px-3 py-3 text-center text-sm text-foreground">{{ row.absent }}</td>
                                <td class="px-3 py-3 text-center text-sm text-foreground">{{ row.late }}</td>
                                <td class="px-3 py-3 text-center text-sm text-foreground">{{ row.leave }}</td>
                                <td class="px-3 py-3 text-center text-sm text-muted-foreground">{{ row.unmarked_days }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="rounded-lg border border-border bg-card p-6 text-center md:p-8">
                <Icon icon="users" class="mx-auto mb-3 h-10 w-10 text-muted-foreground md:mb-4 md:h-12 md:w-12" />
                <p class="text-sm text-muted-foreground md:text-base">No active staff found.</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import type { BreadcrumbItem } from '@/types';

interface ReportRow {
    id: number;
    name: string;
    employee_no: string;
    designation: string | null;
    present: number;
    absent: number;
    late: number;
    leave: number;
    unmarked_days: number;
}

interface Props {
    rows: ReportRow[];
    month: number;
    year: number;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Attendance Report', href: route('staff.attendance.report') },
];

const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const monthOptions = monthNames.map((name, index) => ({ value: index + 1, label: name }));
const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 6 }, (_, i) => ({ value: currentYear - i, label: String(currentYear - i) }));

const selectedMonth = ref<number>(props.month);
const selectedYear = ref<number>(props.year);

const load = () => {
    router.visit(route('staff.attendance.report'), {
        method: 'get',
        data: { month: selectedMonth.value, year: selectedYear.value },
        preserveState: true,
        replace: true,
    });
};
</script>
