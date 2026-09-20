<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';

interface SettingModule {
    key: string;
    title: string;
    description: string;
    url: string;
}

interface Props {
    modules: SettingModule[];
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'School Setting', href: '/settings/school-hub' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="School Setting" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">
                    School Setting
                </h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Every module's settings, in one place. Pick a module to manage it.
                </p>
            </div>

            <!-- Module cards -->
            <div v-if="modules.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    v-for="module in modules"
                    :key="module.key"
                    :href="module.url"
                    class="block rounded-lg border border-border bg-card p-4 md:p-5 transition-colors hover:border-primary hover:bg-accent"
                >
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="text-sm md:text-base font-semibold text-foreground">{{ module.title }}</h2>
                        <Icon icon="arrow-right" class="h-4 w-4 text-muted-foreground shrink-0" />
                    </div>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">{{ module.description }}</p>
                </Link>
            </div>

            <!-- Empty state -->
            <div v-else class="bg-card rounded-lg border border-border p-8 text-center text-muted-foreground">
                <Icon icon="settings" class="h-10 w-10 mx-auto mb-3 text-muted-foreground" />
                No settings are available to you.
            </div>
        </div>
    </AppLayout>
</template>
