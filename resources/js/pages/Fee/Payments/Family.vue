<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { alert, formatCurrency } from '@/utils';

interface FamilyVoucher {
    id: number;
    voucher_no: string;
    month: string | null;
    year: number;
    balance_amount: number;
}

interface FamilyChild {
    id: number;
    name: string;
    registration_number: string;
    vouchers: FamilyVoucher[];
}

const props = defineProps<{
    student: { id: number; name: string };
    children: FamilyChild[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payments', href: '/fee/payments' },
    { title: 'Family payment', href: '#' },
];

const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

const form = reactive({
    payment_date: today,
    payment_method: 'cash',
    reference_no: '',
    bank_name: '',
});

const lines = reactive(
    props.children.flatMap((child) =>
        child.vouchers.map((voucher) => ({
            voucher_id: voucher.id,
            balance: voucher.balance_amount,
            amount: '' as number | string,
            enabled: false,
        })),
    ),
);

const lineFor = (voucherId: number) => lines.find((line) => line.voucher_id === voucherId)!;

const toggleLine = (voucherId: number) => {
    const line = lineFor(voucherId);
    line.amount = line.enabled ? line.balance : '';
};

const total = computed(() => lines.filter((line) => line.enabled).reduce((sum, line) => sum + Number(line.amount || 0), 0));
const isSubmitting = ref(false);
const futureDateMessage = computed(() => (form.payment_date > today ? 'Payment date cannot be in the future.' : ''));

const page = usePage();
const paymentDateError = computed(() => {
    const errors = (page.props.errors ?? {}) as Record<string, string>;
    const key = Object.keys(errors).find((name) => name.endsWith('payment_date'));

    return key ? errors[key] : '';
});

const lineError = (line: { amount: number | string; balance: number }): string => {
    const amount = Number(line.amount);

    if (line.amount === '' || !Number.isFinite(amount)) {
        return 'Enter the amount to pay.';
    }

    if (amount < 0) {
        return 'Negative amount is not allowed.';
    }

    if (amount === 0) {
        return 'Amount must be greater than 0.';
    }

    return amount > line.balance ? `Amount is more than the balance (Rs ${line.balance.toLocaleString()}).` : '';
};

const submit = () => {
    if (isSubmitting.value) {
        return;
    }

    const firstError = lines.filter((line) => line.enabled).map(lineError).find((message) => message !== '');

    if (firstError) {
        alert.error(firstError);

        return;
    }

    const vouchers = lines
        .filter((line) => line.enabled && Number(line.amount) > 0)
        .map((line) => ({ voucher_id: line.voucher_id, amount: Number(line.amount) }));

    if (vouchers.length === 0) {
        alert.error('Select at least one voucher.');

        return;
    }

    if (lines.some((line) => line.enabled && Number(line.amount) > line.balance)) {
        alert.error('Amount is more than the balance.');

        return;
    }

    if (futureDateMessage.value) {
        return;
    }

    isSubmitting.value = true;

    router.post(route('fee.payments.store-family'), {
        student_id: props.student.id,
        ...form,
        reference_no: form.payment_method === 'cash' ? '' : form.reference_no,
        bank_name: ['bank', 'cheque'].includes(form.payment_method) ? form.bank_name : '',
        vouchers,
    }, {
        onError: (errors) => alert.error(String(Object.values(errors)[0] ?? 'Could not record the payment.')),
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Family Payment" />

        <form novalidate class="max-w-4xl space-y-6 p-4 md:p-6" @submit.prevent="submit">
            <h1 class="text-xl font-bold text-foreground md:text-2xl">Family payment</h1>

            <div class="grid gap-4 rounded-lg border border-border bg-card p-4 md:grid-cols-2">
                <div>
                    <Label for="family_date">Payment date</Label>
                    <Input id="family_date" v-model="form.payment_date" type="date" required />
                    <p v-if="futureDateMessage || paymentDateError" class="text-sm text-destructive">{{ futureDateMessage || paymentDateError }}</p>
                </div>
                <div>
                    <Label for="family_method">Method</Label>
                    <select id="family_method" v-model="form.payment_method" class="w-full rounded-md border border-border bg-background p-2 text-sm">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="online">Online Payment</option>
                        <option value="jazzcash">JazzCash</option>
                        <option value="easypaisa">EasyPaisa</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div v-if="form.payment_method !== 'cash'">
                    <Label for="family_ref">Reference no</Label>
                    <Input id="family_ref" v-model="form.reference_no" placeholder="Bank transaction ID, cheque number, etc." />
                </div>
                <div v-if="['bank', 'cheque'].includes(form.payment_method)">
                    <Label for="family_bank">Bank name</Label>
                    <Input id="family_bank" v-model="form.bank_name" placeholder="Bank name" />
                </div>
            </div>

            <div v-for="child in props.children" :key="child.id" class="rounded-lg border border-border bg-card p-4">
                <h2 class="font-semibold text-foreground">{{ child.name }} <span class="text-sm font-normal text-muted-foreground">{{ child.registration_number }}</span></h2>
                <p v-if="child.vouchers.length === 0" class="mt-2 text-sm text-muted-foreground">No unpaid vouchers.</p>
                <div v-for="voucher in child.vouchers" :key="voucher.id" class="mt-3 grid grid-cols-[auto_1fr_auto] items-center gap-x-3 gap-y-1 sm:grid-cols-[auto_minmax(14rem,1fr)_11rem_8rem]">
                    <input v-model="lineFor(voucher.id).enabled" type="checkbox" @change="toggleLine(voucher.id)" :aria-label="`Pay ${voucher.voucher_no}`" />
                    <span class="text-sm">{{ voucher.voucher_no }} - {{ voucher.month }} {{ voucher.year }}</span>
                    <span class="text-sm text-muted-foreground sm:text-right">Balance {{ formatCurrency(voucher.balance_amount) }}</span>
                    <Input
                        v-model="lineFor(voucher.id).amount"
                        type="number"
                        step="0.01"
                        min="0"
                        :max="voucher.balance_amount"
                        placeholder="Enter amount to pay"
                        class="w-full text-right"
                        :disabled="!lineFor(voucher.id).enabled"
                    />
                    <p v-if="lineFor(voucher.id).enabled && lineError(lineFor(voucher.id))" class="col-span-full text-right text-sm text-destructive">{{ lineError(lineFor(voucher.id)) }}</p>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-lg font-bold text-foreground">Total {{ formatCurrency(total) }}</p>
                <Button type="submit" :disabled="isSubmitting">
                    {{ isSubmitting ? 'Processing...' : `Pay selected (${formatCurrency(total)})` }}
                </Button>
            </div>
        </form>
    </AppLayout>
</template>
