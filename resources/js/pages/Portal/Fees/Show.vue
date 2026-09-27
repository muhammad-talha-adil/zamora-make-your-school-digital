<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/layouts/PortalLayout.vue';
import Icon from '@/components/Icon.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { formatCurrency, formatDate } from '@/utils/format';
import { ref, computed, onMounted } from 'vue';

interface ChildOption {
    id: number;
    name: string | null;
    class?: string | null;
    section?: string | null;
    avatar?: string | null;
}

interface PaymentRecord {
    id: number;
    paid_amount: string | number;
    paid_on: string;
    payment_method: string;
    reference_no?: string | null;
    note?: string | null;
    receipt_no?: string | null;
}

interface VoucherDetail {
    id: number;
    voucher_no: string | null;
    voucher_year: number;
    status: string;
    net_amount: string | number;
    paid_amount: string | number;
    balance_amount: string | number;
    due_date: string | null;
    voucher_month?: { name: string } | null;
    student?: { name: string; class?: { name: string }; section?: { name: string } } | null;
    campus?: { name: string; address?: string; phone?: string } | null;
    line_items?: { name: string; amount: string | number }[];
    payments?: PaymentRecord[];
}

interface Props {
    student: ChildOption;
    students: ChildOption[];
    voucher: VoucherDetail;
}

const props = defineProps<Props>();

const statusConfig = {
    paid: { label: 'Paid', variant: 'success' as const, icon: 'check-circle' },
    partial: { label: 'Partial', variant: 'warning' as const, icon: 'clock' },
    unpaid: { label: 'Unpaid', variant: 'destructive' as const, icon: 'alert-triangle' },
    overdue: { label: 'Overdue', variant: 'destructive' as const, icon: 'alert-circle' },
    cancelled: { label: 'Cancelled', variant: 'secondary' as const, icon: 'x-circle' },
    adjusted: { label: 'Adjusted', variant: 'default' as const, icon: 'edit' },
} as const;

const getStatusConfig = (status: string) => {
    return statusConfig[status as keyof typeof statusConfig] ?? { label: status, variant: 'secondary' as const, icon: 'help-circle' };
};

const monthLabel = (voucher: VoucherDetail): string => {
    return [voucher.voucher_month?.name, voucher.voucher_year].filter(Boolean).join(' ');
};

const printVoucher = () => {
    window.print();
};

const breadcrumbs = computed(() => [
    { title: 'Fees', href: route('portal.fees.index') },
    { title: `Voucher ${props.voucher.voucher_no}`, href: route('portal.fees.show', props.voucher.id) },
]);

const detailVisible = ref(false);
const timelineVisible = ref(false);

onMounted(() => {
    requestAnimationFrame(() => {
        detailVisible.value = true;
        setTimeout(() => timelineVisible.value = true, 100);
    });
});
</script>

<template>
<PortalLayout :breadcrumbs="breadcrumbs" :current-child="props.student" :children="props.students">
    <template #default>
        <Head :title="`Voucher ${props.voucher.voucher_no}`" />
        <div class="space-y-6 animate-fade-slide-up">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl md:text-3xl font-bold text-foreground">Fee Voucher</h1>
                        <Badge :variant="getStatusConfig(props.voucher.status).variant" class="gap-1.5 text-sm">
                            <Icon :icon="getStatusConfig(props.voucher.status).icon" :size="12" />
                            {{ getStatusConfig(props.voucher.status).label }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Voucher #{{ props.voucher.voucher_no }} · {{ monthLabel(props.voucher) }} · {{ props.voucher.student?.name }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Button variant="outline" @click="printVoucher" class="no-print">
                        <Icon icon="printer" :size="16" />
                        Print Challan
                    </Button>
                    <Link :href="route('portal.fees.index')" class="btn-ghost no-print">
                        <Icon icon="arrow-left" :size="16" />
                        Back to List
                    </Link>
                </div>
            </div>

            <!-- Voucher Detail Card -->
            <Card v-show="detailVisible" class="animate-fade-slide-up overflow-hidden no-print">
                <CardHeader class="pb-4 border-b">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Voucher #</p>
                            <p class="text-lg font-bold text-foreground mt-1">{{ props.voucher.voucher_no }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Period</p>
                            <p class="text-lg font-bold text-foreground mt-1">{{ monthLabel(props.voucher) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Due Date</p>
                            <p class="text-lg font-bold text-foreground mt-1">{{ props.voucher.due_date ? formatDate(props.voucher.due_date) : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</p>
                            <Badge :variant="getStatusConfig(props.voucher.status).variant" class="mt-1 gap-1.5">
                                <Icon :icon="getStatusConfig(props.voucher.status).icon" :size="12" />
                                {{ getStatusConfig(props.voucher.status).label }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6 pt-4">
                    <!-- Amount Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Card class="bg-success/5 border-success/20">
                            <CardContent class="py-4 text-center">
                                <p class="text-xs font-medium text-success/80 uppercase tracking-wider">Net Amount</p>
                                <p class="text-3xl font-bold text-success mt-1">{{ formatCurrency(Number(props.voucher.net_amount)) }}</p>
                            </CardContent>
                        </Card>
                        <Card class="bg-primary/5 border-primary/20">
                            <CardContent class="py-4 text-center">
                                <p class="text-xs font-medium text-primary/80 uppercase tracking-wider">Amount Paid</p>
                                <p class="text-3xl font-bold text-primary mt-1">{{ formatCurrency(Number(props.voucher.paid_amount)) }}</p>
                            </CardContent>
                        </Card>
                        <Card :class="Number(props.voucher.balance_amount) > 0 ? 'bg-destructive/5 border-destructive/20' : 'bg-success/5 border-success/20'">
                            <CardContent class="py-4 text-center">
                                <p class="text-xs font-medium uppercase tracking-wider" :class="Number(props.voucher.balance_amount) > 0 ? 'text-destructive/80' : 'text-success/80'">Balance Due</p>
                                <p class="text-3xl font-bold mt-1" :class="Number(props.voucher.balance_amount) > 0 ? 'text-destructive' : 'text-success'">
                                    {{ formatCurrency(Number(props.voucher.balance_amount)) }}
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Line Items -->
                    <div v-if="props.voucher.line_items && props.voucher.line_items.length > 0">
                        <h3 class="text-lg font-semibold text-foreground">Fee Breakdown</h3>
                        <Card>
                            <CardContent class="p-0">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-border">
                                        <thead class="bg-muted">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Fee Head</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-border">
                                            <tr v-for="(item, index) in props.voucher.line_items" :key="index" class="hover:bg-accent/50">
                                                <td class="px-4 py-3 text-sm text-foreground">{{ item.name }}</td>
                                                <td class="px-4 py-3 text-sm text-right font-medium text-foreground">{{ formatCurrency(Number(item.amount)) }}</td>
                                            </tr>
                                            <tr class="bg-muted/50 font-semibold">
                                                <td class="px-4 py-3 text-sm text-foreground">Total</td>
                                                <td class="px-4 py-3 text-sm text-right text-foreground">{{ formatCurrency(Number(props.voucher.net_amount)) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- School Info -->
                    <div v-if="props.voucher.campus" class="bg-muted/50 rounded-lg p-4">
                        <h4 class="font-medium text-foreground mb-2">{{ props.voucher.campus.name }}</h4>
                        <div class="text-sm text-muted-foreground space-y-1">
                            <p v-if="props.voucher.campus.address">{{ props.voucher.campus.address }}</p>
                            <p v-if="props.voucher.campus.phone">Phone: {{ props.voucher.campus.phone }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Payment History Timeline -->
            <Card v-show="timelineVisible" class="animate-fade-slide-up overflow-hidden">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-lg">Payment History</CardTitle>
                        <span class="text-sm text-muted-foreground">{{ props.voucher.payments?.length ?? 0 }} payment(s)</span>
                    </div>
                </CardHeader>
                <CardContent class="pt-0">
                    <div v-if="props.voucher.payments && props.voucher.payments.length > 0" class="relative pl-4">
                        <div class="absolute left-3 top-0 bottom-0 w-0.5 bg-border" aria-hidden="true" />
                        <div class="space-y-6">
                            <div v-for="(payment, index) in props.voucher.payments" :key="payment.id" class="relative">
                                <div class="absolute left-[-34px] top-1 w-8 h-8 rounded-full bg-primary border-4 border-background flex items-center justify-center z-10">
                                    <Icon icon="check" class="w-4 h-4 text-primary-foreground" />
                                </div>
                                <div class="ms-4 space-y-2">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div>
                                            <p class="font-medium text-foreground">{{ formatCurrency(Number(payment.paid_amount)) }}</p>
                                            <p class="text-sm text-muted-foreground">{{ formatDate(payment.paid_on) }} · {{ payment.payment_method }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm">
                                            <span v-if="payment.reference_no" class="px-2 py-0.5 rounded bg-muted text-muted-foreground">
                                                Ref: {{ payment.reference_no }}
                                            </span>
                                            <span v-if="payment.receipt_no" class="px-2 py-0.5 rounded bg-primary/10 text-primary text-xs font-medium">
                                                Receipt #{{ payment.receipt_no }}
                                            </span>
                                        </div>
                                    </div>
                                    <p v-if="payment.note" class="text-sm text-muted-foreground pl-2 border-l-2 border-border ml-2">{{ payment.note }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-muted-foreground">
                        <Icon icon="credit-card" class="h-10 w-10 mx-auto mb-3 text-muted-foreground/50" />
                        <p>No payments recorded yet.</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Printable Challan (Hidden on screen, shown on print) -->
            <div class="print-only hidden" style="display: none;">
                @media print {
                    .no-print { display: none !important; }
                    .print-only { display: block !important; }
                    body { font-size: 12pt; }
                    .voucher-print { padding: 20px; max-width: 800px; margin: 0 auto; }
                }
                <div class="voucher-print">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold">{{ props.voucher.campus?.name ?? 'School' }}</h2>
                        <p class="text-sm text-muted-foreground">{{ props.voucher.campus?.address ?? '' }}</p>
                        <p class="text-sm text-muted-foreground">Phone: {{ props.voucher.campus?.phone ?? '' }}</p>
                    </div>
                    <hr class="mb-4" />
                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                        <div><strong>Voucher #:</strong> {{ props.voucher.voucher_no }}</div>
                        <div><strong>Period:</strong> {{ monthLabel(props.voucher) }}</div>
                        <div><strong>Student:</strong> {{ props.voucher.student?.name }}</div>
                        <div><strong>Class:</strong> {{ props.voucher.student?.class?.name }} - {{ props.voucher.student?.section?.name }}</div>
                        <div><strong>Due Date:</strong> {{ props.voucher.due_date ? formatDate(props.voucher.due_date) : '—' }}</div>
                        <div><strong>Status:</strong> {{ getStatusConfig(props.voucher.status).label }}</div>
                    </div>
                    <table class="w-full border-collapse mb-4 text-sm">
                        <thead>
                            <tr class="border-b-2">
                                <th class="text-left p-2">Fee Head</th>
                                <th class="text-right p-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in props.voucher.line_items" :key="index" class="border-b">
                                <td class="p-2">{{ item.name }}</td>
                                <td class="p-2 text-right">{{ formatCurrency(Number(item.amount)) }}</td>
                            </tr>
                            <tr class="font-bold border-t-2">
                                <td class="p-2">Total</td>
                                <td class="p-2 text-right">{{ formatCurrency(Number(props.voucher.net_amount)) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div><strong>Net Amount:</strong> {{ formatCurrency(Number(props.voucher.net_amount)) }}</div>
                        <div><strong>Paid:</strong> {{ formatCurrency(Number(props.voucher.paid_amount)) }}</div>
                        <div><strong>Balance:</strong> {{ formatCurrency(Number(props.voucher.balance_amount)) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</PortalLayout>
</template>