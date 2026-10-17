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
    DialogDescription,
    DialogContent,
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

const getInitialForm = () => ({
    name: props.schoolClass?.name || '',
    code: props.schoolClass?.code || '',
    description: props.schoolClass?.description || '',
});

const form = ref(getInitialForm());

/**
 * A convenience only, not a real field: typing "Class" here once and "9" in
 * Name auto-combines them into "Class 9" so the office doesn't retype the
 * same prefix for every class. Remembered per-browser so it carries over
 * to the next "Add Class" without retyping it either.
 */
const prefixPresets = ['Class', 'Grade', 'Level'];
const namePrefix = ref(localStorage.getItem('schoolClassForm.prefix') ?? prefixPresets[0]);
// "Other" is active whenever the remembered/current prefix isn't one of the presets.
const isOtherPrefix = ref(!prefixPresets.includes(namePrefix.value));

watch(namePrefix, (value) => {
    try {
        localStorage.setItem('schoolClassForm.prefix', value);
    } catch {
        // Ignore — private browsing / storage disabled. Not critical.
    }
});

const selectPrefixPreset = (preset: string) => {
    isOtherPrefix.value = false;
    namePrefix.value = preset;
};

const selectOtherPrefix = () => {
    isOtherPrefix.value = true;
    if (prefixPresets.includes(namePrefix.value)) {
        namePrefix.value = '';
    }
};

const applyPrefix = () => {
    if (!namePrefix.value.trim() || !form.value.name.trim()) {
        return;
    }

    const alreadyPrefixed = form.value.name.trim().toLowerCase().startsWith(namePrefix.value.trim().toLowerCase());
    if (!alreadyPrefixed) {
        form.value.name = `${namePrefix.value.trim()} ${form.value.name.trim()}`;
    }
};

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const { isValid } = useFormValidity(form, ['name']);

// Dialog
const open = ref(false);

// Watch for prop changes to populate form
watch(() => props.schoolClass, () => {
    form.value = getInitialForm();
    errors.value = {};
}, { immediate: true });

// Watch for open state to reset form when modal opens
watch(open, (isOpen) => {
    if (isOpen) {
        form.value = getInitialForm();
        errors.value = {};
    }
});

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};

    const formData = {
        name: form.value.name,
        description: form.value.description,
    };

    if (props.schoolClass) {
        axios.patch(`/settings/school-classes/${props.schoolClass.id}`, formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Class updated successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((err) => {
                const errorResponse = err.response?.data;
                if (errorResponse?.errors) {
                    errors.value = Object.fromEntries(
                        Object.entries(errorResponse.errors).map(([key, value]) => [
                            key,
                            Array.isArray(value) ? value[0] : (value as string),
                        ])
                    );
                    const firstError = Object.values(errors.value)[0];
                    if (firstError) {
                        alert.error(firstError);
                    }
                } else {
                    alert.error(err instanceof Error ? err.message : 'Failed to update class. Please try again.');
                }
            }).finally(() => {
                processing.value = false;
            });
    } else {
        axios.post('/settings/school-classes', formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Class created successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((err) => {
                const errorResponse = err.response?.data;
                if (errorResponse?.errors) {
                    errors.value = Object.fromEntries(
                        Object.entries(errorResponse.errors).map(([key, value]) => [
                            key,
                            Array.isArray(value) ? value[0] : (value as string),
                        ])
                    );
                    const firstError = Object.values(errors.value)[0];
                    if (firstError) {
                        alert.error(firstError);
                    }
                } else {
                    alert.error(err instanceof Error ? err.message : 'Failed to create class. Please try again.');
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
            <RowAction v-if="props.schoolClass" kind="edit" />
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
                    <DialogTitle>{{ schoolClass ? 'Edit Class' : 'Add Class' }}</DialogTitle>
                    <DialogDescription>
                        {{ schoolClass ? 'Update the class details.' : 'Create a new class for the school.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div v-if="!props.schoolClass" class="grid gap-2">
                        <Label for="name-prefix">Prefix <span class="text-xs font-normal text-muted-foreground">(optional)</span></Label>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="preset in prefixPresets"
                                :key="preset"
                                type="button"
                                size="sm"
                                :variant="!isOtherPrefix && namePrefix === preset ? 'default' : 'outline'"
                                @click="selectPrefixPreset(preset)"
                            >
                                {{ preset }}
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                :variant="isOtherPrefix ? 'default' : 'outline'"
                                @click="selectOtherPrefix"
                            >
                                Other
                            </Button>
                        </div>
                        <Input
                            v-if="isOtherPrefix"
                            id="name-prefix"
                            v-model="namePrefix"
                            placeholder='e.g., "Section", "Standard"'
                        />
                        <p class="text-xs text-muted-foreground">Attached to whatever you type in Name below — set it once, it's remembered next time.</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder='e.g., "10", "11" (or the full name, like "Class 10")'
                            :class="{ 'border-destructive': errors.name }"
                            @blur="applyPrefix"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            v-model="form.description"
                            placeholder="Enter class description (optional)"
                            :class="{ 'border-destructive': errors.description }"
                        />
                        <InputError :message="errors.description" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="processing || !isValid">
                        {{ processing ? 'Saving...' : (schoolClass ? 'Update' : 'Create') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
