<script setup lang="ts">
import axios from 'axios';
import { ref, watch } from 'vue';
import { alert } from '@/utils';
import { useFormValidity } from '@/composables/useFormValidity';

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
        description?: string;
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
    description: props.subject?.description || '',
    is_active: props.subject?.is_active ?? true,
    campus_ids: props.subject
        ? (props.subject.campuses ?? []).map((c) => c.id)
        : props.campuses.map((c) => c.id),
});

const form = ref(getInitialForm());

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const { isValid } = useFormValidity(form, ['name']);

// Dialog
const open = ref(false);

watch(open, (isOpen) => {
    if (isOpen) {
        form.value = getInitialForm();
        errors.value = {};
    }
});

const submit = () => {
    processing.value = true;
    errors.value = {};

    const formData: Record<string, unknown> = {
        name: form.value.name,
        description: form.value.description,
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
                alert.success('Subject updated successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((error) => {
                errors.value = error.response?.data?.errors ?? {};
                if (Object.keys(errors.value).length > 0) {
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Failed to update subject. Please check the errors.');
                }
            }).finally(() => {
                processing.value = false;
            });
    } else {
        // Create
        axios.post('/settings/subjects', formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Subject created successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((error) => {
                errors.value = error.response?.data?.errors ?? {};
                if (Object.keys(errors.value).length > 0) {
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Failed to create subject. Please check the errors.');
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

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            v-model="form.description"
                            placeholder="Enter subject description (optional)"
                            :class="{ 'border-destructive': errors.description }"
                        />
                        <InputError :message="errors.description" />
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
                    <Button type="submit" :disabled="processing || !isValid">
                        {{ processing ? 'Saving...' : (subject ? 'Update' : 'Create') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
