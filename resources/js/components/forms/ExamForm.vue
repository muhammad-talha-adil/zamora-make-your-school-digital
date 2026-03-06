<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
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
import type { Exam, ExamType, Session } from '@/types/exam';

// Props
interface Props {
    exam?: Exam;
    examTypes: ExamType[];
    sessions: Session[];
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Create Exam',
    variant: 'default',
    size: 'default',
    disabled: false,
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Helper function to format date for input (handles both ISO strings and other formats)
const formatDateForInput = (date: string | null | undefined): string => {
    if (!date) return '';
    // If already in Y-m-d format, return as is
    if (/^\d{4}-\d{2}-\d{2}$/.test(date)) return date;
    // Try to parse ISO date string
    const d = new Date(date);
    if (!isNaN(d.getTime())) {
        return d.toISOString().split('T')[0];
    }
    return date;
};

// Form data - ensure session_id is always a string for consistent comparison
const form = ref({
    name: props.exam?.name || '',
    exam_type_id: props.exam?.exam_type_id?.toString() || '',
    session_id: props.exam?.session_id?.toString() || '',
    start_date: formatDateForInput(props.exam?.start_date || ''),
    end_date: formatDateForInput(props.exam?.end_date || ''),
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Dialog
const open = ref(false);

// Reset form when dialog opens with exam data
watch(open, (isOpen) => {
    if (isOpen && props.exam) {
        form.value = {
            name: props.exam.name || '',
            exam_type_id: props.exam.exam_type_id?.toString() || '',
            session_id: props.exam.session_id?.toString() || '',
            start_date: formatDateForInput(props.exam.start_date || ''),
            end_date: formatDateForInput(props.exam.end_date || ''),
        };
    }
});

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};

    const formData = {
        name: form.value.name,
        exam_type_id: Number(form.value.exam_type_id) || null,
        session_id: Number(form.value.session_id) || null,
        start_date: form.value.start_date,
        end_date: form.value.end_date,
    };

    if (props.exam) {
        // Update
        router.put(route('exam.update', props.exam.id), formData, {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                setTimeout(() => {
                    alert.success('Exam updated successfully!');
                    resetForm();
                    emit('saved');
                }, 100);
            },
            onError: (err: Record<string, string>) => {
                errors.value = err;
                // Don't show alert popup - errors are displayed inline with InputError component
                // This avoids z-index issues with the Dialog component
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } else {
        // Create
        router.post(route('exam.store'), formData, {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                setTimeout(() => {
                    alert.success('Exam created successfully!');
                    resetForm();
                    emit('saved');
                }, 100);
            },
            onError: (err: Record<string, string>) => {
                errors.value = err;
                // Don't show alert popup - errors are displayed inline with InputError component
                // This avoids z-index issues with the Dialog component
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
        exam_type_id: '',
        session_id: '',
        start_date: '',
        end_date: '',
    };
    errors.value = {};
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size" :disabled="props.disabled">
                <Icon v-if="props.exam" icon="edit" class="mr-1" />
                <Icon v-else icon="plus" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[550px]">
            <form @submit.prevent="submit" class="space-y-4">
                <DialogHeader>
                    <DialogTitle>{{ exam ? 'Edit Exam' : 'Create Exam' }}</DialogTitle>
                    <DialogDescription>
                        {{ exam ? 'Update the exam details.' : 'Add a new exam configuration.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Exam Name <span class="text-red-500">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter exam name"
                                :class="{ 'border-red-500': errors.name }"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="exam_type_id">Exam Type <span class="text-red-500">*</span></Label>
                            <select
                                id="exam_type_id"
                                v-model="form.exam_type_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                                :class="{ 'border-red-500': errors.exam_type_id }"
                            >
                                <option value="">Select Exam Type</option>
                                <option v-for="type in props.examTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                            <InputError :message="errors.exam_type_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="session_id">Session <span class="text-red-500">*</span></Label>
                            <select
                                id="session_id"
                                v-model="form.session_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                                :class="{ 'border-red-500': errors.session_id }"
                            >
                                <option value="">Select Session</option>
                                <option v-for="session in props.sessions" :key="session.id" :value="String(session.id)">
                                    {{ session.name }}
                                </option>
                            </select>
                            <InputError :message="errors.session_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="start_date">Start Date <span class="text-red-500">*</span></Label>
                            <Input
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                :class="{ 'border-red-500': errors.start_date }"
                            />
                            <InputError :message="errors.start_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_date">End Date <span class="text-red-500">*</span></Label>
                            <Input
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                :class="{ 'border-red-500': errors.end_date }"
                            />
                            <InputError :message="errors.end_date" />
                        </div>
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
                        {{ processing ? 'Saving...' : (exam ? 'Update Exam' : 'Create Exam') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
