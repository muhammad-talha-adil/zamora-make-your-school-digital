<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="School Profile" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        School Profile
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage school information and related data.
                    </p>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-700 overflow-x-auto overflow-hidden">
                    <nav class="-mb-px flex space-x-4 md:space-x-8 min-w-0">
                        <button
                            @click="activeTab = 'school-info'"
                            :class="[
                                activeTab === 'school-info'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            School Profile
                        </button>
                        <button
                            @click="activeTab = 'campuses'"
                            :class="[
                                activeTab === 'campuses'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Campuses
                        </button>
                        <button
                            @click="activeTab = 'classes'"
                            :class="[
                                activeTab === 'classes'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Classes
                        </button>
                        <button
                            @click="activeTab = 'sections'"
                            :class="[
                                activeTab === 'sections'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Sections
                        </button>
                        <button
                            @click="activeTab = 'sessions'"
                            :class="[
                                activeTab === 'sessions'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Sessions
                        </button>
                        <button
                            @click="activeTab = 'subjects'"
                            :class="[
                                activeTab === 'subjects'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Subjects
                        </button>
                        <button
                            @click="activeTab = 'subjects-to-class'"
                            :class="[
                                activeTab === 'subjects-to-class'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
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
                <div v-if="activeTab === 'classes'">
                    <SchoolClassesTable :classes="classes" />
                </div>

                <!-- Sections Tab -->
                <div v-if="activeTab === 'sections'">
                    <SectionsTable :sections="sections" :school-classes="allClasses" />
                </div>

                <!-- Sessions Tab -->
                <div v-if="activeTab === 'sessions'">
                    <AcademicSessionsTable :sessions="sessions" />
                </div>

                <!-- Subjects Tab -->
                <div v-if="activeTab === 'subjects'">
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
