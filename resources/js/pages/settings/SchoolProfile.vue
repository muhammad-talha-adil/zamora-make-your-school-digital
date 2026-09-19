<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import SchoolForm from '@/components/forms/SchoolForm.vue';
import AcademicSessionsTable from '@/components/tables/AcademicSessionsTable.vue';
import CampusesTable from '@/components/tables/CampusesTable.vue';
import SchoolClassesTable from '@/components/tables/SchoolClassesTable.vue';
import SectionsTable from '@/components/tables/SectionsTable.vue';
import SubjectsTable from '@/components/tables/SubjectsTable.vue';
import ClassSubjectsForm from '@/components/settings/ClassSubjectsForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    campuses: any;
    campusTypes: any;
    school: any;
    classes: any;
    allClasses: Array<{ id: number; name: string }>;
    sections: any;
    sessions: any;
    subjects: any;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'School Profile',
        href: '/settings/school-profile',
    },
];

const activeTab = ref('school-info');

// The Classes tab creates/edits/deletes classes via its own axios calls, not
// an Inertia visit, so the `classes` and `allClasses` props this page was
// rendered with never picked up the change on their own — leaving the
// Sections tab's "Select Class" dropdown (fed by `allClasses`) stale until a
// full page reload. Refresh both shared props in place whenever the Classes
// tab reports a change, so every tab reading class data sees it immediately.
const refreshClasses = () => {
    router.reload({ only: ['classes', 'allClasses'] });
};
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
                        Manage school information and related data.
                    </p>
                </div>

                <!-- Tabs -->
                <div class="border-b border-border overflow-x-auto overflow-hidden">
                    <nav class="-mb-px flex space-x-4 md:space-x-8 min-w-0">
                        <button
                            @click="activeTab = 'school-info'"
                            :class="[
                                activeTab === 'school-info'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            School Profile
                        </button>
                        <button
                            @click="activeTab = 'campuses'"
                            :class="[
                                activeTab === 'campuses'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Campuses
                        </button>
                        <button
                            @click="activeTab = 'classes'"
                            :class="[
                                activeTab === 'classes'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Classes
                        </button>
                        <button
                            @click="activeTab = 'sections'"
                            :class="[
                                activeTab === 'sections'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Sections
                        </button>
                        <button
                            @click="activeTab = 'sessions'"
                            :class="[
                                activeTab === 'sessions'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Sessions
                        </button>
                        <button
                            @click="activeTab = 'subjects'"
                            :class="[
                                activeTab === 'subjects'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Subjects
                        </button>
                        <button
                            @click="activeTab = 'subjects-to-class'"
                            :class="[
                                activeTab === 'subjects-to-class'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Subjects to Class
                        </button>
                    </nav>
                </div>

                <!-- School Info Tab -->
                <div v-if="activeTab === 'school-info'">
                    <SchoolForm :school="school" />
                </div>

                <!-- Campuses Tab -->
                <div v-if="activeTab === 'campuses'">
                    <CampusesTable
                        :campuses="campuses"
                        :campus-types="campusTypes"
                    />
                </div>

                <!-- Classes Tab -->
                <div v-show="activeTab === 'classes'">
                    <SchoolClassesTable :classes="classes" @saved="refreshClasses" />
                </div>

                <!-- Sections Tab -->
                <div v-show="activeTab === 'sections'">
                    <SectionsTable :sections="sections" :school-classes="allClasses" />
                </div>

                <!-- Sessions Tab -->
                <div v-show="activeTab === 'sessions'">
                    <AcademicSessionsTable :sessions="sessions" />
                </div>

                <!-- Subjects Tab -->
                <div v-show="activeTab === 'subjects'">
                    <SubjectsTable :subjects="subjects" />
                </div>

                <!-- Subjects to Class Tab -->
                <div v-if="activeTab === 'subjects-to-class'">
                    <ClassSubjectsForm />
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
