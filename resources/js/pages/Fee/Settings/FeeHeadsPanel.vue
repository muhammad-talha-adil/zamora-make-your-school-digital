<script setup lang="ts">
import { reactive, ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import TablePagination from '@/components/tables/TablePagination.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';
import StatusToggle from '@/components/tables/StatusToggle.vue';

interface FeeHead {
    id: number;
    name: string;
    code: string;
    description?: string;
    category: string;
    default_frequency: string;
    is_recurring?: boolean;
    is_active: boolean;
    is_optional: boolean;
    sort_order: number;
}

interface Props {
    feeHeads: {
        data: FeeHead[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters?: {
        search?: string;
        category?: string;
        is_active?: string;
    };
    categories: Array<{ value: string; label: string }>;
    frequencies: Array<{ value: string; label: string }>;
    nextOrder: number;
}

const props = defineProps<Props>();

const showCreateModal = ref(false);
const editingFeeHead = ref<FeeHead | null>(null);
const isSubmitting = ref(false);
const formErrors = ref<Record<string, string>>({});

const normalizeErrors = (errors: Record<string, string | string[]>) => {
    return Object.fromEntries(
        Object.entries(errors).map(([key, value]) => [
            key,
            Array.isArray(value) ? String(value[0] ?? 'Validation failed.') : String(value),
        ]),
    );
};

const form = ref({
    name: '',
    description: '',
    category: props.categories[0]?.value || 'tuition',
    default_frequency: props.frequencies[0]?.value || 'monthly',
    is_recurring: true,
    is_active: true,
    is_optional: false,
    sort_order: props.nextOrder,
});

const resetForm = () => {
    form.value = {
        name: '',
        description: '',
        category: props.categories[0]?.value || 'tuition',
        default_frequency: props.frequencies[0]?.value || 'monthly',
        is_recurring: true,
        is_active: true,
        is_optional: false,
        sort_order: props.nextOrder,
    };
    formErrors.value = {};
};

const openCreateModal = () => {
    resetForm();
    editingFeeHead.value = null;
    showCreateModal.value = true;
};

const openEditModal = (feeHead: FeeHead) => {
    form.value = {
        name: feeHead.name,
        description: feeHead.description || '',
        category: feeHead.category,
        default_frequency: feeHead.default_frequency,
        is_recurring: feeHead.is_recurring ?? true,
        is_active: feeHead.is_active,
        is_optional: feeHead.is_optional,
        sort_order: feeHead.sort_order || 1,
    };
    formErrors.value = {};
    editingFeeHead.value = feeHead;
    showCreateModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    editingFeeHead.value = null;
    resetForm();
};

const validateForm = (): boolean => {
    formErrors.value = {};
    let isValid = true;

    if (!form.value.name.trim()) {
        formErrors.value.name = 'Name is required';
        isValid = false;
    }

    if (!form.value.category) {
        formErrors.value.category = 'Category is required';
        isValid = false;
    }

    if (!form.value.default_frequency) {
        formErrors.value.default_frequency = 'Frequency is required';
        isValid = false;
    }

    return isValid;
};

const submitForm = () => {
    if (!validateForm()) {
        return;
    }

    isSubmitting.value = true;

    const data = {
        ...form.value,
        sort_order: form.value.sort_order ? Number(form.value.sort_order) : 0,
    };

    const request = editingFeeHead.value
        ? axios.put(route('fee.heads.update', editingFeeHead.value.id), data, {
              headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          })
        : axios.post(route('fee.heads.store'), data, {
              headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          });

    request
        .then(() => {
            alert.success(editingFeeHead.value ? 'Fee head updated successfully!' : 'Fee head created successfully!');
            closeModal();
            fetchFeeHeads(pagination.value.current_page || 1);
        })
        .catch((error) => {
            formErrors.value = normalizeErrors(error.response?.data?.errors || {});
        })
        .finally(() => {
            isSubmitting.value = false;
        });
};

const feeHeadsData = ref<FeeHead[]>(props.feeHeads.data);
const pagination = ref(props.feeHeads || { data: [], links: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1, per_page: 10 });
const perPage = ref(props.feeHeads.per_page || 10);

const sortedFeeHeads = computed(() => {
    return [...feeHeadsData.value].sort((a, b) => {
        return (a.sort_order || 0) - (b.sort_order || 0);
    });
});

const filters = reactive({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    is_active: props.filters?.is_active !== undefined && props.filters?.is_active !== null
        ? String(props.filters.is_active)
        : '',
});

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const onSearchInput = () => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
    searchDebounceTimer = window.setTimeout(() => {
        fetchFeeHeads(1);
    }, 300);
};

const perPageOptions = [
    { id: 10, name: '10' },
    { id: 25, name: '25' },
    { id: 50, name: '50' },
    { id: 100, name: '100' },
];

const fetchFeeHeads = (page = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });
    if (filters.search) params.append('search', filters.search);
    if (filters.category) params.append('category', filters.category);
    if (filters.is_active !== '') params.append('is_active', filters.is_active);

    axios.get(route('fee.heads.all') + `?${params}`).then((response) => {
        feeHeadsData.value = response.data?.data || [];
        pagination.value = response.data || { data: [], links: [], from: 0, to: 0, total: 0 };
    }).catch((error) => {
        console.error('Failed to fetch fee heads:', error);
        alert.error('Failed to load fee heads. Please try again.');
    });
};

watch([perPage], () => {
    fetchFeeHeads(1);
});

const toggleActive = (feeHead: FeeHead) => {
    const actionText = feeHead.is_active ? 'deactivate' : 'activate';
    const confirmButtonText = feeHead.is_active ? 'Yes, deactivate it!' : 'Yes, activate it!';

    alert
        .confirm(
            `Are you sure you want to ${actionText} "${feeHead.name}"?`,
            actionText.charAt(0).toUpperCase() + actionText.slice(1) + ' Fee Head',
            confirmButtonText,
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.post(route('fee.heads.toggle-active', feeHead.id), {})
                    .then(() => {
                        feeHead.is_active = !feeHead.is_active;
                        alert.success(`Fee head ${actionText}d successfully!`);
                    })
                    .catch(() => {
                        alert.error('Failed to update status. Please try again.');
                    });
            }
        });
};

const deleteFeeHead = (feeHead: FeeHead) => {
    alert
        .confirm(
            `Are you sure you want to delete "${feeHead.name}"?`,
            'Delete Fee Head',
            'Yes, delete it!',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.delete(route('fee.heads.destroy', feeHead.id))
                    .then(() => {
                        alert.success('Fee head deleted successfully!');
                        fetchFeeHeads();
                    })
                    .catch(() => {
                        alert.error('Failed to delete fee head. Please try again.');
                    });
            }
        });
};

const getCategoryColor = (category: string) => {
    const colors: Record<string, string> = {
        tuition: 'bg-primary/10 text-primary',
        transport: 'bg-warning/10 text-warning',
        hostel: 'bg-primary/10 text-primary',
        library: 'bg-success/10 text-success',
        examination: 'bg-destructive/10 text-destructive',
        other: 'bg-muted text-foreground',
    };
    return colors[category] || colors.other;
};

const getCategoryLabel = (category: string) => {
    const labels: Record<string, string> = {
        tuition: 'Tuition',
        transport: 'Transport',
        hostel: 'Hostel',
        library: 'Library',
        examination: 'Examination',
        other: 'Other',
    };
    return labels[category] || category;
};

const getFrequencyLabel = (frequency: string) => {
    const labels: Record<string, string> = {
        monthly: 'Monthly',
        yearly: 'Yearly',
        once: 'One Time',
    };
    return labels[frequency] || frequency;
};
</script>

<template>
    <div class="space-y-4 md:space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
            <div>
                <h2 class="text-lg font-semibold text-foreground">Fee Heads</h2>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Manage fee categories and heads
                </p>
            </div>
            <Button @click="openCreateModal">
                <Icon icon="plus" class="mr-2 h-4 w-4" />
                Create Fee Head
            </Button>
        </div>

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap">
            <div class="w-full sm:w-44 md:w-48">
                <Label for="fee-head-search" class="sr-only">Search</Label>
                <Input
                    id="fee-head-search"
                    v-model="filters.search"
                    @input="onSearchInput"
                    placeholder="Search fee heads..."
                    class="min-h-10 md:min-h-11"
                />
            </div>
            <div class="w-full sm:w-44 md:w-48">
                <Label for="fee-head-filter-category" class="sr-only">Category</Label>
                <select
                    id="fee-head-filter-category"
                    v-model="filters.category"
                    @change="fetchFeeHeads(1)"
                    class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
                >
                    <option value="">All Categories</option>
                    <option v-for="cat in props.categories" :key="cat.value" :value="cat.value">
                        {{ cat.label }}
                    </option>
                </select>
            </div>
            <div class="w-full sm:w-44 md:w-48">
                <Label for="fee-head-filter-active" class="sr-only">Status</Label>
                <select
                    id="fee-head-filter-active"
                    v-model="filters.is_active"
                    @change="fetchFeeHeads(1)"
                    class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
                >
                    <option value="">All Status</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="block lg:hidden space-y-3">
            <div
                v-for="(feeHead, index) in sortedFeeHeads"
                :key="feeHead.id"
                class="bg-card rounded-lg border border-border p-4 space-y-2"
            >
                <div class="flex flex-wrap gap-2 justify-between items-start">
                    <div>
                        <div class="text-xs text-muted-foreground">Sr# {{ ((pagination.from || 1) - 1) + index + 1 }}</div>
                        <div class="font-medium text-foreground">{{ feeHead.name }}</div>
                        <div class="text-xs text-muted-foreground">Code: {{ feeHead.code }}</div>
                    </div>
                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getCategoryColor(feeHead.category)]">
                        {{ getCategoryLabel(feeHead.category) }}
                    </span>
                </div>
                <div class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
                    <div>Frequency: {{ getFrequencyLabel(feeHead.default_frequency) }}</div>
                    <div>Order: {{ feeHead.sort_order }}</div>
                    <div>
                        <StatusToggle :active="feeHead.is_active" @toggle="toggleActive(feeHead)" />
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <Button variant="outline" size="sm" @click="openEditModal(feeHead)">
                        <Icon icon="edit" class="mr-1" />Edit
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteFeeHead(feeHead)" class="flex-1">
                        <Icon icon="trash" class="mr-1" />Delete
                    </Button>
                </div>
            </div>
            <div v-if="feeHeadsData.length === 0" class="text-center py-8 text-muted-foreground">
                No fee heads found.
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Sr#</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Order</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Code</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Name</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Category</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Frequency</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr v-for="(feeHead, index) in sortedFeeHeads" :key="feeHead.id" class="transition-colors hover:bg-accent">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-foreground">{{ ((pagination.from || 1) - 1) + index + 1 }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-foreground">{{ feeHead.sort_order }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-foreground">{{ feeHead.code }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-foreground">{{ feeHead.name }}</div>
                                <div v-if="feeHead.description" class="text-xs text-muted-foreground">{{ feeHead.description }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getCategoryColor(feeHead.category)]">
                                    {{ getCategoryLabel(feeHead.category) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-muted-foreground">{{ getFrequencyLabel(feeHead.default_frequency) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <StatusToggle :active="feeHead.is_active" @toggle="toggleActive(feeHead)" />
                            </td>
                            <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                <div class="flex flex-wrap gap-2 justify-end">
                                    <Button variant="outline" size="sm" @click="openEditModal(feeHead)">
                                        <Icon icon="edit" class="mr-1 h-3 w-3" />Edit
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="deleteFeeHead(feeHead)">
                                        <Icon icon="trash" class="mr-1 h-3 w-3" />Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pt-4">
            <TablePagination
                v-model:per-page="perPage"
                :pagination="pagination"
                :per-page-options="perPageOptions"
                @page="fetchFeeHeads"
            />
        </div>

        <!-- Create/Edit Modal -->
        <Dialog v-model:open="showCreateModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingFeeHead ? 'Edit Fee Head' : 'Create Fee Head' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Name & Code -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="fh-name">Name *</Label>
                            <Input
                                id="fh-name"
                                v-model="form.name"
                                placeholder="e.g., Tuition Fee"
                                :class="formErrors.name ? 'border-destructive' : ''"
                            />
                            <p v-if="formErrors.name" class="text-xs text-destructive">{{ formErrors.name }}</p>
                        </div>
                        <div v-if="editingFeeHead" class="space-y-2">
                            <Label for="fh-code">Code</Label>
                            <Input id="fh-code" :model-value="editingFeeHead.code" disabled class="bg-muted" />
                            <p class="text-xs text-muted-foreground">System-generated reference, cannot be changed.</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="fh-description">Description</Label>
                        <textarea
                            id="fh-description"
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            placeholder="Description of this fee head..."
                        ></textarea>
                    </div>

                    <!-- Category & Frequency -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="fh-category">Category *</Label>
                            <select
                                id="fh-category"
                                v-model="form.category"
                                :class="['w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm', formErrors.category ? 'border-destructive' : '']"
                            >
                                <option v-for="cat in props.categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                            <p v-if="formErrors.category" class="text-xs text-destructive">{{ formErrors.category }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="fh-frequency">Frequency *</Label>
                            <select
                                id="fh-frequency"
                                v-model="form.default_frequency"
                                :class="['w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm', formErrors.default_frequency ? 'border-destructive' : '']"
                            >
                                <option v-for="freq in props.frequencies" :key="freq.value" :value="freq.value">
                                    {{ freq.label }}
                                </option>
                            </select>
                            <p v-if="formErrors.default_frequency" class="text-xs text-destructive">{{ formErrors.default_frequency }}</p>
                        </div>
                    </div>

                    <!-- Order, Status & Optional -->
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="fh-order">Display Order</Label>
                            <Input
                                id="fh-order"
                                v-model="form.sort_order"
                                type="number"
                                min="1"
                                :placeholder="String(props.nextOrder)"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="fh-active">Status</Label>
                            <select
                                id="fh-active"
                                v-model="form.is_active"
                                class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            >
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="fh-optional">Is Optional</Label>
                            <select
                                id="fh-optional"
                                v-model="form.is_optional"
                                class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            >
                                <option :value="false">Required</option>
                                <option :value="true">Optional</option>
                            </select>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
                        <Button type="submit" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Saving...' : (editingFeeHead ? 'Save Changes' : 'Create') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
