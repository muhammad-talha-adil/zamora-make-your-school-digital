<script setup lang="ts">
import axios from 'axios';
import { ref, watch } from 'vue';
import { alert } from '@/utils';

// Components
import InputError from '@/components/InputError.vue';
import { Button, type ButtonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogDescription,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';

// Props
interface Props {
    schoolClass?: {
        id: number;
        name: string;
        code: string;
        description?: string;
        is_active: boolean;
    };
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Class',
    variant: 'default',
    size: 'default',
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

const getInitialForm = () => ({
    name: props.schoolClass?.name || '',
    code: props.schoolClass?.code || '',
    description: props.schoolClass?.description || '',
});

const form = ref(getInitialForm());

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Dialog
const open = ref(false);

// Watch for prop changes to populate form
watch(() => props.schoolClass, () => {
    form.value = getInitialForm();
    errors.value = {};
}, { immediate: true });

// Watch for open state to reset form when modal opens
watch(open, (isOpen) => {
    if (isOpen) {
        form.value = getInitialForm();
        errors.value = {};
    }
});

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};

    const formData = {
        name: form.value.name,
        code: form.value.code,
        description: form.value.description,
    };

    if (props.schoolClass) {
        axios.patch(`/settings/school-classes/${props.schoolClass.id}`, formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Class updated successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((err) => {
                const errorResponse = err.response?.data;
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
                    alert.error(err instanceof Error ? err.message : 'Failed to update class. Please try again.');
                }
            }).finally(() => {
                processing.value = false;
            });
    } else {
        axios.post('/settings/school-classes', formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Class created successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((err) => {
                const errorResponse = err.response?.data;
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
                    alert.error(err instanceof Error ? err.message : 'Failed to create class. Please try again.');
                }
            }).finally(() => {
                processing.value = false;
            });
    }
};

const resetForm = () => {
    form.value = getInitialForm();
    errors.value = {};
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon v-if="props.schoolClass" icon="edit" class="mr-1" />
                <Icon v-else icon="plus" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[425px]">
            <form
                @submit.prevent="submit"
                class="space-y-4"
            >
                <DialogHeader>
                    <DialogTitle>{{ schoolClass ? 'Edit Class' : 'Add Class' }}</DialogTitle>
                    <DialogDescription>
                        {{ schoolClass ? 'Update the class details.' : 'Create a new class for the school.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="name">Name <span class="text-red-500">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder='e.g., "Class 10", "Grade 11"'
                            :class="{ 'border-red-500': errors.name }"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="code">Code <span class="text-red-500">*</span></Label>
                        <Input
                            id="code"
                            v-model="form.code"
                            placeholder='e.g., "CLS-001", "GRD-10"'
                            :class="{ 'border-red-500': errors.code }"
                        />
                        <InputError :message="errors.code" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            v-model="form.description"
                            placeholder="Enter class description (optional)"
                            :class="{ 'border-red-500': errors.description }"
                        />
                        <InputError :message="errors.description" />
                    </div>
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="secondary"
                            @click="resetForm"
                        >
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : (schoolClass ? 'Update' : 'Create') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
