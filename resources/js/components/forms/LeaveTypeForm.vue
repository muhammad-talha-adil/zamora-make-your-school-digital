<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { alert } from '@/utils';

// Components
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardFooter,
} from '@/components/ui/card';
import Icon from '@/components/Icon.vue';

// Props
interface Props {
    leaveType?: {
        id: number;
        name: string;
        description?: string;
        is_active: boolean;
    };
    trigger?: string;
    variant?: string;
    size?: string;
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Leave Type',
    variant: 'default',
    size: 'default',
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

const isOpen = ref(false);
const errors = ref({});
const processing = ref(false);

// Form data
const form = ref({
    name: props.leaveType?.name || '',
    description: props.leaveType?.description || '',
    is_active: props.leaveType?.is_active ?? true,
});

const isEditing = computed(() => !!props.leaveType?.id);

const openModal = () => {
    if (props.leaveType) {
        form.value = {
            name: props.leaveType.name,
            description: props.leaveType.description || '',
            is_active: props.leaveType.is_active,
        };
    } else {
        form.value = {
            name: '',
            description: '',
            is_active: true,
        };
    }
    errors.value = {};
    isOpen.value = true;
};

const closeModal = () => {
    isOpen.value = false;
};

const submit = () => {
    processing.value = true;
    errors.value = {};

    const url = isEditing.value
        ? `/attendance/settings/leave-types/${props.leaveType?.id}`
        : '/attendance/settings/leave-types';

    const method = isEditing.value ? 'put' : 'post';

    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success(
                isEditing.value
                    ? 'Leave type updated successfully!'
                    : 'Leave type created successfully!',
            );
            emit('saved');
            closeModal();
        },
        onError: (err) => {
            errors.value = err;
            alert.error(
                `Failed to ${isEditing.value ? 'update' : 'create'} leave type. Please check the errors.`,
            );
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <!-- Trigger Button -->
        <Button
            :variant="(variant || 'default') as any"
            :size="(size || 'default') as any"
            @click="openModal"
        >
            <Icon v-if="isEditing" icon="plus" class="mr-1" />
            <slot>{{ trigger }}</slot>
        </Button>

        <!-- Modal -->
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="closeModal"
        >
            <Card class="w-full max-w-md mx-4">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Icon icon="calendar" class="mr-2 h-5 w-5" />
                        {{ isEditing ? 'Edit' : 'Add' }} Leave Type
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <Label for="name" class="flex items-center">
                                <Icon icon="tag" class="mr-1 h-4 w-4" />
                                Name *
                            </Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                :class="{ 'border-red-500': (errors as any).name }"
                                placeholder="e.g., Sick Leave"
                            />
                            <InputError :message="(errors as any).name" />
                        </div>

                        <div>
                            <Label for="description" class="flex items-center">
                                <Icon icon="file-text" class="mr-1 h-4 w-4" />
                                Description
                            </Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                :class="{ 'border-red-500': (errors as any).description }"
                                placeholder="Optional description"
                            ></textarea>
                            <InputError :message="(errors as any).description" />
                        </div>

                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_active"
                                v-model:checked="form.is_active"
                            />
                            <Label for="is_active" class="flex items-center">
                                <Icon icon="check-circle" class="mr-1 h-4 w-4" />
                                Active
                            </Label>
                        </div>
                    </form>
                </CardContent>
                <CardFooter class="flex justify-end space-x-2">
                    <Button variant="outline" @click="closeModal">
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="processing"
                        @click="submit"
                    >
                        <Icon
                            v-if="processing"
                            icon="loader"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        {{ isEditing ? 'Update' : 'Create' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
