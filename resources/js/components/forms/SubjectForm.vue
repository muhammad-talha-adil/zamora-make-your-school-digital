<script setup lang="ts">
import type { Ref } from 'vue';
import axios from 'axios';
import { ref, watch } from 'vue';
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
    subject?: {
        id: number;
        name: string;
        code: string;
        short_name?: string;
        is_active: boolean;
        campuses?: Array<{ id: number; name: string }>;
    };
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    /** Owner/developer only - everyone else is auto-scoped to their own campus server-side. */
    isSuperAdmin?: boolean;
    campuses?: Array<{ id: number; name: string }>;
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Subject',
    variant: 'default',
    size: 'default',
    isSuperAdmin: false,
    campuses: () => [],
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Form data
const getInitialForm = () => ({
    name: props.subject?.name || '',
    code: props.subject?.code || '',
    short_name: props.subject?.short_name || '',
    is_active: props.subject?.is_active ?? true,
    campus_ids: props.subject
        ? (props.subject.campuses ?? []).map((c) => c.id)
        : props.campuses.map((c) => c.id),
});

const form = ref(getInitialForm());

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const { isValid } = useFormValidity(form, ['name']);
useClearErrorsOnEdit(form, errors as Ref<Record<string, string>>);

// Dialog
const open = ref(false);

watch(open, (isOpen) => {
    if (isOpen) {
        form.value = getInitialForm();
        errors.value = {};
    }
});

const submit = () => {
    const missing = requiredErrors(form, ['name']);
    if (Object.keys(missing).length > 0) {
        errors.value = missing as typeof errors.value;
        return;
    }

    processing.value = true;
    errors.value = {};

    const formData: Record<string, unknown> = {
        name: form.value.name,
        is_active: form.value.is_active ? 1 : 0,
    };

    if (props.isSuperAdmin) {
        formData.campus_ids = form.value.campus_ids;
    }

    if (props.subject) {
        // Update
        axios.patch(`/settings/subjects/${props.subject.id}`, formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Subject saved.');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((error) => {
                errors.value = error.response?.data?.errors ?? {};
                if (Object.keys(errors.value).length > 0) {
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Could not save subject. Check the form.');
                }
            }).finally(() => {
                processing.value = false;
            });
    } else {
        // Create
        axios.post('/settings/subjects', formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Subject added.');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((error) => {
                errors.value = error.response?.data?.errors ?? {};
                if (Object.keys(errors.value).length > 0) {
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Could not save subject. Check the form.');
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
            <RowAction v-if="props.subject" kind="edit" />
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
                    <DialogTitle>{{ subject ? 'Edit Subject' : 'Add Subject' }}</DialogTitle>
                    <DialogDescription>
                        {{ subject ? 'Update the subject details.' : 'Create a new subject for the school.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="name">Name <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Enter subject name"
                            :class="{ 'border-destructive': errors.name }"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div v-if="isSuperAdmin" class="grid gap-2">
                        <Label>Campuses <span class="text-destructive">*</span></Label>
                        <div class="flex items-center gap-2 border-b border-border pb-2">
                            <input
                                id="subject_campus_ids_all"
                                type="checkbox"
                                :checked="form.campus_ids.length === campuses.length && campuses.length > 0"
                                class="rounded border-border text-primary shadow-sm focus:ring-primary"
                                @change="form.campus_ids = ($event.target as HTMLInputElement).checked ? campuses.map((c) => c.id) : []"
                            />
                            <Label for="subject_campus_ids_all" class="font-normal">All Campuses</Label>
                        </div>
                        <div class="grid max-h-40 grid-cols-2 gap-2 overflow-y-auto">
                            <div v-for="campus in campuses" :key="campus.id" class="flex items-center gap-2">
                                <input
                                    :id="`subject_campus_ids_${campus.id}`"
                                    v-model="form.campus_ids"
                                    type="checkbox"
                                    :value="campus.id"
                                    class="rounded border-border text-primary shadow-sm focus:ring-primary"
                                />
                                <Label :for="`subject_campus_ids_${campus.id}`" class="font-normal">{{ campus.name }}</Label>
                            </div>
                        </div>
                        <InputError :message="errors.campus_ids" />
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
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : (subject ? 'Update' : 'Create') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
