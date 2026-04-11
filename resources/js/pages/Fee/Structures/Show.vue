<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';

interface FeeStructure {
    id: number;
    title: string;
    session: { id: number; name: string };
    campus: { id: number; name: string };
    class?: { id: number; name: string };
    section?: { id: number; name: string };
    status: string;
    effective_from: string;
    effective_to?: string;
    notes?: string;
    created_by?: { id: number; name: string };
    items: Array<{
        id: number;
        fee_head: { id: number; name: string };
        amount: number;
        type: string;
        frequency: string;
    }>;
}

interface Props {
    structure: FeeStructure;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Fee Structures', href: '/fee/structures' },
    { title: props.structure.title, href: `/fee/structures/${props.structure.id}` },
];

const getStatusColor = (status: string) => {
    const colors = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        draft: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return colors[status] || colors.inactive;
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'PKR',
    }).format(amount);
};

const totalAmount = computed(() => {
    return props.structure.items?.reduce((sum, item) => sum + (item.amount || 0), 0) || 0;
});

const editStructure = () => {
    router.visit(route('fee.structures.edit', props.structure.id));
};

const goBack = () => {
    router.visit(route('fee.structures.index'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems" :key="structure.id">
        <Head :title="structure.title" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        {{ structure.title ?? 'N/A' }}
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Fee Structure Details
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="goBack">
                        <Icon icon="arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <Button @click="editStructure">
                        <Icon icon="edit" class="mr-2 h-4 w-4" />
                        Edit Structure
                    </Button>
                </div>
            </div>

            <!-- Details Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Title -->
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Title</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ structure.title ?? 'N/A' }}</p>
                    </div>

                    <!-- Status -->
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full inline-block', getStatusColor(structure.status)]">
                            {{ structure.status ?? 'N/A' }}
                        </span>
                    </div>

                    <!-- Session -->
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Session</p>
                        <p class="text-base text-gray-900 dark:text-white">{{ structure.session?.name || 'N/A' }}</p>
                    </div>

                    <!-- Campus -->
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Campus</p>
                        <p class="text-base text-gray-900 dark:text-white">{{ structure.campus?.name || 'N/A' }}</p>
                    </div>

                    <!-- Class -->
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Class</p>
                        <p class="text-base text-gray-900 dark:text-white">{{ structure.class?.name || 'All Classes' }}</p>
                    </div>

                    <!-- Section -->
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Section</p>
                        <p class="text-base text-gray-900 dark:text-white">{{ structure.section?.name || 'All Sections' }}</p>
                    </div>

                    <!-- Effective From -->
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Effective From</p>
                        <p class="text-base text-gray-900 dark:text-white">{{ formatDate(structure.effective_from) }}</p>
                    </div>

                    <!-- Effective To -->
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Effective To</p>
                        <p class="text-base text-gray-900 dark:text-white">{{ structure.effective_to ? formatDate(structure.effective_to) : 'Indefinite' }}</p>
                    </div>

                    <!-- Created By -->
                    <div v-if="structure.created_by" class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By</p>
                        <p class="text-base text-gray-900 dark:text-white">{{ structure.created_by.name }}</p>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="structure.notes" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Notes</p>
                    <p class="text-base text-gray-900 dark:text-white whitespace-pre-wrap">{{ structure.notes }}</p>
                </div>
            </div>

            <!-- Fee Items -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Fee Items</h2>
                    <span class="text-sm text-gray-500">Total: {{ formatCurrency(totalAmount) }}</span>
                </div>

                <div v-if="structure.items && structure.items.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Fee Head
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Type
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Frequency
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="item in structure.items" :key="item.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ item.fee_head?.name || 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ item.type || 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ item.frequency || 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap text-right">
                                    <div class="text-gray-900 dark:text-white">{{ formatCurrency(item.amount) }}</div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">
                                    Total
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white text-right">
                                    {{ formatCurrency(totalAmount) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-else class="text-center py-8 text-gray-500">
                    No fee items added yet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
