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
import { formatMoney, useTransportForm } from '@/composables/useTransportForm';
import { alert } from '@/utils';
import type { BreadcrumbItem } from '@/types';

interface ExpenseRow {
    id: number;
    transport_vehicle_id?: number | null;
    expense_type: string;
    expense_date: string;
    amount: number | string;
    payment_method: string;
    reference_no?: string | null;
    description?: string | null;
    vehicle?: { id: number; vehicle_no: string } | null;
}

const props = defineProps<{ expenses: ExpenseRow[]; vehicles: { id: number; vehicle_no: string }[] }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transport', href: '/transport' },
    { title: 'Expenses', href: '/transport/expenses' },
];

const typeOptions = [
    { value: 'fuel', label: 'Fuel' },
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'repair', label: 'Repair' },
    { value: 'misc', label: 'Miscellaneous' },
];
const methodOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'bank', label: 'Bank' },
    { value: 'cheque', label: 'Cheque' },
];
const vehicleOptions = computed(() => props.vehicles.map((vehicle) => ({ value: String(vehicle.id), label: vehicle.vehicle_no })));

const search = ref('');
const dialogOpen = ref(false);
const { fieldErrors, submitting, validateRequired, save, axios } = useTransportForm();

const today = () => new Date().toISOString().slice(0, 10);
const blankForm = () => ({
    id: null as number | null,
    transport_vehicle_id: '',
    expense_type: 'fuel',
    expense_date: today(),
    amount: '',
    payment_method: 'cash',
    reference_no: '',
    description: '',
});
const form = reactive(blankForm());
useClearErrorsOnEdit(form, fieldErrors);

const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.expenses.filter(
        (expense) =>
            !query ||
            [expense.expense_type, expense.reference_no, expense.description, expense.vehicle?.vehicle_no].some((value) =>
                String(value ?? '').toLowerCase().includes(query),
            ),
    );
});

const openAdd = () => {
    Object.assign(form, blankForm());
    fieldErrors.value = {};
    dialogOpen.value = true;
};

const openEdit = (expense: ExpenseRow) => {
    Object.assign(form, {
        id: expense.id,
        transport_vehicle_id: expense.transport_vehicle_id ? String(expense.transport_vehicle_id) : '',
        expense_type: expense.expense_type,
        expense_date: expense.expense_date.slice(0, 10),
        amount: String(expense.amount ?? ''),
        payment_method: expense.payment_method,
        reference_no: expense.reference_no ?? '',
        description: expense.description ?? '',
    });
    fieldErrors.value = {};
    dialogOpen.value = true;
};

const submit = async () => {
    if (await validateRequired(form, ['expense_type', 'expense_date', 'amount', 'payment_method'], { expense_type: 'Type', expense_date: 'Date', payment_method: 'Paid by' })) {
        return;
    }

    const payload = {
        ...form,
        transport_vehicle_id: form.transport_vehicle_id || null,
        reference_no: form.reference_no || null,
        description: form.description || null,
    };

    const saved = await save(
        () => (form.id ? axios.put(route('transport.expenses.update', form.id), payload) : axios.post(route('transport.expenses.store'), payload)),
        'Could not save the expense.',
    );

    if (saved) {
        alert.success(form.id ? 'Expense updated.' : 'Expense added.');
        dialogOpen.value = false;
        router.reload({ only: ['expenses'], preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Transport Expenses" />

        <div class="space-y-6 p-4 md:p-6">
            <TransportListHeader
                v-model:search="search"
                title="Expenses"
                description="Fuel, repairs and other vehicle costs."
                add-label="Add Expense"
                search-placeholder="Search by type, vehicle or reference..."
                @add="openAdd"
            />

            <div class="overflow-x-auto rounded-2xl border border-border bg-card shadow-sm">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="w-10 px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Vehicle</th>
                            <th class="px-4 py-3 font-medium">Paid by</th>
                            <th class="px-4 py-3 text-right font-medium">Amount</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(expense, idx) in filtered" :key="expense.id" class="border-t border-border">
                            <td class="px-4 py-3 text-muted-foreground">{{ idx + 1 }}</td>
                            <td class="px-4 py-3 text-foreground">{{ expense.expense_date.slice(0, 10) }}</td>
                            <td class="px-4 py-3 capitalize text-foreground">{{ expense.expense_type }}</td>
                            <td class="px-4 py-3 text-foreground">{{ expense.vehicle?.vehicle_no || '-' }}</td>
                            <td class="px-4 py-3 capitalize text-foreground">{{ expense.payment_method }}</td>
                            <td class="px-4 py-3 text-right font-medium text-foreground">{{ formatMoney(expense.amount) }}</td>
                            <td class="px-4 py-3">
                                <RowActions><RowAction kind="edit" @click="openEdit(expense)" /></RowActions>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No expenses found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <TransportFormDialog
            v-model:open="dialogOpen"
            :title="form.id ? 'Edit Expense' : 'Add Expense'"
            :submitting="submitting"
            @submit="submit"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <TransportField label="Type" required :error="fieldErrors.expense_type">
                    <SearchableSelect v-model="form.expense_type" :options="typeOptions" placeholder="Select type" />
                </TransportField>
                <TransportField label="Date" required :error="fieldErrors.expense_date">
                    <Input v-model="form.expense_date" type="date" />
                </TransportField>
                <TransportField label="Vehicle" :error="fieldErrors.transport_vehicle_id">
                    <SearchableSelect v-model="form.transport_vehicle_id" :options="vehicleOptions" placeholder="Any vehicle" clearable />
                </TransportField>
                <TransportField label="Amount" required :error="fieldErrors.amount">
                    <Input v-model="form.amount" type="number" min="0" step="0.01"  placeholder="e.g. 5000" />
                </TransportField>
                <TransportField label="Paid by" required :error="fieldErrors.payment_method">
                    <SearchableSelect v-model="form.payment_method" :options="methodOptions" placeholder="Select" />
                </TransportField>
                <TransportField label="Reference" :error="fieldErrors.reference_no">
                    <Input v-model="form.reference_no"  placeholder="Bill / receipt no." />
                </TransportField>
            </div>
            <TransportField label="Note" :error="fieldErrors.description">
                <Input v-model="form.description"  placeholder="What was this expense for?" />
            </TransportField>
        </TransportFormDialog>
    </AppLayout>
</template>
