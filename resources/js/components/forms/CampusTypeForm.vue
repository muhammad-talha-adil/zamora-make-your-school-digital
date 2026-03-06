<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { alert } from '@/utils';

// Components
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// Props
interface Props {
    campusType?: {
        id: number;
        name: string;
    };
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
    saved: [];
    cancel: [];
}>();

// Form data
const form = ref({
    name: props.campusType?.name || '',
});

const errors = ref({});
const processing = ref(false);

// Watch for prop changes to update form
watch(() => props.campusType, (newCampusType) => {
    form.value.name = newCampusType?.name || '';
    errors.value = {};
}, { immediate: true });

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};

    if (props.campusType) {
        // Update
        router.put(`/settings/campus-types/${props.campusType.id}`, form.value, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Campus type updated successfully!');
                resetForm();
                emit('saved');
            },
            onError: (err) => {
                errors.value = err;
                alert.error('Failed to update campus type. Please check the errors.');
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } else {
        // Create
        router.post('/settings/campus-types', form.value, {
            preserveScroll: true,
            onSuccess: () => {
                alert.success('Campus type created successfully!');
                resetForm();
                emit('saved');
            },
            onError: (err) => {
                errors.value = err;
                alert.error('Failed to create campus type. Please check the errors.');
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
    };
    errors.value = {};
};

const cancel = () => {
    resetForm();
    emit('cancel');
};
</script>

<template>
    <form
        @submit.prevent="submit"
        class="space-y-4"
    >
        <div class="grid gap-4 py-4">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    placeholder="Enter campus type name"
                />
                <InputError :message="(errors as any).name" />
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-2">
            <Button
                type="button"
                variant="secondary"
                @click="cancel"
            >
                Cancel
            </Button>
            <Button type="submit" :disabled="processing">
                {{ campusType ? 'Update' : 'Create' }}
            </Button>
        </div>
    </form>
</template>
