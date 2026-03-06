<script setup lang="ts">
import SectionForm from '@/components/forms/SectionForm.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch } from 'vue';

// Props
interface Props {
    sections: any; // Paginated response
    schoolClasses?: { id: number; name: string }[];
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
    saved: [];
}>();

const showInactive = ref(false);
const statusFilter = ref('');
const perPage = ref(10);
const sectionsData = ref(props.sections.data || []);
const pagination = ref(props.sections);

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

const toggleInactive = () => {
    showInactive.value = !showInactive.value;
    fetchSections();
};

const fetchSections = (page = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    if (showInactive.value) {
        params.append('status', 'inactive');
    } else if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    axios.get(`/settings/sections/all?${params}`).then((response) => {
        sectionsData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch([statusFilter, perPage], () => {
    fetchSections();
});

// Watch for props changes (e.g., after form submissions)
watch(() => props.sections, (newSections) => {
    sectionsData.value = newSections.data || [];
    pagination.value = newSections;
}, { deep: true });

const inactivateSection = (section: any) => {
    alert
        .confirm(
            `Are you sure you want to deactivate "${section.name}"?`,
            'Deactivate Section',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.patch(`/settings/sections/${section.id}/inactivate`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Section deactivated successfully!');
                        fetchSections();
                    },
                    onError: () => {
                        alert.error(
                            'Failed to deactivate section. Please try again.',
                        );
                    },
                });
            }
        });
};

const activateSection = (section: any) => {
    router.patch(`/settings/sections/${section.id}/activate`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('Section activated successfully!');
            fetchSections();
        },
        onError: () => {
            alert.error('Failed to activate section. Please try again.');
        },
    });
};

const forceDeleteSection = (section: any) => {
    alert
        .confirm(
            `Are you sure you want to permanently delete "${section.name}"? This action cannot be undone.`,
            'Delete Section',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/settings/sections/${section.id}/force`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Section permanently deleted successfully!');
                        fetchSections();
                    },
                    onError: () => {
                        alert.error(
                            'Failed to permanently delete section. Please try again.',
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
                <select v-model="statusFilter" class="w-32 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex gap-2">
                <SectionForm :school-classes="schoolClasses" @saved="fetchSections" />
                <Button
                    :variant="showInactive ? 'ghost' : 'default'"
                    size="sm"
                    @click="toggleInactive"
                >
                    <Icon :icon="showInactive ? 'arrow-left' : 'eye'" class="mr-1" />
                    {{ showInactive ? 'Back' : 'Inactive Sections' }}
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
                                Section Name
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Class
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Code
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Description
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
                            v-for="(section, index) in sectionsData"
                            :key="section.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ (index as number) + 1 }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ section.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ section.school_class?.name || '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ section.code || '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate text-sm text-gray-600 dark:text-gray-300">
                                    {{ section.description || '—' }}
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
                                <div class="flex space-x-2" v-if="!showInactive">
                                    <SectionForm
                                        :section="section"
                                        :school-classes="schoolClasses"
                                        trigger="Edit"
                                        variant="outline"
                                        size="sm"
                                        @saved="fetchSections"
                                    >
                                        <Icon icon="edit" class="mr-1" />Edit
                                    </SectionForm>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="inactivateSection(section)"
                                    >
                                        <Icon icon="trash" class="mr-1" />Delete
                                    </Button>
                                </div>
                                <div class="flex space-x-2" v-else>
                                    <Button
                                        variant="default"
                                        size="sm"
                                        @click="activateSection(section)"
                                    >
                                        <Icon icon="check" class="mr-1" />Activate
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="forceDeleteSection(section)"
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
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="link.url ? fetchSections(parseInt(link.url.match(/page=(\d+)/)?.[1] || '1')) : null"
                >
                    <span v-html="link.label"></span>
                </Button>
            </div>
        </div>
    </div>
</template>
