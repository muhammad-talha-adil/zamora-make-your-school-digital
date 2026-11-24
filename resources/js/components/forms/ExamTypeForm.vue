<script setup lang="ts">
import type { Ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { alert } from '@/utils';
import { requiredErrors, useClearErrorsOnEdit, useFormValidity } from '@/composables/useFormValidity';

// Components
import InputError from '@/components/InputError.vue';
import { Button, type ButtonVariants } from '@/components/ui/button';
import {
    Dialog,
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
import RowAction from '@/components/tables/RowAction.vue';

// Props
interface Props {
    examType?: {
        id: number;
        name: string;
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
    is_active: props.examType?.is_active ?? true,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const { isValid } = useFormValidity(form, ['name']);
useClearErrorsOnEdit(form, errors as Ref<Record<string, string>>);

// Dialog
const open = ref(false);

// Methods
const submit = () => {
    const missing = requiredErrors(form, ['name']);
    if (Object.keys(missing).length > 0) {
        errors.value = missing as typeof errors.value;
        return;
    }

    processing.value = true;
    errors.value = {};

    const formData = {
        name: form.value.name,
        is_active: form.value.is_active ? 1 : 0,
    };

    if (props.examType) {
        // Update
        router.patch(`/settings/exam-types/${props.examType.id}`, formData, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Exam type saved.');
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
                    alert.error('Could not save exam type. Check the form.');
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
                alert.success('Exam type added.');
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
                    alert.error('Could not save exam type. Check the form.');
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
        is_active: true,
    };
    errors.value = {};
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <RowAction v-if="props.examType" kind="edit" />
            <Button v-else :variant="props.variant" :size="props.size">
                <Icon icon="plus" class="mr-1" />
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
                        <Label for="name">Name <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="e.g., Mid Term, Final Term"
                            :class="{ 'border-destructive': errors.name }"
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="flex items-center space-x-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-border text-primary shadow-sm focus:ring-primary"
                        />
                        <Label for="is_active">Active</Label>
                    </div>
                    <p class="-mt-2 text-xs text-muted-foreground">Only one exam type can be active. Making this one active turns the other off.</p>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : (examType ? 'Update' : 'Create') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
