<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { formatCurrency, formatDate } from '@/utils/format';

interface ChildOption {
    id: number;
    name: string | null;
}

interface Voucher {
    id: number;
    voucher_no: string | null;
    voucher_year: number;
    status: string;
    net_amount: string | number;
    paid_amount: string | number;
    balance_amount: string | number;
    due_date: string | null;
    voucher_month?: { name: string } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    student: ChildOption;
    students: ChildOption[];
    vouchers: Paginated<Voucher>;
}

const props = defineProps<Props>();

const statusClass = (status: string): string => {
    return (
        {
            unpaid: 'bg-warning/10 text-warning',
            partial: 'bg-primary/10 text-primary',
            paid: 'bg-success/10 text-success',
            overdue: 'bg-destructive/10 text-destructive',
            cancelled: 'bg-muted text-muted-foreground',
            adjusted: 'bg-accent text-accent-foreground',
        }[status] ?? 'bg-muted text-muted-foreground'
    );
};

const monthLabel = (voucher: Voucher): string => {
    return [voucher.voucher_month?.name, voucher.voucher_year].filter(Boolean).join(' ');
};
</script>

<template>
    <AppLayout>
        <Head title="My Fee Vouchers" />

        <div class="space-y-4 md:space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">Fee Vouchers</h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Fee vouchers for {{ props.student.name }}.
                    </p>
                </div>

                <label v-if="props.students.length > 1" class="w-full sm:w-auto">
                    <span class="sr-only">Choose child</span>
                    <select
                        :value="props.student.id"
                        class="w-full sm:w-auto rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                        @change="
                            (event) => router.visit(route('portal.fees.index', { student_id: (event.target as HTMLSelectElement).value }))
                        "
                    >
                        <option v-for="child in props.students" :key="child.id" :value="child.id">
                            {{ child.name }}
                        </option>
                    </select>
                </label>
            </div>

            <!-- Empty state -->
            <div v-if="props.vouchers.data.length === 0" class="bg-card rounded-lg border border-border p-8 text-center text-muted-foreground">
                <Icon icon="wallet" class="h-10 w-10 mx-auto mb-3 text-muted-foreground" />
                No fee vouchers found.
            </div>

            <template v-else>
                <!-- Mobile Card View -->
                <div class="block lg:hidden space-y-3">
                    <div v-for="voucher in props.vouchers.data" :key="voucher.id" class="bg-card rounded-lg border border-border p-4 space-y-3">
                        <div class="flex flex-wrap gap-2 justify-between items-start">
                            <div>
                                <div class="font-medium text-foreground">{{ voucher.voucher_no }}</div>
                                <div class="text-xs text-muted-foreground">{{ monthLabel(voucher) }}</div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full shrink-0" :class="statusClass(voucher.status)">
                                {{ voucher.status }}
                            </span>
                        </div>

                        <div class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
                            <div class="flex items-center justify-between">
                                <span>Net amount</span>
                                <span class="text-foreground">{{ formatCurrency(Number(voucher.net_amount)) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Paid</span>
                                <span class="text-foreground">{{ formatCurrency(Number(voucher.paid_amount)) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Balance</span>
                                <span class="font-semibold" :class="Number(voucher.balance_amount) > 0 ? 'text-destructive' : 'text-success'">
                                    {{ formatCurrency(Number(voucher.balance_amount)) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Due date</span>
                                <span class="text-foreground">{{ voucher.due_date ? formatDate(voucher.due_date) : '—' }}</span>
                            </div>
                        </div>

                        <Link :href="route('portal.fees.show', voucher.id)" class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                            View Voucher
                            <Icon icon="arrow-right" :size="14" />
                        </Link>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Voucher #</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Month/Year</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Net</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Paid</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Balance</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Due Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="voucher in props.vouchers.data" :key="voucher.id" class="transition-colors hover:bg-accent">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">{{ voucher.voucher_no }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ monthLabel(voucher) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full" :class="statusClass(voucher.status)">
                                            {{ voucher.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-muted-foreground">{{ formatCurrency(Number(voucher.net_amount)) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-muted-foreground">{{ formatCurrency(Number(voucher.paid_amount)) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold" :class="Number(voucher.balance_amount) > 0 ? 'text-destructive' : 'text-success'">
                                        {{ formatCurrency(Number(voucher.balance_amount)) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ voucher.due_date ? formatDate(voucher.due_date) : '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <Link :href="route('portal.fees.show', voucher.id)" class="text-sm font-medium text-primary hover:underline">View</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <div v-if="props.vouchers.links.length > 3" class="flex flex-wrap gap-1">
                <template v-for="link in props.vouchers.links" :key="`${link.label}-${link.url ?? 'disabled'}`">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded px-3 py-1 text-sm border border-border"
                        :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'text-foreground'"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span v-else class="rounded px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
