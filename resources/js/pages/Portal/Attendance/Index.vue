<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/layouts/PortalLayout.vue';
import Icon from '@/components/Icon.vue';
import { formatDate } from '@/utils/format';

interface ChildOption {
    id: number;
    name: string | null;
}

interface AttendanceRecord {
    id: number;
    check_in: string | null;
    check_out: string | null;
    remarks: string | null;
    attendance?: { attendance_date: string } | null;
    attendanceStatus?: { name: string; code: string } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
}

interface Props {
    student: ChildOption;
    students: ChildOption[];
    records: Paginated<AttendanceRecord>;
}

const props = defineProps<Props>();

const statusClass = (code: string | undefined): string => {
    return (
        {
            P: 'bg-success/10 text-success',
            A: 'bg-destructive/10 text-destructive',
            L: 'bg-primary/10 text-primary',
            LT: 'bg-warning/10 text-warning',
            HD: 'bg-muted text-muted-foreground',
        }[code ?? ''] ?? 'bg-muted text-muted-foreground'
    );
};
</script>

<template>
    <PortalLayout>
        <Head title="My Attendance" />

        <div class="space-y-4 md:space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">Attendance History</h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Day-by-day attendance for {{ props.student.name }}.
                    </p>
                </div>

                <label v-if="props.students.length > 1" class="w-full sm:w-auto">
                    <span class="sr-only">Choose child</span>
                    <select
                        :value="props.student.id"
                        class="w-full sm:w-auto rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                        @change="
                            (event) => router.visit(route('portal.attendance.index', { student_id: (event.target as HTMLSelectElement).value }))
                        "
                    >
                        <option v-for="child in props.students" :key="child.id" :value="child.id">
                            {{ child.name }}
                        </option>
                    </select>
                </label>
            </div>

            <!-- Empty state -->
            <div v-if="props.records.data.length === 0" class="bg-card rounded-lg border border-border p-8 text-center text-muted-foreground">
                <Icon icon="calendar-check" class="h-10 w-10 mx-auto mb-3 text-muted-foreground" />
                No attendance recorded yet.
            </div>

            <template v-else>
                <!-- Mobile Card View -->
                <div class="block lg:hidden space-y-3">
                    <div v-for="record in props.records.data" :key="record.id" class="bg-card rounded-lg border border-border p-4 space-y-3">
                        <div class="flex flex-wrap gap-2 justify-between items-start">
                            <div class="font-medium text-foreground">
                                {{ record.attendance?.attendance_date ? formatDate(record.attendance.attendance_date) : '—' }}
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full shrink-0" :class="statusClass(record.attendanceStatus?.code)">
                                {{ record.attendanceStatus?.name }}
                            </span>
                        </div>

                        <div v-if="record.check_in || record.check_out || record.remarks" class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
                            <div v-if="record.check_in || record.check_out" class="flex items-center justify-between">
                                <span>Check in / out</span>
                                <span class="text-foreground">{{ record.check_in ?? '—' }} / {{ record.check_out ?? '—' }}</span>
                            </div>
                            <div v-if="record.remarks" class="flex items-start gap-2">
                                <Icon icon="message-square" class="h-4 w-4 mt-0.5" />
                                <span>{{ record.remarks }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Check in</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Check out</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="record in props.records.data" :key="record.id" class="transition-colors hover:bg-accent">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">
                                        {{ record.attendance?.attendance_date ? formatDate(record.attendance.attendance_date) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full" :class="statusClass(record.attendanceStatus?.code)">
                                            {{ record.attendanceStatus?.name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ record.check_in ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ record.check_out ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground max-w-xs truncate" :title="record.remarks ?? ''">{{ record.remarks ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <div v-if="props.records.links.length > 3" class="flex flex-wrap gap-1">
                <template v-for="link in props.records.links" :key="`${link.label}-${link.url ?? 'disabled'}`">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded px-3 py-1 text-sm border border-border"
                        :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'text-foreground'"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span v-else class="rounded px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                </template>
            </div>
        </div>
    </PortalLayout>
</template>
