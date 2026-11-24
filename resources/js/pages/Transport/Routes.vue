<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
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

interface StopRow {
    id: number;
    name: string;
    pickup_time?: string | null;
    drop_time?: string | null;
}

interface RouteRow {
    id: number;
    transport_vehicle_id?: number | null;
    name: string;
    monthly_fee: number | string;
    is_active: boolean;
    notes?: string | null;
    vehicle?: { id: number; vehicle_no: string } | null;
    stops: StopRow[];
}

const props = defineProps<{ routes: RouteRow[]; stops: StopRow[]; vehicles: { id: number; vehicle_no: string }[] }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transport', href: '/transport' },
    { title: 'Routes & Stops', href: '/transport/routes' },
];

const search = ref('');
const dialogOpen = ref(false);
const { fieldErrors, submitting, validateRequired, save, axios } = useTransportForm();

const blankForm = () => ({
    id: null as number | null,
    name: '',
    monthly_fee: '',
    transport_vehicle_id: '',
    stop_ids: [] as string[],
    is_active: true,
});
const form = reactive(blankForm());
useClearErrorsOnEdit(form, fieldErrors);

const newStop = reactive({ name: '', pickup_time: '', drop_time: '' });
const stopError = ref('');
const addingStop = ref(false);

const knownStops = ref<StopRow[]>([...props.stops]);
const stopOptions = computed(() => knownStops.value.map((stop) => ({ value: String(stop.id), label: stop.name })));
const vehicleOptions = computed(() => props.vehicles.map((vehicle) => ({ value: String(vehicle.id), label: vehicle.vehicle_no })));

const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.routes.filter(
        (row) =>
            !query ||
            [row.name, row.vehicle?.vehicle_no, ...row.stops.map((stop) => stop.name)].some((value) => String(value ?? '').toLowerCase().includes(query)),
    );
});

const resetNewStop = () => {
    Object.assign(newStop, { name: '', pickup_time: '', drop_time: '' });
    stopError.value = '';
};

const openAdd = () => {
    Object.assign(form, blankForm());
    knownStops.value = [...props.stops];
    resetNewStop();
    fieldErrors.value = {};
    dialogOpen.value = true;
};

const openEdit = (row: RouteRow) => {
    Object.assign(form, {
        id: row.id,
        name: row.name,
        monthly_fee: String(row.monthly_fee ?? ''),
        transport_vehicle_id: row.transport_vehicle_id ? String(row.transport_vehicle_id) : '',
        stop_ids: row.stops.map((stop) => String(stop.id)),
        is_active: row.is_active,
    });
    knownStops.value = [...props.stops];
    resetNewStop();
    fieldErrors.value = {};
    dialogOpen.value = true;
};

const addStop = async () => {
    if (!newStop.name.trim()) {
        stopError.value = 'Stop name is required.';
        alert.warning('Please check the errors in the form.');

        return;
    }

    addingStop.value = true;

    try {
        const response = await axios.post(route('transport.stops.store'), {
            name: newStop.name,
            pickup_time: newStop.pickup_time || null,
            drop_time: newStop.drop_time || null,
        });
        const stop = response.data.stop as StopRow;
        knownStops.value.push(stop);
        form.stop_ids.push(String(stop.id));
        resetNewStop();
        alert.success('Stop added.');
    } catch (error: any) {
        stopError.value = error?.response?.data?.errors?.name?.[0] ?? 'Could not add the stop.';
    } finally {
        addingStop.value = false;
    }
};

const submit = async () => {
    if (await validateRequired(form, ['name', 'monthly_fee'], { name: 'Route name', monthly_fee: 'Monthly fee' })) {
        return;
    }

    const payload = {
        ...form,
        transport_vehicle_id: form.transport_vehicle_id || null,
        stop_ids: form.stop_ids.map((id) => Number(id)),
    };

    const saved = await save(
        () => (form.id ? axios.put(route('transport.routes.update', form.id), payload) : axios.post(route('transport.routes.store'), payload)),
        'Could not save the route.',
    );

    if (saved) {
        alert.success(form.id ? 'Route updated.' : 'Route added.');
        dialogOpen.value = false;
        router.reload({ only: ['routes', 'stops'], preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Routes & Stops" />

        <div class="space-y-6 p-4 md:p-6">
            <TransportListHeader
                v-model:search="search"
                title="Routes & Stops"
                description="Each route has its stops and a monthly fee."
                add-label="Add Route"
                search-placeholder="Search by route, stop or vehicle..."
                @add="openAdd"
            />

            <div class="overflow-x-auto rounded-2xl border border-border bg-card shadow-sm">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="w-10 px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Route</th>
                            <th class="px-4 py-3 font-medium">Stops</th>
                            <th class="px-4 py-3 font-medium">Vehicle</th>
                            <th class="px-4 py-3 text-right font-medium">Monthly fee</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, idx) in filtered" :key="row.id" class="border-t border-border">
                            <td class="px-4 py-3 text-muted-foreground">{{ idx + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-foreground">{{ row.name }}</td>
                            <td class="px-4 py-3 text-foreground">{{ row.stops.map((stop) => stop.name).join(', ') || '-' }}</td>
                            <td class="px-4 py-3 text-foreground">{{ row.vehicle?.vehicle_no || '-' }}</td>
                            <td class="px-4 py-3 text-right text-foreground">{{ formatMoney(row.monthly_fee) }}</td>
                            <td class="px-4 py-3 text-foreground">{{ row.is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-3">
                                <RowActions><RowAction kind="edit" @click="openEdit(row)" /></RowActions>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No routes found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <TransportFormDialog
            v-model:open="dialogOpen"
            :title="form.id ? 'Edit Route' : 'Add Route'"
            :submitting="submitting"
            @submit="submit"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <TransportField label="Route name" required :error="fieldErrors.name">
                    <Input v-model="form.name"  placeholder="e.g. Gulberg Morning Route" />
                </TransportField>
                <TransportField label="Monthly fee" required :error="fieldErrors.monthly_fee">
                    <Input v-model="form.monthly_fee" type="number" min="0"  placeholder="e.g. 3000" />
                </TransportField>
            </div>
            <TransportField label="Vehicle" :error="fieldErrors.transport_vehicle_id">
                <SearchableSelect v-model="form.transport_vehicle_id" :options="vehicleOptions" placeholder="No vehicle yet" clearable />
            </TransportField>
            <TransportField label="Stops" :error="fieldErrors.stop_ids">
                <SearchableSelect v-model="form.stop_ids" :options="stopOptions" placeholder="Pick stops" multiple />
            </TransportField>
            <div class="space-y-3 rounded-lg border border-dashed border-border p-3">
                <p class="text-sm font-medium text-foreground">Need a new stop?</p>
                <TransportField label="Stop name" :error="stopError">
                    <Input v-model="newStop.name" placeholder="e.g. Main Market" @keydown.enter.prevent.stop="addStop" />
                </TransportField>
                <div class="grid gap-3 sm:grid-cols-2">
                    <TransportField label="Pick-up time">
                        <Input v-model="newStop.pickup_time" type="time" @keydown.enter.prevent.stop="addStop" />
                    </TransportField>
                    <TransportField label="Drop time">
                        <Input v-model="newStop.drop_time" type="time" @keydown.enter.prevent.stop="addStop" />
                    </TransportField>
                </div>
                <Button type="button" variant="outline" size="sm" :class="{ 'opacity-70': addingStop }" @click="addStop">Add stop</Button>
            </div>
        </TransportFormDialog>
    </AppLayout>
</template>
