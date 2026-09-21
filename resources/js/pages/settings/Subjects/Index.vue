<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import SubjectsTable from '@/components/tables/SubjectsTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    tableSubjects: any;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'School Setting', href: '/settings/school-hub' },
    { title: 'Subjects', href: '/settings/subjects' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Subjects" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Subjects</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Manage subjects.</p>
                </div>

                <SubjectsTable :subjects="tableSubjects" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
