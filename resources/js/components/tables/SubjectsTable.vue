<script setup lang="ts">
import SubjectForm from '@/components/forms/SubjectForm.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { alert } from '@/utils';
import axios from 'axios';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface Props {
    subjects: any;
}

const props = defineProps<Props>();
const EMPTY_VALUE = '-';

const statusFilter = ref('');
const perPage = ref(10);
const subjectsData = ref(props.subjects?.data || []);
const pagination = ref(props.subjects || { data: [], links: [], from: 0, to: 0, total: 0 });

const statusOptions = [
    { id: '', name: 'All' },
    { id: 'active', name: 'Active' },
    { id: 'inactive', name: 'Inactive' },
];

const perPageOptions = [
    { id: 10, name: '10' },
    { id: 25, name: '25' },
    { id: 50, name: '50' },
    { id: 100, name: '100' },
];

const fetchSubjects = (page = pagination.value?.current_page || 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    axios.get(`${route('subjects.all')}?${params}`).then((response) => {
        subjectsData.value = response.data.data;
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

watch([statusFilter, perPage], () => {
    fetchSubjects(1);
});

watch(
    () => props.subjects,
    (newSubjects) => {
        subjectsData.value = newSubjects.data || [];
        pagination.value = newSubjects;
    },
    { deep: true },
);

const handleSaved = () => {
    fetchSubjects(pagination.value?.current_page || 1);
};

const deleteSubject = (subject: any) => {
    alert.confirm(
        `Are you sure you want to delete "${subject.name}"?`,
        'Delete Subject',
    ).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('subjects.destroy', subject.id), {
                headers: { Accept: 'application/json' },
            }).then(() => {
                alert.success('Subject deleted successfully!');
                fetchSubjects(pagination.value?.current_page || 1);
            }).catch(() => {
                alert.error('Failed to delete subject. Please try again.');
            });
        }
    });
};

const activateSubject = (subject: any) => {
    axios.patch(route('subjects.activate', subject.id), {}, {
        headers: { Accept: 'application/json' },
    }).then(() => {
        alert.success('Subject activated successfully!');
        fetchSubjects(pagination.value?.current_page || 1);
    }).catch(() => {
        alert.error('Failed to activate subject. Please try again.');
    });
};

const inactivateSubject = (subject: any) => {
    alert.confirm(
        `Are you sure you want to deactivate "${subject.name}"?`,
        'Deactivate Subject',
        'Yes, deactivate it!',
    ).then((result) => {
        if (result.isConfirmed) {
            axios.patch(route('subjects.inactivate', subject.id), {}, {
                headers: { Accept: 'application/json' },
            }).then(() => {
                alert.success('Subject deactivated successfully!');
                fetchSubjects(pagination.value?.current_page || 1);
            }).catch(() => {
                alert.error('Failed to deactivate subject. Please try again.');
            });
        }
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <div class="flex gap-2">
                <select v-model="statusFilter" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 w-32">
                    <option v-for="option in statusOptions" :key="option.id" :value="option.id">
                        {{ option.name }}
                    </option>
                </select>
            </div>
            <div class="flex gap-2">
                <SubjectForm @saved="handleSaved" />
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
                                Subject Name
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
                            v-for="(subject, index) in subjectsData"
                            :key="subject.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ getRowNumber(index as number) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ subject.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ subject.code || subject.short_name || EMPTY_VALUE }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate text-sm text-gray-600 dark:text-gray-300">
                                    {{ subject.description || EMPTY_VALUE }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        subject.is_active
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    ]"
                                >
                                    {{ subject.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex space-x-2">
                                    <SubjectForm
                                        :subject="subject"
                                        trigger="Edit"
                                        variant="outline"
                                        size="sm"
                                        @saved="handleSaved"
                                    >
                                        <Icon icon="edit" class="mr-1" />Edit
                                    </SubjectForm>
                                    <Button
                                        v-if="subject.is_active"
                                        variant="outline"
                                        size="sm"
                                        @click="inactivateSubject(subject)"
                                    >
                                        <Icon icon="pause" class="mr-1" />Inactive
                                    </Button>
                                    <Button
                                        v-else
                                        variant="default"
                                        size="sm"
                                        @click="activateSubject(subject)"
                                    >
                                        <Icon icon="check" class="mr-1" />Active
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="deleteSubject(subject)"
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
                <select v-model="perPage" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 w-20">
                    <option v-for="option in perPageOptions" :key="option.id" :value="option.id">
                        {{ option.name }}
                    </option>
                </select>
            </div>
            <div class="flex gap-1">
                <Button
                    v-for="link in pagination.links"
                    :key="`${link.label}-${link.url || 'disabled'}`"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="link.url ? fetchSubjects(getPageFromUrl(link.url) || 1) : null"
                >
                    <span v-html="link.label"></span>
                </Button>
            </div>
        </div>
    </div>
</template>
