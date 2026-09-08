<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Purchase Returns" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Purchase Returns</h1>
                    <p class="text-muted-foreground">Return items to suppliers</p>
                </div>
                <Link
                    :href="`/inventory/purchase-returns/create`"
                    class="px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90 transition-colors min-h-11 flex items-center justify-center"
                >
                    New Return
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-card rounded-lg shadow-sm p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Campus</label>
                        <select
                            v-model="filters.campus_id"
                            @change="router.visit(`/inventory/purchase-returns?campus_id=${filters.campus_id || ''}`)"
                            class="w-full border border-border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary min-h-11"
                        >
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Supplier</label>
                        <select
                            v-model="filters.supplier_id"
                            @change="router.visit(`/inventory/purchase-returns?campus_id=${filters.campus_id || ''}&supplier_id=${filters.supplier_id || ''}`)"
                            class="w-full border border-border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary min-h-11"
                        >
                            <option value="">All Suppliers</option>
                            <option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">
                                {{ supplier.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">From Date</label>
                        <input
                            v-model="filters.from_date"
                            type="date"
                            @change="router.visit(`/inventory/purchase-returns?campus_id=${filters.campus_id || ''}&from_date=${filters.from_date || ''}`)"
                            class="w-full border border-border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary min-h-11"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">To Date</label>
                        <input
                            v-model="filters.to_date"
                            type="date"
                            @change="router.visit(`/inventory/purchase-returns?campus_id=${filters.campus_id || ''}&to_date=${filters.to_date || ''}`)"
                            class="w-full border border-border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary min-h-11"
                        />
                    </div>
                </div>
            </div>

            <!-- Returns Table -->
            <div class="table-scroll bg-card rounded-lg shadow-sm">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Return #
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Supplier
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Items
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Total Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Created By
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-card divide-y divide-border">
                        <tr v-for="returnItem in props.returns.data" :key="returnItem.id" class="hover:bg-accent">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-primary">{{ returnItem.return_number }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                {{ new Date(returnItem.return_date).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                {{ returnItem.supplier?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                <!-- Would need to load items count -->
                                <span class="text-muted-foreground">View details</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">
                                {{ formatCurrency(returnItem.total_amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                {{ returnItem.user?.name || 'System' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <Link
                                    :href="`/inventory/purchase-returns/${returnItem.id}`"
                                    class="text-primary hover:text-primary"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="props.returns.data.length === 0" class="px-6 py-12 text-center">
                    <p class="text-muted-foreground">No returns found.</p>
                </div>

                <!-- Pagination -->
                <div v-if="props.returns.links" class="px-6 py-4 border-t border-border flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ props.returns.from }} to {{ props.returns.to }} of {{ props.returns.total }} entries
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Link
                            v-for="link in props.returns.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors min-h-11 flex items-center justify-center',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-card text-muted-foreground hover:bg-accent border border-border'
                            ]"
                            preserve-state
                        >
                            <span v-html="link.label"></span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { formatCurrency } from '@/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Props {
    returns: {
        data: Array<{
            id: number;
            return_number: string;
            return_date: string;
            total_amount: number;
            supplier?: {
                name: string;
            };
            user?: {
                name: string;
            };
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
    suppliers: Array<{
        id: number;
        name: string;
    }>;
    filters?: {
        campus_id?: string;
        supplier_id?: string;
        from_date?: string;
        to_date?: string;
    };
}

const props = defineProps<Props>();

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
        title: 'Purchase Returns',
        href: '/inventory/purchase-returns',
    },
];

const filters = reactive({
    campus_id: props.filters?.campus_id || '',
    supplier_id: props.filters?.supplier_id || '',
    from_date: props.filters?.from_date || '',
    to_date: props.filters?.to_date || '',
});
</script>
