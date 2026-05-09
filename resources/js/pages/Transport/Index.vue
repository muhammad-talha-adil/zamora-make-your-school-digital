<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { alert } from '@/utils';
import { tableActionButtonClass } from '@/utils/table-actions';
import type { BreadcrumbItem } from '@/types';

interface Campus {
    id: number;
    name: string;
}

interface MonthOption {
    id: number;
    name: string;
}

interface StudentOption {
    id: number;
    name: string;
    registration_no?: string | null;
    campus_id?: number | null;
    enrollment_id?: number | null;
    display: string;
}

interface TransportVehicleRow {
    id: number;
    campus_id?: number | null;
    vehicle_no: string;
    vehicle_type: string;
    capacity: number;
    driver_name?: string | null;
    attendant_name?: string | null;
    status: string;
    purchase_date?: string | null;
    is_active: boolean;
    notes?: string | null;
    campus?: {
        id: number;
        name: string;
    } | null;
}

interface TransportStopRow {
    id: number;
    campus_id?: number | null;
    name: string;
    pickup_time?: string | null;
    drop_time?: string | null;
    is_active: boolean;
    campus?: {
        id: number;
        name: string;
    } | null;
}

interface TransportRouteRow {
    id: number;
    campus_id?: number | null;
    transport_vehicle_id?: number | null;
    name: string;
    monthly_fee: number | string;
    is_active: boolean;
    notes?: string | null;
    campus?: {
        id: number;
        name: string;
    } | null;
    vehicle?: {
        id: number;
        vehicle_no: string;
    } | null;
    stops: TransportStopRow[];
}

interface TransportAssignmentRow {
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
    student?: {
        id: number;
        name: string;
        registration_no?: string | null;
        user?: {
            id: number;
            name: string;
        } | null;
    } | null;
    route?: {
        id: number;
        name: string;
        monthly_fee: number | string;
    } | null;
    stop?: {
        id: number;
        name: string;
    } | null;
    campus?: {
        id: number;
        name: string;
    } | null;
}

interface TransportExpenseRow {
    id: number;
    campus_id?: number | null;
    transport_vehicle_id?: number | null;
    expense_type: string;
    expense_date: string;
    amount: number | string;
    payment_method: string;
    reference_no?: string | null;
    description?: string | null;
    vehicle?: {
        id: number;
        vehicle_no: string;
    } | null;
    campus?: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    vehicles: TransportVehicleRow[];
    routes: TransportRouteRow[];
    stops: TransportStopRow[];
    assignments: TransportAssignmentRow[];
    expenses: TransportExpenseRow[];
    campuses: Campus[];
    students: StudentOption[];
    months: MonthOption[];
    summary: {
        active_vehicles: number;
        active_routes: number;
        active_assignments: number;
        this_month_expense: number;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transport', href: '/transport' },
];

const selectClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white';
const textareaClass = 'min-h-24 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white';

const activeTab = ref<'vehicles' | 'routes' | 'stops' | 'assignments' | 'expenses' | 'dues'>('vehicles');
const searchQuery = ref('');
const campusFilter = ref('');

const vehicleForm = reactive({
    id: null as number | null,
    campus_id: '',
    vehicle_no: '',
    vehicle_type: 'van',
    capacity: '0',
    driver_name: '',
    attendant_name: '',
    status: 'active',
    purchase_date: '',
    notes: '',
    is_active: true,
});

const stopForm = reactive({
    id: null as number | null,
    campus_id: '',
    name: '',
    pickup_time: '',
    drop_time: '',
    is_active: true,
});

const routeForm = reactive({
    id: null as number | null,
    campus_id: '',
    transport_vehicle_id: '',
    name: '',
    monthly_fee: '',
    stop_ids: [] as string[],
    notes: '',
    is_active: true,
});

const assignmentForm = reactive({
    id: null as number | null,
    student_id: '',
    student_enrollment_record_id: '',
    campus_id: '',
    transport_route_id: '',
    transport_stop_id: '',
    monthly_fee: '',
    effective_from: '',
    effective_to: '',
    status: 'active',
    generate_dues: true,
});

const expenseForm = reactive({
    id: null as number | null,
    campus_id: '',
    transport_vehicle_id: '',
    expense_type: 'fuel',
    expense_date: '',
    amount: '',
    payment_method: 'cash',
    reference_no: '',
    description: '',
});

const duesForm = reactive({
    campus_id: '',
    month_id: props.months.find((month) => month.id === new Date().getMonth() + 1)?.id?.toString() ?? '',
    year: String(new Date().getFullYear()),
    due_date: '',
});

const formatMoney = (amount: number | string | null | undefined) => {
    const value = Number(amount || 0);

    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(value);
};

const formatDate = (value?: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const filteredVehicles = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.vehicles.filter((vehicle) => {
        const matchesCampus = !campusFilter.value || String(vehicle.campus_id ?? '') === campusFilter.value;
        const matchesQuery = !query || [
            vehicle.vehicle_no,
            vehicle.vehicle_type,
            vehicle.driver_name,
            vehicle.attendant_name,
            vehicle.campus?.name,
        ].some((value) => String(value ?? '').toLowerCase().includes(query));

        return matchesCampus && matchesQuery;
    });
});

const filteredStops = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.stops.filter((stop) => {
        const matchesCampus = !campusFilter.value || String(stop.campus_id ?? '') === campusFilter.value;
        const matchesQuery = !query || [
            stop.name,
            stop.campus?.name,
        ].some((value) => String(value ?? '').toLowerCase().includes(query));

        return matchesCampus && matchesQuery;
    });
});

const filteredRoutes = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.routes.filter((routeRow) => {
        const matchesCampus = !campusFilter.value || String(routeRow.campus_id ?? '') === campusFilter.value;
        const matchesQuery = !query || [
            routeRow.name,
            routeRow.vehicle?.vehicle_no,
            routeRow.campus?.name,
            ...routeRow.stops.map((stop) => stop.name),
        ].some((value) => String(value ?? '').toLowerCase().includes(query));

        return matchesCampus && matchesQuery;
    });
});

const filteredAssignments = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.assignments.filter((assignment) => {
        const matchesCampus = !campusFilter.value || String(assignment.campus_id ?? '') === campusFilter.value;
        const matchesQuery = !query || [
            assignment.student?.user?.name,
            assignment.student?.registration_no,
            assignment.route?.name,
            assignment.stop?.name,
            assignment.campus?.name,
        ].some((value) => String(value ?? '').toLowerCase().includes(query));

        return matchesCampus && matchesQuery;
    });
});

const filteredExpenses = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.expenses.filter((expense) => {
        const matchesCampus = !campusFilter.value || String(expense.campus_id ?? '') === campusFilter.value;
        const matchesQuery = !query || [
            expense.expense_type,
            expense.reference_no,
            expense.description,
            expense.vehicle?.vehicle_no,
            expense.campus?.name,
        ].some((value) => String(value ?? '').toLowerCase().includes(query));

        return matchesCampus && matchesQuery;
    });
});

const filteredStudents = computed(() => {
    if (!campusFilter.value) {
        return props.students;
    }

    return props.students.filter((student) => String(student.campus_id ?? '') === campusFilter.value);
});

const availableStops = computed(() => {
    if (!routeForm.campus_id) {
        return props.stops;
    }

    return props.stops.filter((stop) => String(stop.campus_id ?? '') === routeForm.campus_id);
});

const availableRouteStops = computed(() => {
    const selectedRoute = props.routes.find((routeRow) => String(routeRow.id) === assignmentForm.transport_route_id);

    return selectedRoute?.stops ?? [];
});

const reloadPage = (only: string[] = ['vehicles', 'routes', 'stops', 'assignments', 'expenses', 'summary']) => {
    router.reload({
        only,
        preserveScroll: true,
        preserveState: true,
    });
};

const resetVehicleForm = () => {
    vehicleForm.id = null;
    vehicleForm.campus_id = '';
    vehicleForm.vehicle_no = '';
    vehicleForm.vehicle_type = 'van';
    vehicleForm.capacity = '0';
    vehicleForm.driver_name = '';
    vehicleForm.attendant_name = '';
    vehicleForm.status = 'active';
    vehicleForm.purchase_date = '';
    vehicleForm.notes = '';
    vehicleForm.is_active = true;
};

const resetStopForm = () => {
    stopForm.id = null;
    stopForm.campus_id = '';
    stopForm.name = '';
    stopForm.pickup_time = '';
    stopForm.drop_time = '';
    stopForm.is_active = true;
};

const resetRouteForm = () => {
    routeForm.id = null;
    routeForm.campus_id = '';
    routeForm.transport_vehicle_id = '';
    routeForm.name = '';
    routeForm.monthly_fee = '';
    routeForm.stop_ids = [];
    routeForm.notes = '';
    routeForm.is_active = true;
};

const resetAssignmentForm = () => {
    assignmentForm.id = null;
    assignmentForm.student_id = '';
    assignmentForm.student_enrollment_record_id = '';
    assignmentForm.campus_id = '';
    assignmentForm.transport_route_id = '';
    assignmentForm.transport_stop_id = '';
    assignmentForm.monthly_fee = '';
    assignmentForm.effective_from = '';
    assignmentForm.effective_to = '';
    assignmentForm.status = 'active';
    assignmentForm.generate_dues = true;
};

const resetExpenseForm = () => {
    expenseForm.id = null;
    expenseForm.campus_id = '';
    expenseForm.transport_vehicle_id = '';
    expenseForm.expense_type = 'fuel';
    expenseForm.expense_date = '';
    expenseForm.amount = '';
    expenseForm.payment_method = 'cash';
    expenseForm.reference_no = '';
    expenseForm.description = '';
};

const editVehicle = (vehicle: TransportVehicleRow) => {
    activeTab.value = 'vehicles';
    vehicleForm.id = vehicle.id;
    vehicleForm.campus_id = vehicle.campus_id ? String(vehicle.campus_id) : '';
    vehicleForm.vehicle_no = vehicle.vehicle_no;
    vehicleForm.vehicle_type = vehicle.vehicle_type;
    vehicleForm.capacity = String(vehicle.capacity ?? 0);
    vehicleForm.driver_name = vehicle.driver_name ?? '';
    vehicleForm.attendant_name = vehicle.attendant_name ?? '';
    vehicleForm.status = vehicle.status;
    vehicleForm.purchase_date = vehicle.purchase_date ?? '';
    vehicleForm.notes = vehicle.notes ?? '';
    vehicleForm.is_active = vehicle.is_active;
};

const editStop = (stop: TransportStopRow) => {
    activeTab.value = 'stops';
    stopForm.id = stop.id;
    stopForm.campus_id = stop.campus_id ? String(stop.campus_id) : '';
    stopForm.name = stop.name;
    stopForm.pickup_time = stop.pickup_time ?? '';
    stopForm.drop_time = stop.drop_time ?? '';
    stopForm.is_active = stop.is_active;
};

const editRoute = (routeRow: TransportRouteRow) => {
    activeTab.value = 'routes';
    routeForm.id = routeRow.id;
    routeForm.campus_id = routeRow.campus_id ? String(routeRow.campus_id) : '';
    routeForm.transport_vehicle_id = routeRow.transport_vehicle_id ? String(routeRow.transport_vehicle_id) : '';
    routeForm.name = routeRow.name;
    routeForm.monthly_fee = String(routeRow.monthly_fee ?? '');
    routeForm.stop_ids = routeRow.stops.map((stop) => String(stop.id));
    routeForm.notes = routeRow.notes ?? '';
    routeForm.is_active = routeRow.is_active;
};

const editAssignment = (assignment: TransportAssignmentRow) => {
    activeTab.value = 'assignments';
    assignmentForm.id = assignment.id;
    assignmentForm.student_id = String(assignment.student_id);
    assignmentForm.student_enrollment_record_id = assignment.student_enrollment_record_id ? String(assignment.student_enrollment_record_id) : '';
    assignmentForm.campus_id = assignment.campus_id ? String(assignment.campus_id) : '';
    assignmentForm.transport_route_id = String(assignment.transport_route_id);
    assignmentForm.transport_stop_id = assignment.transport_stop_id ? String(assignment.transport_stop_id) : '';
    assignmentForm.monthly_fee = String(assignment.monthly_fee ?? '');
    assignmentForm.effective_from = assignment.effective_from ?? '';
    assignmentForm.effective_to = assignment.effective_to ?? '';
    assignmentForm.status = assignment.status;
    assignmentForm.generate_dues = assignment.generate_dues;
};

const editExpense = (expense: TransportExpenseRow) => {
    activeTab.value = 'expenses';
    expenseForm.id = expense.id;
    expenseForm.campus_id = expense.campus_id ? String(expense.campus_id) : '';
    expenseForm.transport_vehicle_id = expense.transport_vehicle_id ? String(expense.transport_vehicle_id) : '';
    expenseForm.expense_type = expense.expense_type;
    expenseForm.expense_date = expense.expense_date ?? '';
    expenseForm.amount = String(expense.amount ?? '');
    expenseForm.payment_method = expense.payment_method;
    expenseForm.reference_no = expense.reference_no ?? '';
    expenseForm.description = expense.description ?? '';
};

watch(
    () => assignmentForm.student_id,
    (studentId) => {
        const student = props.students.find((row) => String(row.id) === studentId);

        if (student) {
            assignmentForm.campus_id = student.campus_id ? String(student.campus_id) : assignmentForm.campus_id;
            assignmentForm.student_enrollment_record_id = student.enrollment_id ? String(student.enrollment_id) : assignmentForm.student_enrollment_record_id;
        }
    },
);

watch(
    () => assignmentForm.transport_route_id,
    (routeId) => {
        const selectedRoute = props.routes.find((row) => String(row.id) === routeId);

        if (selectedRoute) {
            assignmentForm.monthly_fee = String(selectedRoute.monthly_fee ?? 0);
            if (!assignmentForm.transport_stop_id && selectedRoute.stops[0]) {
                assignmentForm.transport_stop_id = String(selectedRoute.stops[0].id);
            }
        }
    },
);

const submitVehicle = async () => {
    try {
        const payload = {
            ...vehicleForm,
            campus_id: vehicleForm.campus_id || null,
            driver_name: vehicleForm.driver_name || null,
            attendant_name: vehicleForm.attendant_name || null,
            purchase_date: vehicleForm.purchase_date || null,
            notes: vehicleForm.notes || null,
        };

        if (vehicleForm.id) {
            await axios.put(route('transport.vehicles.update', vehicleForm.id), payload);
            alert.success('Vehicle updated successfully.');
        } else {
            await axios.post(route('transport.vehicles.store'), payload);
            alert.success('Vehicle created successfully.');
        }

        resetVehicleForm();
        reloadPage(['vehicles', 'summary']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save vehicle.');
    }
};

const submitStop = async () => {
    try {
        const payload = {
            ...stopForm,
            campus_id: stopForm.campus_id || null,
            pickup_time: stopForm.pickup_time || null,
            drop_time: stopForm.drop_time || null,
        };

        if (stopForm.id) {
            await axios.put(route('transport.stops.update', stopForm.id), payload);
            alert.success('Stop updated successfully.');
        } else {
            await axios.post(route('transport.stops.store'), payload);
            alert.success('Stop created successfully.');
        }

        resetStopForm();
        reloadPage(['stops', 'routes']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save stop.');
    }
};

const submitRoute = async () => {
    try {
        const payload = {
            ...routeForm,
            campus_id: routeForm.campus_id || null,
            transport_vehicle_id: routeForm.transport_vehicle_id || null,
            stop_ids: routeForm.stop_ids.map((id) => Number(id)),
            notes: routeForm.notes || null,
        };

        if (routeForm.id) {
            await axios.put(route('transport.routes.update', routeForm.id), payload);
            alert.success('Route updated successfully.');
        } else {
            await axios.post(route('transport.routes.store'), payload);
            alert.success('Route created successfully.');
        }

        resetRouteForm();
        reloadPage(['routes', 'summary']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save route.');
    }
};

const submitAssignment = async () => {
    try {
        const payload = {
            ...assignmentForm,
            student_enrollment_record_id: assignmentForm.student_enrollment_record_id || null,
            campus_id: assignmentForm.campus_id || null,
            transport_stop_id: assignmentForm.transport_stop_id || null,
            effective_to: assignmentForm.effective_to || null,
        };

        if (assignmentForm.id) {
            await axios.put(route('transport.assignments.update', assignmentForm.id), payload);
            alert.success('Assignment updated successfully.');
        } else {
            await axios.post(route('transport.assignments.store'), payload);
            alert.success('Assignment created successfully.');
        }

        resetAssignmentForm();
        reloadPage(['assignments', 'summary']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save assignment.');
    }
};

const submitExpense = async () => {
    try {
        const payload = {
            ...expenseForm,
            campus_id: expenseForm.campus_id || null,
            transport_vehicle_id: expenseForm.transport_vehicle_id || null,
            reference_no: expenseForm.reference_no || null,
            description: expenseForm.description || null,
        };

        if (expenseForm.id) {
            await axios.put(route('transport.expenses.update', expenseForm.id), payload);
            alert.success('Transport expense updated successfully.');
        } else {
            await axios.post(route('transport.expenses.store'), payload);
            alert.success('Transport expense recorded successfully.');
        }

        resetExpenseForm();
        reloadPage(['expenses', 'summary']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save transport expense.');
    }
};

const generateDues = async () => {
    try {
        const response = await axios.post(route('transport.generate-dues'), {
            campus_id: duesForm.campus_id || null,
            month_id: duesForm.month_id,
            year: duesForm.year,
            due_date: duesForm.due_date || null,
        });

        alert.success(response.data?.message || 'Transport dues generated successfully.');
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to generate transport dues.');
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Transport Management" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Transport Management</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage vehicles, routes, stops, student assignments, expenses, and monthly transport dues.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Vehicles</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ props.summary.active_vehicles }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Routes</p>
                    <p class="mt-2 text-2xl font-bold text-blue-600">{{ props.summary.active_routes }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Assignments</p>
                    <p class="mt-2 text-2xl font-bold text-green-600">{{ props.summary.active_assignments }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">This Month Expense</p>
                    <p class="mt-2 text-2xl font-bold text-red-600">{{ formatMoney(props.summary.this_month_expense) }}</p>
                </div>
            </div>

            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px grid grid-cols-2 gap-x-4 gap-y-1 md:grid-cols-3 xl:grid-cols-6">
                    <button v-for="tab in ['vehicles', 'routes', 'stops', 'assignments', 'expenses', 'dues']" :key="tab" type="button" @click="activeTab = tab as typeof activeTab.value" :class="[
                        activeTab === tab ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                        'border-b-2 px-2 py-3 text-sm font-medium capitalize'
                    ]">
                        {{ tab }}
                    </button>
                </nav>
            </div>

            <div v-if="activeTab !== 'dues'" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Campus</label>
                        <select v-model="campusFilter" :class="selectClass">
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                        <Input v-model="searchQuery" placeholder="Search this tab by name, route, vehicle, student, or reference..." />
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'vehicles'" class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ vehicleForm.id ? 'Edit Vehicle' : 'Create Vehicle' }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Campus</label>
                            <select v-model="vehicleForm.campus_id" :class="selectClass">
                                <option value="">Select campus</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Vehicle No</label>
                            <Input v-model="vehicleForm.vehicle_no" placeholder="ABC-123" />
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                <select v-model="vehicleForm.vehicle_type" :class="selectClass">
                                    <option value="van">Van</option>
                                    <option value="bus">Bus</option>
                                    <option value="coaster">Coaster</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Capacity</label>
                                <Input v-model="vehicleForm.capacity" type="number" min="0" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Driver</label>
                                <Input v-model="vehicleForm.driver_name" placeholder="Driver name" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Attendant</label>
                                <Input v-model="vehicleForm.attendant_name" placeholder="Attendant name" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select v-model="vehicleForm.status" :class="selectClass">
                                    <option value="active">Active</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="retired">Retired</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Purchase Date</label>
                                <Input v-model="vehicleForm.purchase_date" type="date" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                            <textarea v-model="vehicleForm.notes" :class="textareaClass" />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input v-model="vehicleForm.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                            Vehicle is active
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <Button @click="submitVehicle">{{ vehicleForm.id ? 'Update Vehicle' : 'Create Vehicle' }}</Button>
                            <Button variant="outline" @click="resetVehicleForm">Clear</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Vehicle</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Campus</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Staff</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="vehicle in filteredVehicles" :key="vehicle.id">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ vehicle.vehicle_no }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ vehicle.vehicle_type }} | Capacity: {{ vehicle.capacity }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ vehicle.campus?.name || '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        <div>{{ vehicle.driver_name || '-' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ vehicle.attendant_name || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="vehicle.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium">
                                            {{ vehicle.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" @click="editVehicle(vehicle)">
                                                <Icon icon="square-pen" class="h-3.5 w-3.5" />
                                                Edit
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredVehicles.length === 0">
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No vehicles found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'routes'" class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ routeForm.id ? 'Edit Route' : 'Create Route' }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Campus</label>
                            <select v-model="routeForm.campus_id" :class="selectClass">
                                <option value="">Select campus</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Route Name</label>
                            <Input v-model="routeForm.name" placeholder="North Route" />
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Vehicle</label>
                                <select v-model="routeForm.transport_vehicle_id" :class="selectClass">
                                    <option value="">Select vehicle</option>
                                    <option v-for="vehicle in props.vehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.vehicle_no }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Monthly Fee</label>
                                <Input v-model="routeForm.monthly_fee" type="number" min="0" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Stops</label>
                            <select v-model="routeForm.stop_ids" :class="selectClass" multiple size="5">
                                <option v-for="stop in availableStops" :key="stop.id" :value="String(stop.id)">{{ stop.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                            <textarea v-model="routeForm.notes" :class="textareaClass" />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input v-model="routeForm.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                            Route is active
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <Button @click="submitRoute">{{ routeForm.id ? 'Update Route' : 'Create Route' }}</Button>
                            <Button variant="outline" @click="resetRouteForm">Clear</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Route</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Campus / Vehicle</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Stops</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Fee</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="routeRow in filteredRoutes" :key="routeRow.id">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ routeRow.name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ routeRow.notes || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        <div>{{ routeRow.campus?.name || '-' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ routeRow.vehicle?.vehicle_no || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ routeRow.stops.map((stop) => stop.name).join(', ') || '-' }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-blue-600">{{ formatMoney(routeRow.monthly_fee) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" @click="editRoute(routeRow)">
                                                <Icon icon="square-pen" class="h-3.5 w-3.5" />
                                                Edit
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'stops'" class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ stopForm.id ? 'Edit Stop' : 'Create Stop' }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Campus</label>
                            <select v-model="stopForm.campus_id" :class="selectClass">
                                <option value="">Select campus</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Stop Name</label>
                            <Input v-model="stopForm.name" placeholder="Stop name" />
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Pickup Time</label>
                                <Input v-model="stopForm.pickup_time" type="time" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Drop Time</label>
                                <Input v-model="stopForm.drop_time" type="time" />
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input v-model="stopForm.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                            Stop is active
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <Button @click="submitStop">{{ stopForm.id ? 'Update Stop' : 'Create Stop' }}</Button>
                            <Button variant="outline" @click="resetStopForm">Clear</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Stop</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Campus</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Timings</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="stop in filteredStops" :key="stop.id">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ stop.name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ stop.campus?.name || '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ stop.pickup_time || '-' }} / {{ stop.drop_time || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" @click="editStop(stop)">
                                                <Icon icon="square-pen" class="h-3.5 w-3.5" />
                                                Edit
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'assignments'" class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ assignmentForm.id ? 'Edit Assignment' : 'Create Assignment' }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Student</label>
                            <select v-model="assignmentForm.student_id" :class="selectClass">
                                <option value="">Select student</option>
                                <option v-for="student in filteredStudents" :key="student.id" :value="String(student.id)">{{ student.display }}</option>
                            </select>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Campus</label>
                                <select v-model="assignmentForm.campus_id" :class="selectClass">
                                    <option value="">Select campus</option>
                                    <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Route</label>
                                <select v-model="assignmentForm.transport_route_id" :class="selectClass">
                                    <option value="">Select route</option>
                                    <option v-for="routeRow in props.routes" :key="routeRow.id" :value="String(routeRow.id)">{{ routeRow.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Stop</label>
                                <select v-model="assignmentForm.transport_stop_id" :class="selectClass">
                                    <option value="">Select stop</option>
                                    <option v-for="stop in availableRouteStops" :key="stop.id" :value="String(stop.id)">{{ stop.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Monthly Fee</label>
                                <Input v-model="assignmentForm.monthly_fee" type="number" min="0" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Effective From</label>
                                <Input v-model="assignmentForm.effective_from" type="date" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Effective To</label>
                                <Input v-model="assignmentForm.effective_to" type="date" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select v-model="assignmentForm.status" :class="selectClass">
                                    <option value="active">Active</option>
                                    <option value="paused">Paused</option>
                                    <option value="ended">Ended</option>
                                </select>
                            </div>
                            <label class="flex items-center gap-2 self-end text-sm text-gray-700 dark:text-gray-300">
                                <input v-model="assignmentForm.generate_dues" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                Generate monthly dues
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button @click="submitAssignment">{{ assignmentForm.id ? 'Update Assignment' : 'Create Assignment' }}</Button>
                            <Button variant="outline" @click="resetAssignmentForm">Clear</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Route / Stop</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Fee</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Duration</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="assignment in filteredAssignments" :key="assignment.id">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ assignment.student?.user?.name || assignment.student?.name || '-' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ assignment.student?.registration_no || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        <div>{{ assignment.route?.name || '-' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ assignment.stop?.name || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-blue-600">{{ formatMoney(assignment.monthly_fee) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ formatDate(assignment.effective_from) }} - {{ formatDate(assignment.effective_to) }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="assignment.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium">
                                            {{ assignment.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" @click="editAssignment(assignment)">
                                                <Icon icon="square-pen" class="h-3.5 w-3.5" />
                                                Edit
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'expenses'" class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ expenseForm.id ? 'Edit Expense' : 'Record Expense' }}</h2>
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Campus</label>
                                <select v-model="expenseForm.campus_id" :class="selectClass">
                                    <option value="">Select campus</option>
                                    <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Vehicle</label>
                                <select v-model="expenseForm.transport_vehicle_id" :class="selectClass">
                                    <option value="">Select vehicle</option>
                                    <option v-for="vehicle in props.vehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.vehicle_no }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Expense Type</label>
                                <select v-model="expenseForm.expense_type" :class="selectClass">
                                    <option value="fuel">Fuel</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="repair">Repair</option>
                                    <option value="misc">Miscellaneous</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Expense Date</label>
                                <Input v-model="expenseForm.expense_date" type="date" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                                <Input v-model="expenseForm.amount" type="number" min="0.01" step="0.01" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
                                <select v-model="expenseForm.payment_method" :class="selectClass">
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Reference No</label>
                            <Input v-model="expenseForm.reference_no" placeholder="Reference / receipt no" />
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea v-model="expenseForm.description" :class="textareaClass" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button @click="submitExpense">{{ expenseForm.id ? 'Update Expense' : 'Record Expense' }}</Button>
                            <Button variant="outline" @click="resetExpenseForm">Clear</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Type / Vehicle</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Campus</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Amount</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="expense in filteredExpenses" :key="expense.id">
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ formatDate(expense.expense_date) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium capitalize text-gray-900 dark:text-white">{{ expense.expense_type }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ expense.vehicle?.vehicle_no || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ expense.campus?.name || '-' }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-red-600">{{ formatMoney(expense.amount) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" @click="editExpense(expense)">
                                                <Icon icon="square-pen" class="h-3.5 w-3.5" />
                                                Edit
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'dues'" class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Generate Monthly Transport Dues</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Campus</label>
                            <select v-model="duesForm.campus_id" :class="selectClass">
                                <option value="">All Campuses</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                            </select>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Month</label>
                                <select v-model="duesForm.month_id" :class="selectClass">
                                    <option value="">Select month</option>
                                    <option v-for="month in props.months" :key="month.id" :value="String(month.id)">{{ month.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Year</label>
                                <Input v-model="duesForm.year" type="number" min="2020" max="2100" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                            <Input v-model="duesForm.due_date" type="date" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button @click="generateDues">
                                <Icon icon="receipt-text" class="h-4 w-4" />
                                Generate Transport Dues
                            </Button>
                            <Button variant="outline" @click="router.get('/fee/vouchers/generate')">
                                <Icon icon="file-text" class="h-4 w-4" />
                                Open Voucher Generation
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">How This Links With Finance</h3>
                    <div class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <p>Monthly dues create student transport charges. These remain separate from fee and inventory charges but can be merged into vouchers when the transport option is enabled on voucher generation.</p>
                        <p>Vehicle expenses post directly into finance journals, so fuel, maintenance, and repairs appear in finance transactions and expense reporting.</p>
                        <p>Assignments with <span class="font-medium text-gray-900 dark:text-white">Generate monthly dues</span> enabled are included in this process only while they remain active and within the effective date range.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
