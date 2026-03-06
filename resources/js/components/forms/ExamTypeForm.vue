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
    examType?: {
        id: number;
        name: string;
        short_name: string;
        is_active: boolean;
    };
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Exam Type',
    variant: 'default',
    size: 'default',
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Form data
const form = ref({
    name: props.examType?.name || '',
    short_name: props.examType?.short_name || '',
    is_active: props.examType?.is_active ?? true,
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
        short_name: form.value.short_name,
        is_active: form.value.is_active ? 1 : 0,
    };

    if (props.examType) {
        // Update
        router.patch(`/settings/exam-types/${props.examType.id}`, formData, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Exam type updated successfully!');
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
                    alert.error('Failed to update exam type. Please check the errors.');
                }
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } else {
        // Create
        router.post('/settings/exam-types', formData, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Exam type created successfully!');
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
                    alert.error('Failed to create exam type. Please check the errors.');
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
        short_name: '',
        is_active: true,
    };
    errors.value = {};
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon v-if="props.examType" icon="edit" class="mr-1" />
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
                    <DialogTitle>{{ examType ? 'Edit Exam Type' : 'Add Exam Type' }}</DialogTitle>
                    <DialogDescription>
                        {{ examType ? 'Update the exam type details.' : 'Create a new exam type for the school.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="name">Name <span class="text-red-500">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="e.g., Mid Term, Final Term"
                            :class="{ 'border-red-500': errors.name }"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="short_name">Short Name</Label>
                        <Input
                            id="short_name"
                            v-model="form.short_name"
                            placeholder="e.g., MT, FT"
                            :class="{ 'border-red-500': errors.short_name }"
                        />
                        <InputError :message="errors.short_name" />
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
                        {{ processing ? 'Saving...' : (examType ? 'Update' : 'Create') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
