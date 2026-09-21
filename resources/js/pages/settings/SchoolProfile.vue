<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import SchoolForm from '@/components/forms/SchoolForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    school: any;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'School Profile',
        href: '/settings/school-profile',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="School Profile" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1
                        class="text-2xl font-bold text-foreground"
                    >
                        School Profile
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Manage school information.
                    </p>
                </div>

                <SchoolForm :school="school" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
