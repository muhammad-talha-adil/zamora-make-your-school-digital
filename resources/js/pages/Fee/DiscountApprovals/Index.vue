<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { alert } from '@/utils';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import RowAction from '@/components/tables/RowAction.vue';
import axios from 'axios';

interface PendingDiscount {
    id: number;
    student_name: string | null;
    discount_type: string | null;
    value_type: string;
    value: number;
    campus: string | null;
    effective_from: string | null;
    reason: string | null;
    created_at: string | null;
}

interface Props {
    pendingDiscounts: PendingDiscount[];
}

const props = defineProps<Props>();

const discounts = ref<PendingDiscount[]>(props.pendingDiscounts);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Discount Approvals', href: '/fee/discount-approvals' },
];

const formatValue = (type: string, value: number) => {
    if (type === 'percent') {
        return `${value}%`;
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PKR' }).format(value);
};

const removeFromList = (id: number) => {
    discounts.value = discounts.value.filter((d) => d.id !== id);
};

const approveDiscount = (discount: PendingDiscount) => {
    alert
        .confirm(
            `Approve the ${discount.discount_type ?? 'discount'} for ${discount.student_name ?? 'this student'}?`,
            'Approve Discount',
            'Yes, approve it!',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios
                    .patch(route('fee.discount-approvals.approve', discount.id), {}, { headers: { Accept: 'application/json' } })
                    .then(() => {
                        alert.success('Discount approved successfully.');
                        removeFromList(discount.id);
                    })
                    .catch((error) => {
                        alert.error(error.response?.data?.message || 'Failed to approve discount.');
                    });
            }
        });
};

const rejectDiscount = (discount: PendingDiscount) => {
    alert
        .confirm(
            `Reject the ${discount.discount_type ?? 'discount'} for ${discount.student_name ?? 'this student'}?`,
            'Reject Discount',
            'Yes, reject it!',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios
                    .patch(route('fee.discount-approvals.reject', discount.id), {}, { headers: { Accept: 'application/json' } })
                    .then(() => {
                        alert.success('Discount rejected.');
                        removeFromList(discount.id);
                    })
                    .catch((error) => {
                        alert.error(error.response?.data?.message || 'Failed to reject discount.');
                    });
            }
        });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Discount Approvals" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">Discount Approvals</h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Student discounts awaiting sign-off from Owner or Principal.
                </p>
            </div>

            <!-- Mobile Card View -->
            <div class="block lg:hidden space-y-3">
                <div v-for="discount in discounts" :key="discount.id" class="bg-card rounded-lg border border-border p-4 space-y-2">
                    <div class="flex flex-wrap gap-2 justify-between items-start">
                        <div>
                            <div class="font-medium text-foreground">{{ discount.student_name }}</div>
                            <div class="text-xs text-muted-foreground">{{ discount.discount_type }} · {{ discount.campus }}</div>
                        </div>
                        <span class="text-sm font-medium text-foreground">{{ formatValue(discount.value_type, discount.value) }}</span>
                    </div>
                    <div class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
                        <div>Effective from: {{ discount.effective_from }}</div>
                        <div v-if="discount.reason">Reason: {{ discount.reason }}</div>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <RowAction kind="approve" @click="approveDiscount(discount)" />
                        <RowAction kind="reject" @click="rejectDiscount(discount)" />
                    </div>
                </div>
                <div v-if="discounts.length === 0" class="text-center py-8 text-muted-foreground">
                    No discounts are awaiting approval.
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Student</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Discount Type</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Value</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Campus</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Applied</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="discount in discounts" :key="discount.id" class="transition-colors hover:bg-accent">
                                <td class="px-4 py-3 text-sm font-medium text-foreground">{{ discount.student_name }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ discount.discount_type }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatValue(discount.value_type, discount.value) }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ discount.campus }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ discount.created_at }}</td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        <RowAction kind="approve" @click="approveDiscount(discount)" />
                                        <RowAction kind="reject" @click="rejectDiscount(discount)" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="discounts.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">No discounts are awaiting approval.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
