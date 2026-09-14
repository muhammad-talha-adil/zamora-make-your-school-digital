<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

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
    attendance_status?: { name: string; code: string } | null;
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
</script>

<template>
    <AppLayout>
        <Head title="My Attendance" />

        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold">Attendance History — {{ props.student.name }}</h1>

                <select
                    v-if="props.students.length > 1"
                    :value="props.student.id"
                    class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-800"
                    @change="
                        (event) =>
                            router.visit(route('portal.attendance.index', { student_id: (event.target as HTMLSelectElement).value }))
                    "
                >
                    <option v-for="child in props.students" :key="child.id" :value="child.id">
                        {{ child.name }}
                    </option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="p-2">Date</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">Check in</th>
                            <th class="p-2">Check out</th>
                            <th class="p-2">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="record in props.records.data" :key="record.id" class="border-b border-gray-100 dark:border-gray-800">
                            <td class="p-2">{{ record.attendance?.attendance_date }}</td>
                            <td class="p-2">{{ record.attendance_status?.name }}</td>
                            <td class="p-2">{{ record.check_in }}</td>
                            <td class="p-2">{{ record.check_out }}</td>
                            <td class="p-2">{{ record.remarks }}</td>
                        </tr>
                    </tbody>
                </table>

                <p v-if="props.records.data.length === 0" class="py-8 text-center text-gray-500">No attendance recorded yet.</p>
            </div>

            <div v-if="props.records.links.length" class="flex flex-wrap gap-1">
                <template v-for="link in props.records.links" :key="`${link.label}-${link.url ?? 'disabled'}`">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-blue-600 text-white' : 'border border-gray-300 dark:border-gray-700'"
                        v-html="link.label"
                    />
                    <span v-else class="rounded px-3 py-1 text-sm text-gray-400" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
