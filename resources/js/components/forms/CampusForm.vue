<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { alert } from '@/utils';

// Components
import InputError from '@/components/InputError.vue';
import { Button, type ButtonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue';
import Icon from '@/components/Icon.vue';
import CampusTypeModal from './CampusTypeModal.vue';

// Props
interface Props {
    campus?: {
        id: number;
        name: string;
        address?: string;
        campus_type_id: number;
        campus_type?: {
            id: number;
            name: string;
        };
    };
    campusTypes?: Array<{
        id: number;
        name: string;
    }>;
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Campus',
    campusTypes: () => [],
    variant: 'default',
    size: 'default',
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Form data
const form = ref({
    name: '',
    address: '',
    campus_type_id: null as number | null,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Modal states
const campusDialogOpen = ref(false);
const showCampusTypeModal = ref(false);

// Watch for campus prop changes
watch(() => props.campus, (newCampus) => {
    if (newCampus) {
        form.value.name = newCampus.name;
        form.value.address = newCampus.address || '';
        form.value.campus_type_id = newCampus.campus_type_id;
    }
}, { immediate: true });

// Watch for campusTypes prop changes
watch(() => props.campusTypes, (newTypes) => {
    if (newTypes.length > 0 && !props.campus) {
        form.value.campus_type_id = newTypes[0]?.id || null;
    }
}, { immediate: true });

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};

    if (props.campus) {
        // Update
        router.put(`/settings/campuses/${props.campus.id}`, form.value, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Campus updated successfully!');
                closeModal();
                emit('saved');
                // Refresh page data after modal closes
                router.reload({ only: ['campuses', 'campusTypes'] });
            },
            onError: (err) => {
                // Inertia validation errors are in err.errors
                const errorResponse = err as any;
                if (errorResponse?.errors) {
                    errors.value = Object.fromEntries(
                        Object.entries(errorResponse.errors).map(([key, value]) => [
                            key,
                            Array.isArray(value) ? value[0] : (value as string),
                        ])
                    );
                    const firstError = Object.values(errors.value)[0];
                    if (firstError) {
                        alert.error(firstError);
                    }
                } else {
                    alert.error(err instanceof Error ? err.message : 'Failed to update campus. Please try again.');
                }
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } else {
        // Create
        router.post('/settings/campuses', form.value, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Campus created successfully!');
                closeModal();
                emit('saved');
                // Refresh page data after modal closes
                router.reload({ only: ['campuses', 'campusTypes'] });
            },
            onError: (err) => {
                // Inertia validation errors are in err.errors
                const errorResponse = err as any;
                if (errorResponse?.errors) {
                    // Flatten array errors to first message per field
                    errors.value = Object.fromEntries(
                        Object.entries(errorResponse.errors).map(([key, value]) => [
                            key,
                            Array.isArray(value) ? value[0] : (value as string),
                        ])
                    );
                    // Show first error as alert for visibility
                    const firstError = Object.values(errors.value)[0];
                    if (firstError) {
                        alert.error(firstError);
                    }
                } else {
                    // Non-validation error (500, network, etc)
                    alert.error(err instanceof Error ? err.message : 'Failed to create campus. Please try again.');
                }
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    }
};

const closeModal = () => {
    campusDialogOpen.value = false;
    resetForm();
};

const resetForm = () => {
    form.value = {
        name: '',
        address: '',
        campus_type_id: props.campusTypes[0]?.id || null,
    };
    errors.value = {};
};

const openCampusTypeModal = () => {
    showCampusTypeModal.value = true;
};

const handleCampusTypeSaved = (campusType: { id: number; name: string } | undefined) => {
    if (!campusType) {
        showCampusTypeModal.value = false;
        return;
    }
    // Add the new campus type to the list if not exists
    const exists = props.campusTypes.find(t => t.id === campusType.id);
    if (!exists && campusType.id) {
        // The parent component should refresh the campus types
        emit('saved');
    }
    // Set the selected campus type
    form.value.campus_type_id = campusType.id;
    showCampusTypeModal.value = false;
};
</script>

<template>
    <Dialog v-model:open="campusDialogOpen">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon v-if="props.campus" icon="edit" class="mr-1" />
                <Icon v-else icon="map-pin" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <Icon :icon="campus ? 'edit' : 'map-pin'" class="h-5 w-5 text-primary" />
                    </div>
                    {{ campus ? 'Edit Campus' : 'Add New Campus' }}
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Form Card -->
                <div class="bg-card rounded-lg border p-5 space-y-4">
                    <!-- Campus Name -->
                    <div class="space-y-2">
                        <Label for="name" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="building" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Campus Name <span class="text-red-500">*</span>
                        </Label>
                        <div class="relative">
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Enter campus name"
                                required
                                class="pl-10 h-11"
                            />
                            <Icon icon="building" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        </div>
                        <InputError :message="errors.name" />
                    </div>

                         <!-- Campus Type -->
                         <div class="space-y-2">
                             <Label for="campusType" class="flex items-center gap-2">
                                 <div class="p-1 bg-muted rounded">
                                     <Icon icon="tag" class="h-3.5 w-3.5 text-muted-foreground" />
                                 </div>
                                 Campus Type <span class="text-red-500">*</span>
                             </Label>
                             <div class="flex gap-2">
                                 <div class="relative flex-1">
                                 <ComboboxInput
                                     id="campusType"
                                     v-model="form.campus_type_id"
                                     placeholder="Search campus types..."
                                     :search-url="'/settings/campus-types/all'"
                                     :initial-items="props.campusTypes.map(type => ({ id: type.id, name: type.name }))"
                                     value-type="id"
                                     class="h-11"
                                 />
                                 </div>
                                 <Button
                                     type="button"
                                     variant="outline"
                                     size="icon"
                                     @click="openCampusTypeModal"
                                     title="Manage Campus Types"
                                     class="h-11 w-11"
                                 >
                                     <Icon icon="settings" class="h-4 w-4" />
                                 </Button>
                             </div>
                             <InputError :message="errors.campus_type_id" />
                         </div>

                    <!-- Address -->
                    <div class="space-y-2">
                        <Label for="address" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="map-pin" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Address <span class="muted-foreground text-sm font-normal">(Optional)</span>
                        </Label>
                        <div class="relative">
                            <Input
                                id="address"
                                v-model="form.address"
                                placeholder="Enter campus address"
                                class="pl-10 h-11"
                            />
                            <Icon icon="map-pin" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        </div>
                        <InputError :message="errors.address" />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-2">
                    <DialogClose as-child>
                        <Button type="button" variant="outline" @click="resetForm" class="h-10">
                            <Icon icon="x" class="mr-2 h-4 w-4" />
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing" class="h-10">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else :icon="campus ? 'check' : 'plus'" class="mr-2 h-4 w-4" />
                        {{ campus ? 'Update Campus' : 'Create Campus' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Campus Type Modal -->
    <CampusTypeModal
        :open="showCampusTypeModal"
        @update:open="showCampusTypeModal = $event"
        @saved="handleCampusTypeSaved"
    />
</template>
