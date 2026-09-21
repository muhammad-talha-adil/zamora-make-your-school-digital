<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import CampusesTable from '@/components/tables/CampusesTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    tableCampuses: any;
    campusTypes: any;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'School Setting', href: '/settings/school-hub' },
    { title: 'Campuses', href: '/settings/campuses' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Campuses" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Campuses</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Manage the school's campuses.</p>
                </div>

                <CampusesTable :campuses="tableCampuses" :campus-types="campusTypes" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
