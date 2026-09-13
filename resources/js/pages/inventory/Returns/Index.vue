<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { formatCurrency } from '@/utils';
import { ref, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';

interface Props {
    returns: {
        data: Array<{
            id: number;
            campus_id: number;
            campus_name: string;
            student_inventory_id: number;
            student_name: string;
            registration_number: string;
            item_name_snapshot: string;
            quantity: number;
            unit_price_snapshot: number;
            discount_snapshot: any;
            return_date: string;
            total_value: number;
            created_at: string;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
    };
    campuses: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

const perPage = ref(10);
const campusFilter = ref('');
const returnsData = ref(props.returns.data || []);
const pagination = ref(props.returns);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Inventory',
        href: '/inventory',
    },
    {
        title: 'Returns',
        href: '/inventory/returns',
    },
];

const fetchReturns = (pageNum = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: pageNum.toString(),
    });

    if (campusFilter.value) {
        params.append('campus_id', campusFilter.value);
    }

    axios.get(`/inventory/returns/all?${params}`).then((response) => {
        returnsData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch([perPage, campusFilter], () => {
    fetchReturns();
});

watch(() => props.returns, (newReturns) => {
    returnsData.value = newReturns.data || [];
    pagination.value = newReturns;
}, { deep: true });

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const calculateFinalPrice = (item: any) => {
    let price = item.unit_price_snapshot;
    const discount = item.discount_snapshot;
    if (discount) {
        if (discount.discount_percentage > 0) {
            price = price - (price * (discount.discount_percentage / 100));
        } else if (discount.discount_amount > 0) {
            price = price - discount.discount_amount;
        }
    }
    return price.toFixed(2);
};

const viewReturn = (returnItem: any) => {
    router.visit(`/inventory/returns/${returnItem.id}`);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Inventory Returns" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">
                        Inventory Returns
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Track all returned inventory items from students.
                    </p>
                </div>
                <Button onclick="window.location.href='/inventory/returns/create'" class="min-h-11">
                    <Icon icon="rotate-ccw" class="mr-2" />
                    Process Return
                </Button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-card rounded-lg p-4 shadow-sm border border-border">
                    <div class="text-sm text-muted-foreground">Total Returns</div>
                    <div class="text-2xl font-bold">{{ pagination.total }}</div>
                </div>
                <div class="bg-card rounded-lg p-4 shadow-sm border border-border">
                    <div class="text-sm text-muted-foreground">Items Returned</div>
                    <div class="text-2xl font-bold">
                        {{ (returnsData || []).reduce((sum, r) => sum + (r.quantity || 0), 0).toLocaleString() }}
                    </div>
                </div>
                <div class="bg-card rounded-lg p-4 shadow-sm border border-border">
                    <div class="text-sm text-muted-foreground">Total Value</div>
                    <div class="text-2xl font-bold text-success">
                        {{ formatCurrency((returnsData || []).reduce((sum, r) => sum + (r.total_value || 0), 0)) }}
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <select v-model="campusFilter" class="w-full sm:w-48 rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-11">
                    <option value="">All Campuses</option>
                    <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                        {{ campus.name }}
                    </option>
                </select>
            </div>

            <div class="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">#</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Student</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Item</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Qty</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Unit Price</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Total</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Date</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(returnItem, index) in returnsData" :key="returnItem.id" class="transition-colors hover:bg-accent">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ (pagination.from || 0) + index }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-foreground">{{ returnItem.student_name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ returnItem.registration_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                    {{ returnItem.item_name_snapshot }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-foreground">
                                    {{ returnItem.quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ formatCurrency(calculateFinalPrice(returnItem)) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-success">
                                    {{ formatCurrency(returnItem.total_value) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ formatDate(returnItem.return_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <Button variant="outline" size="sm" @click="viewReturn(returnItem)" class="min-h-9">
                                        <Icon icon="eye" class="mr-1" />View
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="text-sm text-muted-foreground">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                </div>
                <div class="flex flex-wrap gap-1">
                    <Button
                        v-for="link in pagination.links"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="link.url && fetchReturns(parseInt(link.url.split('=').pop() || '1'))"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
