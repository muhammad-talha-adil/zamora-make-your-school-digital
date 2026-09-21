<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import ClassSubjectsForm from '@/components/settings/ClassSubjectsForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'School Setting', href: '/settings/school-hub' },
    { title: 'Subjects to Class', href: '/settings/class-subjects/page' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Subjects to Class" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Subjects to Class</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Assign subjects to class sections.</p>
                </div>

                <ClassSubjectsForm />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
