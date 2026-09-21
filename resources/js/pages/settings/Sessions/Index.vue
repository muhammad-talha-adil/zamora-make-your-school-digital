<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AcademicSessionsTable from '@/components/tables/AcademicSessionsTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    tableSessions: any;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'School Setting', href: '/settings/school-hub' },
    { title: 'Sessions', href: '/settings/sessions' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Sessions" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Sessions</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Manage academic sessions.</p>
                </div>

                <AcademicSessionsTable :sessions="tableSessions" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
