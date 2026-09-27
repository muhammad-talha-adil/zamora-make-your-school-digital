<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

import SchoolForm from '@/components/forms/SchoolForm.vue';
import WebsiteContentForm from '@/components/forms/WebsiteContentForm.vue';
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

const tabs = [
    { id: 'profile', label: 'Profile' },
    { id: 'website-content', label: 'Website Content' },
];

const activeTab = ref(tabs[0].id);
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
                        Manage school information and the public website's content.
                    </p>
                </div>

                <div class="border-b border-border overflow-x-auto overflow-hidden">
                    <nav class="-mb-px flex space-x-4 md:space-x-8 min-w-0">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            type="button"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- Kept mounted with v-show so switching tabs never discards
                     in-progress form state, matching Fee Settings' pattern. -->
                <div v-show="activeTab === 'profile'">
                    <SchoolForm :school="school" />
                </div>
                <div v-show="activeTab === 'website-content'">
                    <WebsiteContentForm :school="school" />
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
