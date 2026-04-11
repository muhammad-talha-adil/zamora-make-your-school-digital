<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

interface CategorySummary {
    category_id: number;
    total: number;
    category?: { name: string };
}

interface Props {
    fromDate?: string;
    toDate?: string;
    byCategory?: CategorySummary[];
    totalIncome?: number;
    campuses?: Array<{ id: number; name: string }>;
    selectedCampus?: number;
}

const props = defineProps<Props>();

// Computed
const byCategory = computed(() => props.byCategory || []);
const totalIncome = computed(() => props.totalIncome || 0);
const campuses = computed(() => props.campuses || []);

// Form state
const fromDate = ref(props.fromDate || new Date().toISOString().split('T')[0]);
const toDate = ref(props.toDate || new Date().toISOString().split('T')[0]);
const selectedCampus = ref(props.selectedCampus);

// Breadcrumbs
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Reports', href: '/finance/reports' },
    { title: 'Income Statement' },
];

// Format currency
const formatMoney = (amount: number) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

// Load report
const loadReport = () => {
    const params: Record<string, string> = {
        from_date: fromDate.value,
        to_date: toDate.value,
    };
    if (selectedCampus.value) params.campus_id = String(selectedCampus.value);
    router.get('/finance/reports/income', params);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Income Statement" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                    Income Statement
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Income report by category
                </p>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                        <input type="date" v-model="fromDate" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                        <input type="date" v-model="toDate" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Campus</label>
                        <select v-model="selectedCampus" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            <option :value="undefined">All Campuses</option>
                            <option v-for="campus in campuses" :key="campus.id" :value="campus.id">{{ campus.name }}</option>
                        </select>
                    </div>
                    <Button @click="loadReport">Load Report</Button>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Income</p>
                <p class="text-3xl font-bold text-green-600">{{ formatMoney(totalIncome) }}</p>
            </div>

            <!-- By Category Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">% of Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in byCategory" :key="item.category_id" class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="px-4 py-3 text-sm">{{ item.category?.name || 'Unknown' }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-green-600">{{ formatMoney(item.total) }}</td>
                                <td class="px-4 py-3 text-sm text-right">{{ totalIncome > 0 ? ((item.total / totalIncome) * 100).toFixed(1) : 0 }}%</td>
                            </tr>
                            <tr v-if="byCategory.length === 0">
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">No income data</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <td class="px-4 py-3 font-bold">Total</td>
                                <td class="px-4 py-3 text-right font-bold text-green-600">{{ formatMoney(totalIncome) }}</td>
                                <td class="px-4 py-3 text-right">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
