<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Mark Staff Attendance" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">Mark Staff Attendance</h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">Record attendance for every active member of staff in one go.</p>
                </div>
            </div>

            <!-- Date + bulk actions -->
            <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                <div class="flex flex-col sm:flex-row gap-4 sm:items-end">
                    <div class="w-full sm:w-56">
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Date</label>
                        <input
                            v-model="selectedDate"
                            type="date"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            @change="loadForDate"
                        />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" @click="markAllByCode('P')">Mark All Present</Button>
                        <Button variant="outline" size="sm" @click="markAllByCode('A')">Mark All Absent</Button>
                        <Button variant="outline" size="sm" @click="markAllByCode('L')">Mark All Leave</Button>
                    </div>
                </div>
            </div>

            <!-- Staff Table -->
            <div v-if="props.staff.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
                <div class="overflow-x-auto -mx-4 md:mx-0">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase w-12 md:w-16">Sr#</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Staff</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase hidden md:table-cell">Designation</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase hidden md:table-cell">Campus</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase hidden sm:table-cell">Check In</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase hidden sm:table-cell">Check Out</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase hidden lg:table-cell">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(row, index) in rows" :key="row.staff_profile_id">
                                <td class="px-2 md:px-4 py-2 md:py-3 text-sm text-muted-foreground">{{ index + 1 }}</td>
                                <td class="px-2 md:px-4 py-2 md:py-3 text-sm text-foreground">
                                    <div class="font-medium">{{ row.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ row.employee_no }}</div>
                                </td>
                                <td class="px-2 md:px-4 py-2 md:py-3 text-sm text-muted-foreground hidden md:table-cell">{{ row.designation }}</td>
                                <td class="px-2 md:px-4 py-2 md:py-3 text-sm text-muted-foreground hidden md:table-cell">{{ row.campus }}</td>
                                <td class="px-2 md:px-4 py-2 md:py-3">
                                    <select v-model="row.attendance_status_id" class="w-full min-w-32 rounded-md border border-border bg-card text-foreground px-2 py-1.5 text-sm">
                                        <option v-for="status in props.statuses" :key="status.id" :value="status.id">{{ status.name }}</option>
                                    </select>
                                </td>
                                <td class="px-2 md:px-4 py-2 md:py-3 hidden sm:table-cell">
                                    <input v-model="row.check_in_at" type="time" class="w-full rounded-md border border-border bg-card text-foreground px-2 py-1.5 text-sm" />
                                </td>
                                <td class="px-2 md:px-4 py-2 md:py-3 hidden sm:table-cell">
                                    <input v-model="row.check_out_at" type="time" class="w-full rounded-md border border-border bg-card text-foreground px-2 py-1.5 text-sm" />
                                </td>
                                <td class="px-2 md:px-4 py-2 md:py-3 hidden lg:table-cell">
                                    <input v-model="row.remarks" type="text" placeholder="Remarks" class="w-full rounded-md border border-border bg-card text-foreground px-2 py-1.5 text-sm" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Submit Button -->
                <div class="p-4 border-t border-border">
                    <Button @click="submitAttendance" :disabled="isSubmitting" class="w-full sm:w-auto">
                        <Icon v-if="isSubmitting" icon="loader" class="mr-1 animate-spin" />
                        {{ isSubmitting ? 'Submitting...' : 'Submit Attendance' }}
                    </Button>
                </div>
            </div>

            <!-- No Staff Message -->
            <div v-else class="bg-card rounded-lg border border-border p-6 md:p-8 text-center">
                <Icon icon="users" class="h-10 w-10 md:h-12 md:w-12 text-muted-foreground mx-auto mb-3 md:mb-4" />
                <p class="text-sm md:text-base text-muted-foreground">No active staff found.</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { alert } from '@/utils';
import type { BreadcrumbItem } from '@/types';

interface StaffStatus {
    id: number;
    name: string;
    code: string;
}

interface StaffRow {
    id: number;
    employee_no: string;
    user?: { id: number; name: string } | null;
    campus?: { id: number; name: string } | null;
    designation?: { id: number; name: string } | null;
}

interface Props {
    staff: StaffRow[];
    date: string;
    statuses: StaffStatus[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Mark Attendance', href: route('staff.attendance.page') },
];

const selectedDate = ref<string>(props.date);
const isSubmitting = ref(false);

interface RowFormData {
    staff_profile_id: number;
    name: string;
    employee_no: string;
    designation: string;
    campus: string;
    attendance_status_id: number | '';
    check_in_at: string;
    check_out_at: string;
    remarks: string;
}

const defaultStatusId = () => {
    const present = props.statuses.find((s) => s.code === 'P');
    return present ? present.id : (props.statuses[0]?.id ?? '');
};

const buildRows = (staff: StaffRow[]): RowFormData[] =>
    staff.map((member) => ({
        staff_profile_id: member.id,
        name: member.user?.name ?? member.employee_no,
        employee_no: member.employee_no,
        designation: member.designation?.name ?? '-',
        campus: member.campus?.name ?? '-',
        attendance_status_id: defaultStatusId(),
        check_in_at: '',
        check_out_at: '',
        remarks: '',
    }));

const rows = reactive<RowFormData[]>(buildRows(props.staff));

watch(
    () => props.staff,
    (newStaff) => {
        rows.splice(0, rows.length, ...buildRows(newStaff));
    },
);

const markAllByCode = (code: string) => {
    const status = props.statuses.find((s) => s.code === code);
    if (!status) return;
    rows.forEach((row) => {
        row.attendance_status_id = status.id;
    });
};

const loadForDate = () => {
    router.visit(route('staff.attendance.page'), {
        method: 'get',
        data: { date: selectedDate.value },
        preserveState: true,
        replace: true,
    });
};

const submitAttendance = async () => {
    isSubmitting.value = true;
    try {
        await axios.post(route('staff.attendance.bulk'), {
            attendance_date: selectedDate.value,
            rows: rows.map((row) => ({
                staff_profile_id: row.staff_profile_id,
                attendance_status_id: row.attendance_status_id,
                check_in_at: row.check_in_at || null,
                check_out_at: row.check_out_at || null,
                remarks: row.remarks || null,
            })),
        });
        alert.success('Attendance marked.');
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to mark attendance.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>
