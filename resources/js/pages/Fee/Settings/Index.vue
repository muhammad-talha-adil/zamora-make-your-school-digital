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
    { title: 'Settings', href: '/fee/settings' },
];

const settings = [
    {
        title: 'Fee Heads',
        description: 'Manage fee heads and categories',
        icon: 'list',
        color: 'text-blue-600 dark:text-blue-400',
        bgColor: 'bg-blue-100 dark:bg-blue-900/30',
        route: 'fee.settings.fee-heads',
    },
    {
        title: 'Discount Types',
        description: 'Manage discount types and default values',
        icon: 'percent',
        color: 'text-green-600 dark:text-green-400',
        bgColor: 'bg-green-100 dark:bg-green-900/30',
        route: 'fee.settings.discount-types',
    },
    {
        title: 'Fine Rules',
        description: 'Configure late payment fine rules',
        icon: 'alert-circle',
        color: 'text-red-600 dark:text-red-400',
        bgColor: 'bg-red-100 dark:bg-red-900/30',
        route: 'fee.settings.fine-rules',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Settings" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                    Fee Settings
                </h1>
                <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                    Configure fee management settings
                </p>
            </div>

            <!-- Settings Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="setting in settings"
                    :key="setting.route"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow cursor-pointer"
                    @click="router.visit(route(setting.route))"
                >
                    <div class="flex items-start gap-4">
                        <div :class="['p-3 rounded-lg', setting.bgColor]">
                            <Icon :icon="setting.icon" :class="['h-6 w-6', setting.color]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ setting.title }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ setting.description }}
                            </p>
                            <Button variant="link" class="mt-3 p-0 h-auto">
                                Manage
                                <Icon icon="arrow-right" class="ml-1 h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
