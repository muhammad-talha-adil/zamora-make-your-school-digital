<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import RowAction from '@/components/tables/RowAction.vue';
import RowActions from '@/components/tables/RowActions.vue';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import TransportField from '@/components/transport/TransportField.vue';
import TransportFormDialog from '@/components/transport/TransportFormDialog.vue';
import TransportListHeader from '@/components/transport/TransportListHeader.vue';
import { useClearErrorsOnEdit } from '@/composables/useFormValidity';
import { formatMoney, useTransportForm } from '@/composables/useTransportForm';
import { alert } from '@/utils';
import type { BreadcrumbItem } from '@/types';

interface StudentOption {
    id: number;
    campus_id?: number | null;
    enrollment_id?: number | null;
    display: string;
}

interface RouteOption {
    id: number;
    name: string;
    monthly_fee: number | string;
    stops: { id: number; name: string }[];
}

interface AssignmentRow {
    id: number;
    student_id: number;
    student_enrollment_record_id?: number | null;
    campus_id?: number | null;
    transport_route_id: number;
    transport_stop_id?: number | null;
    monthly_fee: number | string;
    effective_from: string;
    effective_to?: string | null;
    status: string;
    generate_dues: boolean;
    student?: { name?: string; registration_no?: string | null; user?: { name: string } | null } | null;
    route?: { name: string } | null;
    stop?: { name: string } | null;
}

const props = defineProps<{
    assignments: AssignmentRow[];
    routes: RouteOption[];
    students: StudentOption[];
    preselectStudentId?: number | null;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Students', href: '/students' },
    { title: 'Transport Assignment', href: '/students/transport' },
];

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'paused', label: 'Paused' },
    { value: 'ended', label: 'Ended' },
];

const search = ref('');
const dialogOpen = ref(false);
const { fieldErrors, submitting, validateRequired, save, axios } = useTransportForm();

const blankForm = () => ({
    id: null as number | null,
    student_id: '',
    student_enrollment_record_id: '',
    campus_id: '',
    transport_route_id: '',
    transport_stop_id: '',
    monthly_fee: '',
    effective_from: new Date().toISOString().slice(0, 10),
    effective_to: '',
    status: 'active',
    generate_dues: true,
});
const form = reactive(blankForm());
useClearErrorsOnEdit(form, fieldErrors);

const studentOptions = computed(() => props.students.map((student) => ({ value: String(student.id), label: student.display })));
const routeOptions = computed(() => props.routes.map((row) => ({ value: String(row.id), label: row.name })));
const stopOptions = computed(() => {
    const selected = props.routes.find((row) => String(row.id) === form.transport_route_id);

    return (selected?.stops ?? []).map((stop) => ({ value: String(stop.id), label: stop.name }));
});

const studentName = (row: AssignmentRow) => row.student?.user?.name ?? row.student?.name ?? '-';

const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.assignments.filter(
        (row) =>
            !query ||
            [studentName(row), row.student?.registration_no, row.route?.name, row.stop?.name].some((value) => String(value ?? '').toLowerCase().includes(query)),
    );
});

watch(
    () => form.student_id,
    (studentId) => {
        const student = props.students.find((row) => String(row.id) === studentId);

        if (student) {
            form.campus_id = student.campus_id ? String(student.campus_id) : '';
            form.student_enrollment_record_id = student.enrollment_id ? String(student.enrollment_id) : '';
        }
    },
);

watch(
    () => form.transport_route_id,
    (routeId) => {
        const selected = props.routes.find((row) => String(row.id) === routeId);

        if (selected && !form.id) {
            form.monthly_fee = String(selected.monthly_fee ?? '');
            form.transport_stop_id = '';
        }
    },
);

const openAdd = (studentId?: number | null) => {
    Object.assign(form, blankForm());
    if (studentId) {
        form.student_id = String(studentId);
    }
    fieldErrors.value = {};
    dialogOpen.value = true;
};

const openEdit = (row: AssignmentRow) => {
    Object.assign(form, {
        id: row.id,
        student_id: String(row.student_id),
        student_enrollment_record_id: row.student_enrollment_record_id ? String(row.student_enrollment_record_id) : '',
        campus_id: row.campus_id ? String(row.campus_id) : '',
        transport_route_id: String(row.transport_route_id),
        transport_stop_id: row.transport_stop_id ? String(row.transport_stop_id) : '',
        monthly_fee: String(row.monthly_fee ?? ''),
        effective_from: row.effective_from.slice(0, 10),
        effective_to: row.effective_to ? row.effective_to.slice(0, 10) : '',
        status: row.status,
        generate_dues: row.generate_dues,
    });
    fieldErrors.value = {};
    dialogOpen.value = true;
};

onMounted(() => {
    if (props.preselectStudentId) {
        const current = props.assignments.find((row) => row.student_id === props.preselectStudentId && row.status === 'active');

        if (current) {
            openEdit(current);
        } else {
            openAdd(props.preselectStudentId);
        }
    }
});

const submit = async () => {
    if (
        await validateRequired(form, ['student_id', 'transport_route_id', 'monthly_fee', 'effective_from'], {
            student_id: 'Student',
            transport_route_id: 'Route',
            monthly_fee: 'Fee',
            effective_from: 'Start date',
        })
    ) {
        return;
    }

    const payload = {
        ...form,
        student_enrollment_record_id: form.student_enrollment_record_id || null,
        campus_id: form.campus_id || null,
        transport_stop_id: form.transport_stop_id || null,
        effective_to: form.effective_to || null,
    };

    const saved = await save(
        () => (form.id ? axios.put(route('transport.assignments.update', form.id), payload) : axios.post(route('transport.assignments.store'), payload)),
        'Could not save the assignment.',
    );

    if (saved) {
        alert.success(form.id ? 'Assignment updated.' : 'Student assigned to route.');
        dialogOpen.value = false;
        router.reload({ only: ['assignments'], preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Transport Assignment" />

        <div class="space-y-6 p-4 md:p-6">
            <TransportListHeader
                v-model:search="search"
                title="Transport Assignment"
                description="Which student rides which route, and what they pay each month."
                add-label="Assign Student"
                search-placeholder="Search by student, route or stop..."
                @add="openAdd()"
            />

            <div class="overflow-x-auto rounded-2xl border border-border bg-card shadow-sm">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="w-10 px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Student</th>
                            <th class="px-4 py-3 font-medium">Route / Stop</th>
                            <th class="px-4 py-3 text-right font-medium">Monthly fee</th>
                            <th class="px-4 py-3 font-medium">From</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, idx) in filtered" :key="row.id" class="border-t border-border">
                            <td class="px-4 py-3 text-muted-foreground">{{ idx + 1 }}</td>
                            <td class="px-4 py-3 text-foreground">
                                <span class="font-medium">{{ studentName(row) }}</span>
                                <span class="block text-xs text-muted-foreground">{{ row.student?.registration_no }}</span>
                            </td>
                            <td class="px-4 py-3 text-foreground">{{ row.route?.name || '-' }}{{ row.stop ? ' / ' + row.stop.name : '' }}</td>
                            <td class="px-4 py-3 text-right text-foreground">{{ formatMoney(row.monthly_fee) }}</td>
                            <td class="px-4 py-3 text-foreground">{{ row.effective_from.slice(0, 10) }}</td>
                            <td class="px-4 py-3 capitalize text-foreground">{{ row.status }}</td>
                            <td class="px-4 py-3">
                                <RowActions><RowAction kind="edit" @click="openEdit(row)" /></RowActions>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No assignments found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <TransportFormDialog
            v-model:open="dialogOpen"
            :title="form.id ? 'Edit Assignment' : 'Assign Student'"
            :submitting="submitting"
            @submit="submit"
        >
            <TransportField label="Student" required :error="fieldErrors.student_id">
                <SearchableSelect v-model="form.student_id" :options="studentOptions" placeholder="Pick a student" :disabled="!!form.id" />
            </TransportField>
            <div class="grid gap-4 sm:grid-cols-2">
                <TransportField label="Route" required :error="fieldErrors.transport_route_id">
                    <SearchableSelect v-model="form.transport_route_id" :options="routeOptions" placeholder="Pick a route" />
                </TransportField>
                <TransportField label="Stop" :error="fieldErrors.transport_stop_id">
                    <SearchableSelect v-model="form.transport_stop_id" :options="stopOptions" placeholder="Pick a stop" clearable />
                </TransportField>
                <TransportField label="Monthly fee" required :error="fieldErrors.monthly_fee">
                    <Input v-model="form.monthly_fee" type="number" min="0"  placeholder="e.g. 3000" />
                </TransportField>
                <TransportField label="Start date" required :error="fieldErrors.effective_from">
                    <Input v-model="form.effective_from" type="date" />
                </TransportField>
                <TransportField v-if="form.id" label="End date" :error="fieldErrors.effective_to">
                    <Input v-model="form.effective_to" type="date" />
                </TransportField>
                <TransportField v-if="form.id" label="Status" :error="fieldErrors.status">
                    <SearchableSelect v-model="form.status" :options="statusOptions" placeholder="Status" />
                </TransportField>
            </div>
        </TransportFormDialog>
    </AppLayout>
</template>
