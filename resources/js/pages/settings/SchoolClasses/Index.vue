<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import SchoolClassesTable from '@/components/tables/SchoolClassesTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    tableSchoolClasses: any;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'School Setting', href: '/settings/school-hub' },
    { title: 'Classes', href: '/settings/school-classes' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Classes" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Classes</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Manage the school's classes.</p>
                </div>

                <SchoolClassesTable :classes="tableSchoolClasses" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
