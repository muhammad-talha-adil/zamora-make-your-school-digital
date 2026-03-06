<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { alert } from '@/utils';

// Components
import InputError from '@/components/InputError.vue';
import { Button, type ButtonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
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

// Form data
const form = ref({
    name: props.schoolClass?.name || '',
    code: props.schoolClass?.code || '',
    description: props.schoolClass?.description || '',
    is_active: props.schoolClass?.is_active ?? true,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Dialog
const open = ref(false);

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};

    const formData = {
        name: form.value.name,
        code: form.value.code,
        description: form.value.description,
        is_active: form.value.is_active ? 1 : 0,
    };

    if (props.schoolClass) {
        // Update
        router.put(`/settings/school-classes/${props.schoolClass.id}`, formData, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Class updated successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            },
            onError: (err) => {
                errors.value = err as Record<string, string>;
                if (Object.keys(errors.value).length > 0) {
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Failed to update class. Please check the errors.');
                }
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } else {
        // Create
        router.post('/settings/school-classes', formData, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Class created successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            },
            onError: (err) => {
                errors.value = err as Record<string, string>;
                if (Object.keys(errors.value).length > 0) {
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Failed to create class. Please check the errors.');
                }
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    }
};

const resetForm = () => {
    form.value = {
        name: '',
        code: '',
        description: '',
        is_active: true,
    };
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
                            placeholder="Enter class name"
                            :class="{ 'border-red-500': errors.name }"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="code">Code</Label>
                        <Input
                            id="code"
                            v-model="form.code"
                            placeholder="Enter class code"
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

                    <div class="flex items-center space-x-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <Label for="is_active">Active</Label>
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
