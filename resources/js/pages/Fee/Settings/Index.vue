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
        color: 'text-primary',
        bgColor: 'bg-primary/10',
        route: 'fee.settings.fee-heads',
    },
    {
        title: 'Discount Types',
        description: 'Manage discount types and default values',
        icon: 'percent',
        color: 'text-success',
        bgColor: 'bg-success/10',
        route: 'fee.settings.discount-types',
    },
    {
        title: 'Fine Rules',
        description: 'Configure late payment fine rules',
        icon: 'alert-circle',
        color: 'text-destructive',
        bgColor: 'bg-destructive/10',
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
                <h1 class="text-lg md:text-2xl font-bold text-foreground">
                    Fee Settings
                </h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Configure fee management settings
                </p>
            </div>

            <!-- Settings Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="setting in settings"
                    :key="setting.route"
                    class="bg-card rounded-lg border border-border p-6 hover:shadow-lg transition-shadow cursor-pointer"
                    @click="router.visit(route(setting.route))"
                >
                    <div class="flex items-start gap-4">
                        <div :class="['p-3 rounded-lg', setting.bgColor]">
                            <Icon :icon="setting.icon" :class="['h-6 w-6', setting.color]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-foreground">
                                {{ setting.title }}
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
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
