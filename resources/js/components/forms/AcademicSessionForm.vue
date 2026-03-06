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
    session?: {
        id: number;
        name: string;
        start_year: number;
        end_year: number;
        is_active: boolean;
        start_date?: string;
        end_date?: string;
    };
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Session',
    variant: 'default',
    size: 'default',
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Form data
const form = ref({
    name: props.session?.name || '',
    start_year: props.session?.start_year || new Date().getFullYear(),
    end_year: props.session?.end_year || new Date().getFullYear() + 1,
    is_active: props.session?.is_active ?? false,
    start_date: props.session?.start_date || '',
    end_date: props.session?.end_date || '',
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
        start_year: form.value.start_year,
        end_year: form.value.end_year,
        is_active: form.value.is_active ? 1 : 0,
        start_date: form.value.start_date || null,
        end_date: form.value.end_date || null,
    };

    if (props.session) {
        // Update
        router.put(`/settings/sessions/${props.session.id}`, formData, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Academic session updated successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            },
            onError: (err) => {
                errors.value = err as Record<string, string>;
                if (Object.keys(errors.value).length > 0) {
                    // Show first error in alert
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Failed to update session. Please check the errors.');
                }
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } else {
        // Create
        router.post('/settings/sessions', formData, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Academic session created successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            },
            onError: (err) => {
                errors.value = err as Record<string, string>;
                if (Object.keys(errors.value).length > 0) {
                    // Show first error in alert
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Failed to create session. Please check the errors.');
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
        start_year: new Date().getFullYear(),
        end_year: new Date().getFullYear() + 1,
        is_active: false,
        start_date: '',
        end_date: '',
    };
    errors.value = {};
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon v-if="props.session" icon="edit" class="mr-1" />
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
                    <DialogTitle>{{ session ? 'Edit Session' : 'Add Session' }}</DialogTitle>
                    <DialogDescription>
                        {{ session ? 'Update the academic session details.' : 'Create a new academic session.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="name">Name <span class="text-red-500">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="e.g., 2024-2025"
                            :class="{ 'border-red-500': errors.name }"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_year">Start Year <span class="text-red-500">*</span></Label>
                            <Input
                                id="start_year"
                                v-model.number="form.start_year"
                                type="number"
                                min="2000"
                                max="2100"
                                :class="{ 'border-red-500': errors.start_year }"
                            />
                            <InputError :message="errors.start_year" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_year">End Year <span class="text-red-500">*</span></Label>
                            <Input
                                id="end_year"
                                v-model.number="form.end_year"
                                type="number"
                                min="2000"
                                max="2100"
                                :class="{ 'border-red-500': errors.end_year }"
                            />
                            <InputError :message="errors.end_year" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_date">Start Date</Label>
                            <Input
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                :class="{ 'border-red-500': errors.start_date }"
                            />
                            <InputError :message="errors.start_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_date">End Date</Label>
                            <Input
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                :class="{ 'border-red-500': errors.end_date }"
                            />
                            <InputError :message="errors.end_date" />
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <Label for="is_active">Set as Active Session</Label>
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
                        {{ processing ? 'Saving...' : (session ? 'Update' : 'Create') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
