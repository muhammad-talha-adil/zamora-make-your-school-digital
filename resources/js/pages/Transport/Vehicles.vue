<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
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
import { useTransportForm } from '@/composables/useTransportForm';
import { alert } from '@/utils';
import type { BreadcrumbItem } from '@/types';

interface VehicleRow {
    id: number;
    vehicle_no: string;
    vehicle_type: string;
    capacity: number;
    driver_name?: string | null;
    attendant_name?: string | null;
    status: string;
    purchase_date?: string | null;
    is_active: boolean;
    notes?: string | null;
}

const props = defineProps<{ vehicles: VehicleRow[] }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transport', href: '/transport' },
    { title: 'Vehicles', href: '/transport/vehicles' },
];

const typeOptions = [
    { value: 'van', label: 'Van' },
    { value: 'bus', label: 'Bus' },
    { value: 'coaster', label: 'Coaster' },
];
const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'retired', label: 'Retired' },
];

const search = ref('');
const dialogOpen = ref(false);
const { fieldErrors, submitting, validateRequired, save, axios } = useTransportForm();

const blankForm = () => ({
    id: null as number | null,
    vehicle_no: '',
    vehicle_type: 'van',
    capacity: '',
    driver_name: '',
    attendant_name: '',
    status: 'active',
    purchase_date: '',
    notes: '',
});
const form = reactive(blankForm());
useClearErrorsOnEdit(form, fieldErrors);

const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.vehicles.filter(
        (vehicle) =>
            !query ||
            [vehicle.vehicle_no, vehicle.vehicle_type, vehicle.driver_name, vehicle.attendant_name].some((value) =>
                String(value ?? '').toLowerCase().includes(query),
            ),
    );
});

const openAdd = () => {
    Object.assign(form, blankForm());
    fieldErrors.value = {};
    dialogOpen.value = true;
};

const openEdit = (vehicle: VehicleRow) => {
    Object.assign(form, {
        id: vehicle.id,
        vehicle_no: vehicle.vehicle_no,
        vehicle_type: vehicle.vehicle_type,
        capacity: String(vehicle.capacity ?? ''),
        driver_name: vehicle.driver_name ?? '',
        attendant_name: vehicle.attendant_name ?? '',
        status: vehicle.status,
        purchase_date: vehicle.purchase_date ? vehicle.purchase_date.slice(0, 10) : '',
        notes: vehicle.notes ?? '',
    });
    fieldErrors.value = {};
    dialogOpen.value = true;
};

const submit = async () => {
    if (await validateRequired(form, ['vehicle_no', 'vehicle_type', 'capacity'], { vehicle_no: 'Vehicle number', vehicle_type: 'Type' })) {
        return;
    }

    const payload = {
        ...form,
        driver_name: form.driver_name || null,
        attendant_name: form.attendant_name || null,
        purchase_date: form.purchase_date || null,
        notes: form.notes || null,
    };

    const saved = await save(
        () => (form.id ? axios.put(route('transport.vehicles.update', form.id), payload) : axios.post(route('transport.vehicles.store'), payload)),
        'Could not save the vehicle.',
    );

    if (saved) {
        alert.success(form.id ? 'Vehicle updated.' : 'Vehicle added.');
        dialogOpen.value = false;
        router.reload({ only: ['vehicles'], preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Vehicles" />

        <div class="space-y-6 p-4 md:p-6">
            <TransportListHeader
                v-model:search="search"
                title="Vehicles"
                description="Vans, buses and coasters used for school transport."
                add-label="Add Vehicle"
                search-placeholder="Search by number, type or driver..."
                @add="openAdd"
            />

            <div class="overflow-x-auto rounded-2xl border border-border bg-card shadow-sm">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Vehicle</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Capacity</th>
                            <th class="px-4 py-3 font-medium">Driver</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="vehicle in filtered" :key="vehicle.id" class="border-t border-border">
                            <td class="px-4 py-3 font-medium text-foreground">{{ vehicle.vehicle_no }}</td>
                            <td class="px-4 py-3 capitalize text-foreground">{{ vehicle.vehicle_type }}</td>
                            <td class="px-4 py-3 text-foreground">{{ vehicle.capacity }}</td>
                            <td class="px-4 py-3 text-foreground">{{ vehicle.driver_name || '-' }}</td>
                            <td class="px-4 py-3 capitalize text-foreground">{{ vehicle.status }}</td>
                            <td class="px-4 py-3">
                                <RowActions><RowAction kind="edit" @click="openEdit(vehicle)" /></RowActions>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">No vehicles found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <TransportFormDialog
            v-model:open="dialogOpen"
            :title="form.id ? 'Edit Vehicle' : 'Add Vehicle'"
            :submitting="submitting"
            @submit="submit"
        >
            <TransportField label="Vehicle number" required :error="fieldErrors.vehicle_no">
                <Input v-model="form.vehicle_no" placeholder="ABC-123" />
            </TransportField>
            <div class="grid gap-4 sm:grid-cols-2">
                <TransportField label="Type" required :error="fieldErrors.vehicle_type">
                    <SearchableSelect v-model="form.vehicle_type" :options="typeOptions" placeholder="Select type" />
                </TransportField>
                <TransportField label="Seats" required :error="fieldErrors.capacity">
                    <Input v-model="form.capacity" type="number" min="0" />
                </TransportField>
                <TransportField label="Driver" :error="fieldErrors.driver_name">
                    <Input v-model="form.driver_name" />
                </TransportField>
                <TransportField label="Attendant" :error="fieldErrors.attendant_name">
                    <Input v-model="form.attendant_name" />
                </TransportField>
                <TransportField v-if="form.id" label="Status" :error="fieldErrors.status">
                    <SearchableSelect v-model="form.status" :options="statusOptions" placeholder="Select status" />
                </TransportField>
                <TransportField label="Purchase date" :error="fieldErrors.purchase_date">
                    <Input v-model="form.purchase_date" type="date" />
                </TransportField>
            </div>
        </TransportFormDialog>
    </AppLayout>
</template>
