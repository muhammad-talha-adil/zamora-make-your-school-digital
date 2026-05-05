<script setup lang="ts">
import SectionForm from '@/components/forms/SectionForm.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { alert } from '@/utils';
import axios from 'axios';
import { ref, watch } from 'vue';

interface Props {
    sections: any;
    schoolClasses?: { id: number; name: string }[];
}

const props = defineProps<Props>();
const EMPTY_VALUE = '-';

const statusFilter = ref('');
const classFilter = ref('');
const perPage = ref(10);
const sectionsData = ref(props.sections.data || []);
const pagination = ref(props.sections);

const fetchSections = (page = pagination.value?.current_page || 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    if (classFilter.value) {
        params.append('class_id', classFilter.value);
    }

    axios.get(`/settings/sections/all?${params}`).then((response) => {
        sectionsData.value = response.data.data;
        pagination.value = response.data;
    });
};

const getRowNumber = (index: number) =>
    ((pagination.value?.from || 1) - 1) + index + 1;

const getPageFromUrl = (url: string | null) => {
    if (!url) {
        return null;
    }

    const page = new URL(url, window.location.origin).searchParams.get('page');
    return page ? Number.parseInt(page, 10) : 1;
};

watch([statusFilter, classFilter, perPage], () => {
    fetchSections(1);
});

watch(
    () => props.sections,
    (newSections) => {
        sectionsData.value = newSections.data || [];
        pagination.value = newSections;
    },
    { deep: true },
);

const inactivateSection = (section: any) => {
    alert
        .confirm(
            `Are you sure you want to deactivate "${section.name}"?`,
            'Deactivate Section',
            'Yes, deactivate it!',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.patch(`/settings/sections/${section.id}/inactivate`, {}, {
                    headers: { Accept: 'application/json' },
                }).then(() => {
                    alert.success('Section deactivated successfully!');
                    fetchSections(pagination.value?.current_page || 1);
                }).catch(() => {
                    alert.error('Failed to deactivate section. Please try again.');
                });
            }
        });
};

const activateSection = (section: any) => {
    axios.patch(`/settings/sections/${section.id}/activate`, {}, {
        headers: { Accept: 'application/json' },
    }).then(() => {
        alert.success('Section activated successfully!');
        fetchSections(pagination.value?.current_page || 1);
    }).catch(() => {
        alert.error('Failed to activate section. Please try again.');
    });
};

const deleteSection = (section: any) => {
    alert.confirm(
        `Are you sure you want to delete "${section.name}"?`,
        'Delete Section',
    ).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/settings/sections/${section.id}`, {
                headers: { Accept: 'application/json' },
            }).then(() => {
                alert.success('Section deleted successfully!');
                fetchSections(pagination.value?.current_page || 1);
            }).catch(() => {
                alert.error('Failed to delete section. Please try again.');
            });
        }
    });
};

const handleSaved = () => {
    fetchSections(pagination.value?.current_page || 1);
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <div class="flex gap-2">
                <select v-model="classFilter" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 w-40">
                    <option value="">All Classes</option>
                    <option v-for="schoolClass in schoolClasses" :key="schoolClass.id" :value="schoolClass.id">
                        {{ schoolClass.name }}
                    </option>
                </select>
                <select v-model="statusFilter" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 w-32">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex gap-2">
                <SectionForm :school-classes="schoolClasses" @saved="handleSaved" />
            </div>
        </div>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                #
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Section Name
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Class
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Code
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Description
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr
                            v-for="(section, index) in sectionsData"
                            :key="section.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ getRowNumber(index as number) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ section.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ section.school_class?.name || EMPTY_VALUE }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ section.code || EMPTY_VALUE }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate text-sm text-gray-600 dark:text-gray-300">
                                    {{ section.description || EMPTY_VALUE }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        section.is_active
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    ]"
                                >
                                    {{ section.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex space-x-2">
                                    <SectionForm
                                        :section="section"
                                        :school-classes="schoolClasses"
                                        trigger="Edit"
                                        variant="outline"
                                        size="sm"
                                        @saved="handleSaved"
                                    >
                                        <Icon icon="edit" class="mr-1" />Edit
                                    </SectionForm>
                                    <Button
                                        v-if="section.is_active"
                                        variant="outline"
                                        size="sm"
                                        @click="inactivateSection(section)"
                                    >
                                        <Icon icon="pause" class="mr-1" />Inactive
                                    </Button>
                                    <Button
                                        v-else
                                        variant="default"
                                        size="sm"
                                        @click="activateSection(section)"
                                    >
                                        <Icon icon="check" class="mr-1" />Activate
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="deleteSection(section)"
                                    >
                                        <Icon icon="trash" class="mr-1" />Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="text-sm text-gray-600">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                </div>
                <select v-model="perPage" class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1 text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="flex gap-1">
                <Button
                    v-for="link in pagination.links"
                    :key="`${link.label}-${link.url || 'disabled'}`"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="link.url ? fetchSections(getPageFromUrl(link.url) || 1) : null"
                >
                    <span v-html="link.label"></span>
                </Button>
            </div>
        </div>
    </div>
</template>
