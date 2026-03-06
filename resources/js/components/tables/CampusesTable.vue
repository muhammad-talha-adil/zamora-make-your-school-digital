<script setup lang="ts">
import CampusForm from '@/components/forms/CampusForm.vue';
import { Button } from '@/components/ui/button';
import { ComboboxInput } from '@/components/ui/combobox';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch } from 'vue';

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

const showInactive = ref(false);
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

const toggleInactive = () => {
    showInactive.value = !showInactive.value;
    fetchCampuses();
};

const fetchCampuses = (page = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    if (showInactive.value) {
        params.append('status', 'inactive');
    } else if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    axios.get(`/settings/campuses?${params}`).then((response) => {
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
            `Are you sure you want to deactivate "${campus.name}"?`,
            'Deactivate Campus',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.patch(`/settings/campuses/${campus.id}/deactivate`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Campus deactivated successfully!');
                        fetchCampuses();
                    },
                    onError: () => {
                        alert.error(
                            'Failed to deactivate campus. Please try again.',
                        );
                    },
                });
            }
        });
};

const restoreCampus = (campus: any) => {
    router.patch(`/settings/campuses/${campus.id}/activate`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('Campus activated successfully!');
            fetchCampuses();
        },
        onError: () => {
            alert.error('Failed to activate campus. Please try again.');
        },
    });
};

const forceDeleteCampus = (campus: any) => {
    alert
        .confirm(
            `Are you sure you want to delete "${campus.name}"? This action cannot be undone.`,
            'Delete Campus',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/settings/campuses/${campus.id}`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Campus deleted successfully!');
                        fetchCampuses();
                    },
                    onError: () => {
                        alert.error(
                            'Failed to delete campus. Please try again.',
                        );
                    },
                });
            }
        });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <div class="flex gap-2">
                <ComboboxInput
                    v-model="statusFilter"
                    placeholder="All"
                    :initial-items="statusOptions"
                    value-type="id"
                    class="w-32"
                    :disabled="showInactive"
                />
            </div>
            <div class="flex gap-2">
                <CampusForm
                    :campus-types="campusTypesLoaded"
                    @saved="handleSaved"
                />
                <Button
                    :variant="showInactive ? 'ghost' : 'default'"
                    size="sm"
                    @click="toggleInactive"
                >
                    <Icon :icon="showInactive ? 'arrow-left' : 'eye'" class="mr-1" />
                    {{ showInactive ? 'Back' : 'Inactive Campuses' }}
                </Button>
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
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex space-x-2" v-if="!showInactive">
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
                                        variant="destructive"
                                        size="sm"
                                        @click="deleteCampus(campus)"
                                    >
                                        <Icon icon="trash" class="mr-1" />Delete
                                    </Button>
                                </div>
                                <div class="flex space-x-2" v-else>
                                    <Button
                                        variant="default"
                                        size="sm"
                                        @click="restoreCampus(campus)"
                                    >
                                        <Icon icon="refresh" class="mr-1" />Activate
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="forceDeleteCampus(campus)"
                                    >
                                        <Icon icon="x" class="mr-1" />Delete
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
                <ComboboxInput
                    v-model="perPage"
                    placeholder="10"
                    :initial-items="perPageOptions"
                    value-type="id"
                    class="w-20"
                />
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
