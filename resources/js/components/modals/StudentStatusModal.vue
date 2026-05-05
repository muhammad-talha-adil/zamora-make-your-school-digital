<template>
    <Dialog :open="isOpen" @open-change="handleOpenChange">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Update Student Status</DialogTitle>
                <DialogDescription>
                    Select a new status for student {{ student?.registration_no }} and provide a reason.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submitForm" class="space-y-4">
                <!-- Status Dropdown -->
                <div class="space-y-2">
                    <Label for="status_id">Status <span class="text-red-500">*</span></Label>
                    <select
                        id="status_id"
                        v-model="form.status_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                        :class="{ 'border-red-500': errors.status_id }"
                        required
                    >
                        <option value="">Select Status</option>
                        <option v-for="status in statuses" :key="status.id" :value="status.id">
                            {{ status.name }}
                        </option>
                    </select>
                    <InputError :message="errors.status_id" />
                </div>

                <!-- Status Description -->
                <div class="space-y-2">
                    <Label for="status_description">Description / Notes</Label>
                    <textarea
                        id="status_description"
                        v-model="form.status_description"
                        rows="3"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 min-h-20"
                        placeholder="Enter reason for status change..."
                    ></textarea>
                </div>

                <!-- Warning Message -->
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-md p-3">
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        <Icon icon="alert-triangle" class="inline h-4 w-4 mr-1" />
                        The student account will be deactivated upon submission.
                    </p>
                </div>

                <DialogFooter class="sm:justify-end gap-2">
                    <Button type="button" variant="outline" @click="closeModal">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        Confirm Status Change
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils/alert';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    isOpen: boolean;
    student: {
        id: number;
        registration_no: string;
        user_id?: number;
    } | null;
    statuses: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'closed'): void;
}>();

const processing = ref(false);
const errors = ref<Record<string, string>>({});

const form = ref({
    status_id: '',
    status_description: '',
});

const resetForm = () => {
    form.value.status_id = '';
    form.value.status_description = '';
    errors.value = {};
};

const closeModal = () => {
    resetForm();
    emit('update:open', false);
    emit('closed');
};

const handleOpenChange = (open: boolean) => {
    if (!open) {
        closeModal();
    }
};

watch(() => props.isOpen, (newVal) => {
    if (newVal && props.student) {
        resetForm();
    }
});

const submitForm = () => {
    if (!props.student) return;

    processing.value = true;
    errors.value = {};

    router.post(route('students.change-status', props.student.id), {
        status_id: parseInt(form.value.status_id),
        status_description: form.value.status_description,
    }, {
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            alert.success('Student status updated successfully!');
            closeModal();
        },
        onError: (err) => {
            errors.value = err as Record<string, string>;
            // Show prominent error alert
            const errorMessages = Object.values(err as Record<string, string>);
            if (errorMessages.length > 0) {
                const firstError = errorMessages[0];
                alert.error(firstError);
            } else {
                alert.error('Failed to update student status. Please try again.');
            }
        },
    });
};
</script>
