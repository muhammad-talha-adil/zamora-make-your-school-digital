<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import SectionsTable from '@/components/tables/SectionsTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    tableSections: any;
    schoolClasses: Array<{ id: number; name: string }>;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'School Setting', href: '/settings/school-hub' },
    { title: 'Sections', href: '/settings/sections' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Sections" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Sections</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Manage the school's sections.</p>
                </div>

                <SectionsTable :sections="tableSections" :school-classes="schoolClasses" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
