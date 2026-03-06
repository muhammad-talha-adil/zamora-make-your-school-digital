<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch, computed } from 'vue';
import { alert } from '@/utils';

// Components
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';

// Props
interface Props {
    open: boolean;
    campusType?: {
        id: number;
        name: string;
    };
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
});

// Emits
const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [campusType: { id: number; name: string }];
}>();

// Form data
const form = ref({
    name: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const campusTypes = ref<Array<{ id: number; name: string; campuses_count?: number }>>([]);
const allCampusTypes = ref<Array<{ id: number; name: string; campuses_count?: number }>>([]);
const loading = ref(false);
const editingId = ref<number | null>(null);

// Pagination
const currentPage = ref(1);
const perPage = ref(5);
const totalPages = computed(() => Math.ceil(allCampusTypes.value.length / perPage.value));

// Watch for prop changes to populate form
watch(() => props.campusType, (newType) => {
    if (newType) {
        form.value.name = newType.name;
        editingId.value = newType.id;
    }
}, { immediate: true });

// Watch for open state
watch(() => props.open, (isOpen) => {
    if (isOpen) {
        if (!props.campusType) {
            form.value.name = '';
            errors.value = {};
            editingId.value = null;
        }
        fetchCampusTypes();
    }
});

// Fetch all campus types
const fetchCampusTypes = () => {
    loading.value = true;
    axios.get('/settings/campus-types/all').then((response) => {
        allCampusTypes.value = response.data.campusTypes || [];
        updatePaginatedData();
    }).catch((error) => {
        console.error('Failed to fetch campus types:', error);
        alert.error('Failed to load campus types.');
    }).finally(() => {
        loading.value = false;
    });
};

// Update paginated data based on current page
const updatePaginatedData = () => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    campusTypes.value = allCampusTypes.value.slice(start, end);
};

// Go to specific page
const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        updatePaginatedData();
    }
};

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};

    if (editingId.value) {
        // Update existing campus type
        router.put(`/settings/campus-types/${editingId.value}`, form.value, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Campus type updated successfully!');
                emit('saved', { id: editingId.value!, name: form.value.name });
                form.value.name = '';
                errors.value = {};
                editingId.value = null;
                fetchCampusTypes();
            },
            onError: (err) => {
                errors.value = err as Record<string, string>;
                alert.error('Failed to update campus type. Please check the errors.');
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } else {
        // Create new campus type
        router.post('/settings/campus-types', form.value, {
            preserveScroll: true,
            onSuccess: (page: any) => {
                const newId = page.props?.campusType?.id;
                alert.success('Campus type created successfully!');
                emit('saved', { id: newId, name: form.value.name });
                form.value.name = '';
                errors.value = {};
                fetchCampusTypes();
            },
            onError: (err) => {
                errors.value = err as Record<string, string>;
                alert.error('Failed to create campus type. Please check the errors.');
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    }
};

const resetForm = () => {
    form.value.name = '';
    errors.value = {};
    editingId.value = null;
};

const closeModal = () => {
    resetForm();
    emit('update:open', false);
};

const editCampusType = (campusType: { id: number; name: string }) => {
    form.value.name = campusType.name;
    editingId.value = campusType.id;
};

const deleteCampusType = (campusType: { id: number; name: string }) => {
    if (confirm(`Are you sure you want to delete "${campusType.name}"?`)) {
        router.delete(`/settings/campus-types/${campusType.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Campus type deleted successfully!');
                // Adjust current page if needed
                if (allCampusTypes.value.length > 1 && currentPage.value > 1 && (allCampusTypes.value.length - 1) % perPage.value === 0) {
                    currentPage.value--;
                }
                fetchCampusTypes();
            },
            onError: () => {
                alert.error('Failed to delete campus type.');
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <Icon icon="building" class="h-5 w-5 text-primary" />
                    </div>
                    Manage Campus Types
                </DialogTitle>
            </DialogHeader>

            <div class="flex-1 overflow-y-auto space-y-5 pr-2">
                <!-- Add/Edit Form Card -->
                <div class="bg-card rounded-lg border p-4">
                    <h3 class="text-sm font-medium mb-3 flex items-center gap-2">
                        <div class="p-1.5 bg-muted rounded-md">
                            <Icon :icon="editingId ? 'edit' : 'plus'" class="h-4 w-4" />
                        </div>
                        {{ editingId ? 'Edit Campus Type' : 'Add New Campus Type' }}
                    </h3>
                    <form @submit.prevent="submit" class="space-y-3">
                        <div class="space-y-2">
                            <Label for="typeName">Campus Type Name</Label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <Input
                                        id="typeName"
                                        v-model="form.name"
                                        placeholder="Enter campus type name (e.g., Main, Branch)"
                                        class="pl-10"
                                    />
                                    <Icon icon="tag" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                </div>
                                <Button 
                                    v-if="editingId" 
                                    type="button" 
                                    variant="outline" 
                                    size="sm"
                                    @click="resetForm"
                                >
                                    <Icon icon="x" class="h-4 w-4 mr-1" />
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="processing">
                                    <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                    <Icon v-else :icon="editingId ? 'check' : 'plus'" class="mr-2 h-4 w-4" />
                                    {{ editingId ? 'Update' : 'Add' }}
                                </Button>
                            </div>
                            <InputError :message="errors.name" />
                        </div>
                    </form>
                </div>

                <!-- Campus Types Table with Pagination -->
                <div>
                    <h3 class="text-sm font-medium mb-3 flex items-center gap-2">
                        <div class="p-1.5 bg-muted rounded-md">
                            <Icon icon="list" class="h-4 w-4" />
                        </div>
                        Existing Campus Types ({{ allCampusTypes.length }})
                    </h3>
                    
                    <div v-if="loading" class="flex items-center justify-center py-8">
                        <Icon icon="loader" class="h-6 w-6 animate-spin text-muted-foreground" />
                    </div>

                    <div v-else-if="allCampusTypes.length === 0" class="text-center py-8 text-muted-foreground bg-muted/30 rounded-lg border border-dashed">
                        <Icon icon="inbox" class="h-10 w-10 mx-auto mb-3 opacity-50" />
                        <p class="text-sm font-medium">No campus types found</p>
                        <p class="text-xs mt-1">Add a new campus type using the form above</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div class="overflow-hidden rounded-lg border bg-card">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-border">
                                    <thead class="bg-muted/50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                                #
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                                Name
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                                Campuses
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border bg-card">
                                        <tr
                                            v-for="(type, index) in campusTypes"
                                            :key="type.id"
                                            class="transition-colors hover:bg-muted/50"
                                        >
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                                {{ (currentPage - 1) * perPage + index + 1 }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="text-sm font-medium text-foreground">{{ type.name }}</span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                                                    {{ type.campuses_count || 0 }} campus{{ (type.campuses_count || 0) !== 1 ? 'es' : '' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium whitespace-nowrap text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <Button 
                                                        variant="ghost" 
                                                        size="icon" 
                                                        @click="editCampusType(type)"
                                                        title="Edit"
                                                        class="h-8 w-8"
                                                    >
                                                        <Icon icon="edit" class="h-4 w-4" />
                                                    </Button>
                                                    <Button 
                                                        variant="ghost" 
                                                        size="icon" 
                                                        @click="deleteCampusType(type)"
                                                        title="Delete"
                                                        class="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    >
                                                        <Icon icon="trash" class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="flex items-center justify-between">
                            <div class="text-sm text-muted-foreground">
                                Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, allCampusTypes.length) }} of {{ allCampusTypes.length }} entries
                            </div>
                            <div class="flex items-center gap-1">
                                <Button
                                    variant="outline"
                                    size="icon"
                                    :disabled="currentPage === 1"
                                    @click="goToPage(currentPage - 1)"
                                    class="h-8 w-8"
                                >
                                    <Icon icon="chevron-left" class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-for="page in totalPages"
                                    :key="page"
                                    :variant="page === currentPage ? 'default' : 'outline'"
                                    size="sm"
                                    @click="goToPage(page)"
                                    class="h-8 w-8"
                                >
                                    {{ page }}
                                </Button>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    :disabled="currentPage === totalPages"
                                    @click="goToPage(currentPage + 1)"
                                    class="h-8 w-8"
                                >
                                    <Icon icon="chevron-right" class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end pt-4 border-t mt-auto">
                <Button type="button" variant="outline" @click="closeModal">
                    <Icon icon="check" class="mr-2 h-4 w-4" />
                    Done
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
