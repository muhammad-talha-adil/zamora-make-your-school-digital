<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import TablePagination from '@/components/tables/TablePagination.vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import Icon from '@/components/Icon.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { formatCurrency, formatDate } from '@/utils/format';
import { ref, computed, onMounted } from 'vue';

interface ChildOption {
    id: number;
    name: string | null;
    class?: string | null;
    section?: string | null;
    avatar?: string | null;
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

const monthLabel = (voucher: Voucher): string => {
    return [voucher.voucher_month?.name, voucher.voucher_year].filter(Boolean).join(' ');
};

const breadcrumbs = computed(() => [
    { title: 'Fees', href: route('portal.fees.index') },
]);

const listVisible = ref(false);
const cardVisible = ref(false);
const isLoading = ref(false);

onMounted(() => {
    requestAnimationFrame(() => {
        listVisible.value = true;
        setTimeout(() => cardVisible.value = true, 60);
    });
});
</script>

<template>
<PortalLayout :breadcrumbs="breadcrumbs" :current-child="props.student" :children="props.students">
    <template #default>
        <Head title="Fee Vouchers" />
        <div class="space-y-6 animate-fade-slide-up">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-foreground">Fee Vouchers</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        All fee vouchers for {{ props.student.name }}.
                    </p>
                </div>
            </div>

            <!-- Summary Stats -->
            <div v-if="props.vouchers.data.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3 animate-fade-slide-up stagger-1">
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-success">{{ props.vouchers.data.filter(v => v.status === 'paid').length }}</p>
                        <p class="text-xs text-muted-foreground">Paid</p>
                    </CardContent>
                </Card>
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-warning">{{ props.vouchers.data.filter(v => v.status === 'partial').length }}</p>
                        <p class="text-xs text-muted-foreground">Partial</p>
                    </CardContent>
                </Card>
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-destructive">{{ props.vouchers.data.filter(v => ['unpaid', 'overdue'].includes(v.status)).length }}</p>
                        <p class="text-xs text-muted-foreground">Due</p>
                    </CardContent>
                </Card>
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-primary">
                            {{ formatCurrency(props.vouchers.data.reduce((sum, v) => sum + Number(v.balance_amount), 0)) }}
                        </p>
                        <p class="text-xs text-muted-foreground">Total Balance</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-if="props.vouchers.data.length === 0" class="animate-fade-slide-up stagger-2">
                <Card class="bg-muted/50 border-dashed">
                    <CardContent class="py-16 text-center">
                        <Icon icon="wallet" class="h-16 w-16 mx-auto text-muted-foreground/50 mb-4" />
                        <h3 class="text-lg font-semibold text-foreground mb-1">No fee vouchers yet</h3>
                        <p class="text-sm text-muted-foreground mb-6">
                            Vouchers will appear here once they are generated by the school.
                        </p>
                        <div class="flex items-center justify-center gap-2 text-sm text-muted-foreground">
                            <Icon icon="info" :size="16" />
                            <span>Check back later or contact the finance office.</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Voucher List -->
            <template v-else>
                <!-- Mobile Card View -->
                <div v-show="cardVisible" class="block lg:hidden space-y-3 animate-fade-slide-up stagger-2">
                    <Card
                        v-for="voucher in props.vouchers.data"
                        :key="voucher.id"
                        class="card-interactive overflow-hidden"
                    >
                        <CardContent class="p-4 space-y-3">
                            <div class="flex flex-wrap gap-2 justify-between items-start">
                                <div>
                                    <p class="font-medium text-foreground">{{ voucher.voucher_no }}</p>
                                    <p class="text-xs text-muted-foreground">{{ monthLabel(voucher) }}</p>
                                </div>
                                <Badge :variant="getStatusConfig(voucher.status).variant" class="gap-1 shrink-0">
                                    <Icon :icon="getStatusConfig(voucher.status).icon" :size="12" />
                                    {{ getStatusConfig(voucher.status).label }}
                                </Badge>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-sm pt-2 border-t">
                                <div>
                                    <p class="text-muted-foreground">Net Amount</p>
                                    <p class="font-medium text-foreground">{{ formatCurrency(Number(voucher.net_amount)) }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Paid</p>
                                    <p class="font-medium text-success">{{ formatCurrency(Number(voucher.paid_amount)) }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Balance</p>
                                    <p class="font-semibold" :class="Number(voucher.balance_amount) > 0 ? 'text-destructive' : 'text-success'">
                                        {{ formatCurrency(Number(voucher.balance_amount)) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Due Date</p>
                                    <p class="font-medium text-foreground">{{ voucher.due_date ? formatDate(voucher.due_date) : '—' }}</p>
                                </div>
                            </div>

                            <Link :href="route('portal.fees.show', voucher.id)" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline w-full justify-center py-2">
                                View Voucher
                                <Icon icon="arrow-right" :size="14" />
                            </Link>
                        </CardContent>
                    </Card>
                </div>

                <!-- Desktop Table View -->
                <div v-show="listVisible" class="hidden lg:block animate-fade-slide-up stagger-2">
                    <Card class="overflow-hidden">
                        <CardContent class="p-0">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-border">
                                    <thead class="bg-muted">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Voucher #</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Period</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Net Amount</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Paid</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Balance</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Due Date</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border bg-card">
                                        <tr
                                            v-for="voucher in props.vouchers.data"
                                            :key="voucher.id"
                                            class="transition-colors hover:bg-accent/50"
                                        >
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">{{ voucher.voucher_no }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ monthLabel(voucher) }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <Badge :variant="getStatusConfig(voucher.status).variant" class="gap-1">
                                                    <Icon :icon="getStatusConfig(voucher.status).icon" :size="11" />
                                                    {{ getStatusConfig(voucher.status).label }}
                                                </Badge>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-muted-foreground">{{ formatCurrency(Number(voucher.net_amount)) }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-muted-foreground">{{ formatCurrency(Number(voucher.paid_amount)) }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold" :class="Number(voucher.balance_amount) > 0 ? 'text-destructive' : 'text-success'">
                                                {{ formatCurrency(Number(voucher.balance_amount)) }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ voucher.due_date ? formatDate(voucher.due_date) : '—' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                                <Link :href="route('portal.fees.show', voucher.id)" class="text-sm font-medium text-primary hover:underline flex items-center gap-1 justify-end">
                                                    View
                                                    <Icon icon="arrow-right" :size="12" />
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <TablePagination
                    :pagination="props.vouchers"
                    :show-per-page-selector="false"
                    use-links
                    class="animate-fade-slide-up stagger-3"
                />
            </template>

            <!-- Skeleton Loading State -->
            <div v-if="isLoading" class="space-y-3 animate-fade-slide-up">
                <Skeleton class="h-8 w-48" />
                <Skeleton class="h-4 w-64" />
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <Skeleton class="h-20" v-for="i in 4" :key="i" />
                </div>
                <Card class="overflow-hidden">
                    <Skeleton class="h-12 w-full" />
                    <div class="divide-y divide-border">
                        <Skeleton class="h-12 w-full" v-for="i in 5" :key="i" />
                    </div>
                </Card>
            </div>
        </div>
    </template>
</PortalLayout>
</template>