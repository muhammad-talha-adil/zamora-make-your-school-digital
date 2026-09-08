<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { route } from 'ziggy-js';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Reports', href: '/fee/reports' },
];

const reports = [
    {
        title: 'Collection Report',
        description: 'View fee collection summary by date, campus, and class',
        icon: 'dollar-sign',
        color: 'text-success',
        bgColor: 'bg-success/10',
        route: 'fee.reports.collection',
    },
    {
        title: 'Outstanding Report',
        description: 'View outstanding balances by student, class, and campus',
        icon: 'alert-circle',
        color: 'text-warning',
        bgColor: 'bg-warning/10',
        route: 'fee.reports.outstanding',
    },
    {
        title: 'Defaulters List',
        description: 'View list of students with overdue payments',
        icon: 'alert-triangle',
        color: 'text-destructive',
        bgColor: 'bg-destructive/10',
        route: 'fee.reports.defaulters',
    },
    {
        title: 'Payment Method Report',
        description: 'View collection breakdown by payment method',
        icon: 'credit-card',
        color: 'text-primary',
        bgColor: 'bg-primary/10',
        route: 'fee.reports.payment-methods',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Reports" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">
                    Fee Reports
                </h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Generate and view various fee-related reports
                </p>
            </div>

            <!-- Reports Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="report in reports"
                    :key="report.route"
                    class="bg-card rounded-lg border border-border p-6 hover:shadow-lg transition-shadow cursor-pointer"
                    @click="router.visit(route(report.route))"
                >
                    <div class="flex items-start gap-4">
                        <div :class="['p-3 rounded-lg', report.bgColor]">
                            <Icon :icon="report.icon" :class="['h-6 w-6', report.color]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-foreground">
                                {{ report.title }}
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ report.description }}
                            </p>
                            <Button variant="link" class="mt-3 p-0 h-auto">
                                View Report
                                <Icon icon="arrow-right" class="ml-1 h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
