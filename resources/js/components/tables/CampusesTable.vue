<script setup lang="ts">
import CampusForm from '@/components/forms/CampusForm.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';
import axios from 'axios';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';

// Props
interface Props {
    campuses: any; // Paginated
    campusTypes: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// const showInactive = ref(false); // REMOVED
const statusFilter = ref('');
const perPage = ref(10);
const campusesData = ref(props.campuses?.data || []);
const pagination = ref(props.campuses || { data: [], links: [], from: 0, to: 0, total: 0 });
const campusTypesLoaded = ref(props.campusTypes || []);

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

// Sync campusTypesLoaded with props when props change
watch(() => props.campusTypes, (newTypes) => {
    if (newTypes && newTypes.length > 0) {
        campusTypesLoaded.value = [...newTypes];
    }
}, { immediate: true, deep: true });

const fetchCampuses = (page = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    axios.get(`${route('campuses.all')}?${params}`).then((response) => {
        campusesData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch([statusFilter, perPage], () => {
    fetchCampuses();
});

// Watch for props changes (e.g., after form submissions)
watch(() => props.campuses, (newCampuses) => {
    campusesData.value = newCampuses.data || [];
    pagination.value = newCampuses;
}, { deep: true });

// Handle saved event from CampusForm (when campus is added/edited)
const handleSaved = () => {
    // Refresh campuses
    fetchCampuses();
    // Trigger parent to refresh all data including campusTypes
    emit('saved');
};

const deleteCampus = (campus: any) => {
    alert
        .confirm(
            `Are you sure you want to delete "${campus.name}"?`, 'Delete Campus',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.delete(route('campuses.destroy', campus.id), {
                    headers: { 'Accept': 'application/json' }
                }).then(() => {
                    alert.success('Campus deleted successfully!');
                    fetchCampuses();
                }).catch(() => {
                    alert.error('Failed to delete campus. Please try again.');
                });
            }
        });
};

const activateCampus = (campus: any) => {
    axios.patch(route('campuses.activate', campus.id), {}, {
        headers: { 'Accept': 'application/json' }
    }).then(() => {
        alert.success('Campus activated successfully!');
        const idx = campusesData.value.findIndex((c: any) => c.id === campus.id);
        if (idx !== -1) {
            campusesData.value.splice(idx, 1, { ...campusesData.value[idx], is_active: true });
        }
    }).catch(() => {
        alert.error('Failed to activate campus. Please try again.');
    });
};

const inactivateCampus = (campus: any) => {
    alert
        .confirm(
            `Are you sure you want to deactivate "${campus.name}"?`,
            'Deactivate Campus',
            'Yes, deactivate it!',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.patch(route('campuses.inactivate', campus.id), {}, {
                    headers: { 'Accept': 'application/json' }
                }).then(() => {
                    alert.success('Campus deactivated successfully!');
                    const idx = campusesData.value.findIndex((c: any) => c.id === campus.id);
                    if (idx !== -1) {
                        campusesData.value.splice(idx, 1, { ...campusesData.value[idx], is_active: false });
                    }
                }).catch(() => {
                    alert.error('Failed to deactivate campus. Please try again.');
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
                <CampusForm
                    :campus-types="campusTypesLoaded"
                    @saved="handleSaved"
                />
            </div>
        </div>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                #
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Campus Name
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Type
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Address
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Status
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr
                            v-for="(campus, index) in campusesData"
                            :key="campus.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ (index as number) + 1 }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ campus.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        campus.campus_type?.name === 'Main'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    ]"
                                >
                                    {{ campus.campus_type?.name || '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate text-sm text-gray-600 dark:text-gray-300">
                                    {{ campus.address || '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        campus.is_active
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    ]"
                                >
                                    {{ campus.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex space-x-2">
                                    <CampusForm
                                        :campus="campus"
                                        :campus-types="campusTypesLoaded"
                                        trigger="Edit"
                                        variant="outline"
                                        size="sm"
                                        @saved="handleSaved"
                                    >
                                        <Icon icon="edit" class="mr-1" />Edit
                                    </CampusForm>
                                    <Button
                                        v-if="campus.is_active"
                                        variant="outline"
                                        size="sm"
                                        @click="inactivateCampus(campus)"
                                    >
                                        <Icon icon="pause" class="mr-1" />Inactive
                                    </Button>
                                    <Button
                                        v-else
                                        variant="default"
                                        size="sm"
                                        @click="activateCampus(campus)"
                                    >
                                        <Icon icon="check" class="mr-1" />Active
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="deleteCampus(campus)"
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
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="link.url ? fetchCampuses(parseInt(link.url.match(/page=(\d+)/)?.[1] || '1')) : null"
                >
                    <span v-html="link.label"></span>
                </Button>
            </div>
        </div>
    </div>
</template>