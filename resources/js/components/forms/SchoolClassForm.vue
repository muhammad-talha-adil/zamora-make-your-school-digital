<script setup lang="ts">
import axios from 'axios';
import { computed, ref, watch } from 'vue';
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
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
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
        campus_id?: number;
    };
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    /** Owner/developer only - everyone else is auto-scoped to their own campus server-side. */
    isSuperAdmin?: boolean;
    campuses?: Array<{ id: number; name: string }>;
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Class',
    variant: 'default',
    size: 'default',
    isSuperAdmin: false,
    campuses: () => [],
});

const campusSelectOptions = computed(() => props.campuses.map((c) => ({ value: c.id, label: c.name })));

// Emits
const emit = defineEmits<{
    saved: [];
}>();

const getInitialForm = () => ({
    name: props.schoolClass?.name || '',
    code: props.schoolClass?.code || '',
    description: props.schoolClass?.description || '',
    // Editing moves one existing class to a different single campus; a new
    // class is created once per campus picked here, defaulting to every
    // campus selected (the common case).
    campus_id: props.schoolClass?.campus_id ?? null,
    campus_ids: props.campuses.map((c) => c.id),
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

    const formData: Record<string, unknown> = {
        name: form.value.name,
        description: form.value.description,
    };

    if (props.isSuperAdmin) {
        if (props.schoolClass) {
            formData.campus_id = form.value.campus_id;
        } else {
            formData.campus_ids = form.value.campus_ids;
        }
    }

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

                    <!-- Editing moves one existing class row, so only a single campus applies. -->
                    <div v-if="isSuperAdmin && props.schoolClass" class="grid gap-2">
                        <Label for="campus_id">Campus <span class="text-destructive">*</span></Label>
                        <SearchableSelect
                            id="campus_id"
                            v-model="form.campus_id"
                            :options="campusSelectOptions"
                            placeholder="Select campus"
                            :class="errors.campus_id ? 'border-destructive' : ''"
                        />
                        <InputError :message="errors.campus_id" />
                    </div>

                    <!-- Creating makes one class per campus chosen - any one, several, or every campus. -->
                    <div v-else-if="isSuperAdmin" class="grid gap-2">
                        <Label>Campuses <span class="text-destructive">*</span></Label>
                        <div class="flex items-center gap-2 border-b border-border pb-2">
                            <input
                                id="class_campus_ids_all"
                                type="checkbox"
                                :checked="form.campus_ids.length === campuses.length && campuses.length > 0"
                                class="rounded border-border text-primary shadow-sm focus:ring-primary"
                                @change="form.campus_ids = ($event.target as HTMLInputElement).checked ? campuses.map((c) => c.id) : []"
                            />
                            <Label for="class_campus_ids_all" class="font-normal">All Campuses</Label>
                        </div>
                        <div class="grid max-h-40 grid-cols-2 gap-2 overflow-y-auto">
                            <div v-for="campus in campuses" :key="campus.id" class="flex items-center gap-2">
                                <input
                                    :id="`class_campus_ids_${campus.id}`"
                                    v-model="form.campus_ids"
                                    type="checkbox"
                                    :value="campus.id"
                                    class="rounded border-border text-primary shadow-sm focus:ring-primary"
                                />
                                <Label :for="`class_campus_ids_${campus.id}`" class="font-normal">{{ campus.name }}</Label>
                            </div>
                        </div>
                        <InputError :message="errors.campus_ids" />
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
