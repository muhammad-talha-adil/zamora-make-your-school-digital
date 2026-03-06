<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import CampusTypeForm from '@/components/forms/CampusTypeForm.vue';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

// Props
interface Props {
    campusTypes: Array<{
        id: number;
        name: string;
    }>;
    trigger?: string;
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Campus Type',
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Dialog
const open = ref(false);

// Form state
const editingCampusType = ref<{
    id: number;
    name: string;
} | undefined>(undefined);

const showTrashed = ref(false);
const campusTypesData = ref(props.campusTypes);

// Methods
const refreshCampusTypes = () => {
    // Reload the page to refresh campus types list
    window.location.reload();
};

const toggleTrashed = () => {
    showTrashed.value = !showTrashed.value;
    fetchCampusTypes();
};

const fetchCampusTypes = () => {
    const trashed = showTrashed.value ? '1' : '0';
    axios.get(`/settings/campus-types?trashed=${trashed}`).then((response) => {
        campusTypesData.value = response.data;
    });
};

const editCampusType = (campusType: { id: number; name: string }) => {
    editingCampusType.value = campusType;
};

const addCampusType = () => {
    editingCampusType.value = undefined;
};

const deleteCampusType = (campusType: { id: number; name: string }) => {
    alert.confirm(`Are you sure you want to delete "${campusType.name}"? This action cannot be undone.`, 'Delete Campus Type').then((result) => {
        if (result.isConfirmed) {
            router.delete(`/settings/campus-types/${campusType.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    alert.success('Campus type deleted successfully!');
                    fetchCampusTypes();
                },
                onError: () => {
                    alert.error('Failed to delete campus type. Please try again.');
                },
            });
        }
    });
};

const restoreCampusType = (campusType: { id: number; name: string }) => {
    router.patch(`/settings/campus-types/${campusType.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('Campus type restored successfully!');
            fetchCampusTypes();
        },
        onError: () => {
            alert.error('Failed to restore campus type. Please try again.');
        },
    });
};

const forceDeleteCampusType = (campusType: { id: number; name: string }) => {
    alert.confirm(`Are you sure you want to permanently delete "${campusType.name}"? This action cannot be undone.`, 'Permanently Delete Campus Type').then((result) => {
        if (result.isConfirmed) {
            router.delete(`/settings/campus-types/${campusType.id}/force`, {
                preserveScroll: true,
                onSuccess: () => {
                    alert.success('Campus type permanently deleted successfully!');
                    fetchCampusTypes();
                },
                onError: () => {
                    alert.error('Failed to permanently delete campus type. Please try again.');
                },
            });
        }
    });
};

const onFormSaved = () => {
    editingCampusType.value = undefined;
    fetchCampusTypes();
    emit('saved');
};

const onFormCancel = () => {
    editingCampusType.value = undefined;
    open.value = false;
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button>{{ trigger }}</Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>Manage Campus Types</DialogTitle>
                <DialogDescription>
                    View, add, edit, and delete campus types.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6">
                <!-- Add/Edit Form -->
                <div v-if="editingCampusType || true" class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h4 class="text-sm font-semibold mb-2">{{ editingCampusType ? 'Edit Campus Type' : 'Add New Campus Type' }}</h4>
                    <CampusTypeForm
                        :campus-type="editingCampusType"
                        @saved="onFormSaved"
                        @cancel="onFormCancel"
                    />
                </div>

                <!-- Table -->
                <div class="space-y-4">
                    <div class="flex justify-end">
                        <Button
                            :variant="showTrashed ? 'ghost' : 'default'"
                            size="sm"
                            @click="toggleTrashed"
                        >
                            <Icon :icon="showTrashed ? 'arrow-left' : 'eye'" class="mr-1" />
                            {{ showTrashed ? 'Back' : 'Deleted Campus Types' }}
                        </Button>
                    </div>
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">#</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(campusType, index) in campusTypesData" :key="campusType.id" class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-600 dark:text-gray-300">{{ index + 1 }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ campusType.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2" v-if="!showTrashed">
                                                <Button variant="outline" size="sm" @click="editCampusType(campusType)">
                                                    <Icon icon="edit" class="mr-1" />
                                                    Edit
                                                </Button>
                                                <Button variant="destructive" size="sm" @click="deleteCampusType(campusType)">
                                                    <Icon icon="trash" class="mr-1" />
                                                    Delete
                                                </Button>
                                            </div>
                                            <div class="flex space-x-2" v-else>
                                                <Button variant="default" size="sm" @click="restoreCampusType(campusType)">
                                                    <Icon icon="refresh" class="mr-1" />
                                                    Restore
                                                </Button>
                                                <Button variant="destructive" size="sm" @click="forceDeleteCampusType(campusType)">
                                                    <Icon icon="x" class="mr-1" />
                                                    Force Delete
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
