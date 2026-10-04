<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import { useYearOptions } from '@/composables/useYearOptions';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import { alert } from '@/utils';
import RowAction from '@/components/tables/RowAction.vue';
import RowActions from '@/components/tables/RowActions.vue';
import GiveAdvanceDialog from '@/components/Staff/GiveAdvanceDialog.vue';
import type { BreadcrumbItem } from '@/types';

interface Campus {
    id: number;
    name: string;
}

interface MonthOption {
    id: number;
    name: string;
    month_number: number;
}

interface PayrollItem {
    id: number;
    staff_profile_id: number;
    gross_salary: number | string;
    allowance_amount: number | string;
    deduction_amount: number | string;
    net_salary: number | string;
    amount_paid?: number | string;
    advance_deduction_amount?: number | string;
    status: string;
    payment_method?: string | null;
    reference_no?: string | null;
    notes?: string | null;
    staff_profile?: { employee_no: string; user?: { name: string } | null } | null;
}

interface PayrollRun {
    id: number;
    title: string;
    campus_id?: number | null;
    payroll_year: number;
    status: string;
    total_gross: number | string;
    total_deductions: number | string;
    total_net: number | string;
    processed_at?: string | null;
    campus?: { name: string } | null;
    month?: { name: string } | null;
    items: PayrollItem[];
}

interface Props {
    campuses: Campus[];
    months: MonthOption[];
    payrollRuns: PayrollRun[];
    can: { approve: boolean };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Payroll', href: route('staff.payroll.page') },
];

const selectClass = 'w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';

const expandedRunId = ref<number | null>(null);
const campusFilter = ref('');

const yearOptions = useYearOptions();

const payrollForm = reactive({
    campus_id: '',
    payroll_month_id: props.months.find((m) => m.month_number === new Date().getMonth() + 1)?.id?.toString() ?? '',
    payroll_year: String(new Date().getFullYear()),
    title: '',
});

const formatMoney = (amount: number | string | null | undefined) => {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', minimumFractionDigits: 0 }).format(Number(amount || 0));
};

const formatDate = (value?: string | null) => {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('en-PK', { year: 'numeric', month: 'short', day: 'numeric' });
};

const campusOptions = computed(() => props.campuses.map((campus) => ({ value: String(campus.id), label: campus.name })));
const monthOptions = computed(() => props.months.map((month) => ({ value: String(month.id), label: month.name })));

const filteredRuns = computed(() => {
    if (!campusFilter.value) return props.payrollRuns;
    return props.payrollRuns.filter((run) => String(run.campus_id ?? '') === campusFilter.value);
});

const generatePayroll = async () => {
    try {
        await axios.post(route('staff.payroll.generate'), { ...payrollForm, campus_id: payrollForm.campus_id || null });
        alert.success('Payroll generated successfully.');
        router.reload({ only: ['payrollRuns'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to generate payroll.');
    }
};

const payDialog = reactive({
    open: false,
    item: null as PayrollItem | null,
    payment_method: 'bank',
    reference_no: '',
    amount: '',
});

const amountDue = (item: PayrollItem) => {
    const payable = Number(item.net_salary || 0) - Number(item.advance_deduction_amount || 0);
    return Math.max(payable - Number(item.amount_paid || 0), 0);
};

const openPayDialog = (item: PayrollItem) => {
    if (item.status === 'paid') return;
    payDialog.item = item;
    payDialog.payment_method = item.payment_method || 'bank';
    payDialog.reference_no = item.reference_no || '';
    payDialog.amount = String(amountDue(item));
    payDialog.open = true;
};

const confirmPayrollPaid = async () => {
    const item = payDialog.item;
    if (!item) return;

    try {
        await axios.post(route('staff.payroll.items.pay', item.id), {
            payment_method: payDialog.payment_method,
            // Cheque/transaction reference number: always optional, shown for every payment method.
            reference_no: payDialog.reference_no || null,
            amount: payDialog.amount || null,
        });
        alert.success('Salary payment recorded.');
        payDialog.open = false;
        router.reload({ only: ['payrollRuns'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to record salary payment.');
    }
};

const showAdvanceDialog = ref(false);
const advanceStaffId = ref<number | null>(null);

const openAdvanceDialog = (staffProfileId: number) => {
    advanceStaffId.value = staffProfileId;
    showAdvanceDialog.value = true;
};

const onAdvanceGiven = () => {
    router.reload({ only: ['payrollRuns'] });
};

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Payroll" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Payroll</h1>
                <p class="mt-1 text-sm text-muted-foreground">Generate a monthly run, then release it. Posts straight into the finance journals.</p>
            </div>

            <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Campus</label>
                        <SearchableSelect v-model="payrollForm.campus_id" :options="campusOptions" placeholder="All Campuses" clearable />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Month</label>
                        <SearchableSelect v-model="payrollForm.payroll_month_id" :options="monthOptions" placeholder="Select month" clearable />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Year</label>
                        <select v-model.number="payrollForm.payroll_year" class="h-10 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground">
                            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Title</label>
                        <Input v-model="payrollForm.title" placeholder="Optional payroll title" />
                    </div>
                </div>
                <div class="mt-4">
                    <Button @click="generatePayroll">
                        <Icon icon="wallet" class="h-4 w-4" />
                        Generate Payroll
                    </Button>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-muted-foreground">Filter by Campus</label>
                <SearchableSelect v-model="campusFilter" :options="campusOptions" placeholder="All Campuses" clearable class="max-w-65" />
            </div>

            <div class="space-y-4">
                <div v-for="run in filteredRuns" :key="run.id" class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-border px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">{{ run.title }}</h3>
                            <p class="text-sm text-muted-foreground">{{ run.month?.name || 'Month' }} {{ run.payroll_year }} | {{ run.campus?.name || 'All Campuses' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-sm">
                            <span :class="run.status === 'paid' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'" class="inline-flex rounded-full px-2.5 py-1 font-medium uppercase">{{ run.status }}</span>
                            <span class="font-medium text-muted-foreground">Net: {{ formatMoney(run.total_net) }}</span>
                            <Button variant="outline" size="sm" @click="expandedRunId = expandedRunId === run.id ? null : run.id">
                                {{ expandedRunId === run.id ? 'Hide Items' : 'Show Items' }}
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-4 border-b border-border px-5 py-4 text-sm md:grid-cols-3">
                        <div>Gross: <span class="font-medium text-foreground">{{ formatMoney(run.total_gross) }}</span></div>
                        <div>Deductions: <span class="font-medium text-destructive">{{ formatMoney(run.total_deductions) }}</span></div>
                        <div>Processed: <span class="font-medium text-foreground">{{ formatDate(run.processed_at) }}</span></div>
                    </div>

                    <div v-if="expandedRunId === run.id" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Staff</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Gross</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Deduction</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Net</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="(item, itemIndex) in run.items" :key="item.id" class="hover:bg-accent">
                                    <td class="px-4 py-3 text-sm text-muted-foreground">{{ itemIndex + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-foreground">{{ item.staff_profile?.user?.name || '-' }}</div>
                                        <div class="text-xs text-muted-foreground">{{ item.staff_profile?.employee_no || '-' }}</div>
                                        <div v-if="item.notes" class="text-xs text-warning">{{ item.notes }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatMoney(item.gross_salary) }}</td>
                                    <td class="px-4 py-3 text-sm text-destructive">{{ formatMoney(item.deduction_amount) }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-primary">{{ formatMoney(item.net_salary) }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="item.status === 'paid' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium uppercase">{{ item.status }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <RowActions v-if="props.can.approve">
                                            <RowAction
                                                kind="pay"
                                                :label="item.status === 'paid' ? 'Paid' : 'Mark Paid'"
                                                :disabled="item.status === 'paid'"
                                                @click="openPayDialog(item)"
                                            />
                                            <RowAction
                                                kind="custom"
                                                icon="banknote"
                                                label="Give Advance"
                                                @click="openAdvanceDialog(item.staff_profile_id)"
                                            />
                                        </RowActions>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="filteredRuns.length === 0" class="rounded-2xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                    No payroll runs found yet.
                </div>
            </div>
        </div>

        <Dialog v-model:open="payDialog.open">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Mark Salary Paid</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="space-y-2">
                        <Label for="pay-method">Payment Method</Label>
                        <select
                            id="pay-method"
                            v-model="payDialog.payment_method"
                            class="h-10 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                        >
                            <option value="bank">Bank</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="pay-amount">Amount</Label>
                        <Input id="pay-amount" v-model="payDialog.amount" type="number" min="0.01" step="0.01" placeholder="Amount to pay now" />
                        <p class="text-xs text-muted-foreground">Leave as-is to pay in full, or lower it to record a partial payment.</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="pay-reference">Cheque / Reference Number <span class="text-muted-foreground font-normal">(optional)</span></Label>
                        <Input id="pay-reference" v-model="payDialog.reference_no" placeholder="Enter cheque or reference number" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="payDialog.open = false">Cancel</Button>
                    <Button @click="confirmPayrollPaid">Record Payment</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <GiveAdvanceDialog
            v-model:open="showAdvanceDialog"
            :staff-profile-id="advanceStaffId"
            @given="onAdvanceGiven"
        />
    </AppLayout>
</template>
