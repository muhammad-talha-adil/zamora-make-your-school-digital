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
const getInitialForm = () => ({
    name: props.session?.name || '',
    start_year: props.session?.start_year || new Date().getFullYear(),
    end_year: props.session?.end_year || new Date().getFullYear() + 1,
    is_active: props.session?.is_active ?? false,
    start_date: props.session?.start_date || '',
    end_date: props.session?.end_date || '',
});

const form = ref(getInitialForm());

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const { isValid } = useFormValidity(form, ['name', 'start_year', 'end_year', 'start_date', 'end_date']);

// Dialog
const open = ref(false);

watch(open, (isOpen) => {
    if (isOpen) {
        form.value = getInitialForm();
        // The start/end-year watcher below only re-fires when those values
        // actually change, so a reset to the same default years (e.g.
        // reopening this dialog twice in one visit) would otherwise leave
        // `name` blank and the Create button permanently disabled.
        if (form.value.start_year && form.value.end_year) {
            form.value.name = `${form.value.start_year}-${form.value.end_year}`;
        }
        errors.value = {};
    }
});

// Selectable years for the start/end year dropdowns (issue #25): 2000 up to
// next year, so a session starting or ending next year can still be planned.
const currentYear = new Date().getFullYear();
const startYearOptions = Array.from(
    { length: currentYear - 2000 + 1 },
    (_, i) => 2000 + i,
);
const endYearOptions = Array.from(
    { length: currentYear + 1 - 2000 + 1 },
    (_, i) => 2000 + i,
);

/**
 * The Session Name is derived from Start Year + End Year (issue #59) so the
 * office never types the same years twice, once as a name and once as the
 * selected years.
 */
watch(
    [() => form.value.start_year, () => form.value.end_year],
    ([startYear, endYear]) => {
        if (startYear && endYear) {
            form.value.name = `${startYear}-${endYear}`;
        }
    },
    { immediate: true },
);

/**
 * Keeps the start/end date pickers inside the year they belong to (issue
 * #25): the start date calendar cannot leave the start year, and the end
 * date calendar cannot leave the end year. Existing out-of-range values are
 * cleared rather than silently kept.
 */
watch(
    () => form.value.start_year,
    (year) => {
        if (!year) return;
        if (form.value.start_date && !form.value.start_date.startsWith(String(year))) {
            form.value.start_date = '';
        }
    },
);

watch(
    () => form.value.end_year,
    (year) => {
        if (!year) return;
        if (form.value.end_date && !form.value.end_date.startsWith(String(year))) {
            form.value.end_date = '';
        }
    },
);

const startDateMin = computed(() => form.value.start_year ? `${form.value.start_year}-01-01` : undefined);
const startDateMax = computed(() => form.value.start_year ? `${form.value.start_year}-12-31` : undefined);
const endDateMin = computed(() => form.value.end_year ? `${form.value.end_year}-01-01` : undefined);
const endDateMax = computed(() => form.value.end_year ? `${form.value.end_year}-12-31` : undefined);

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
        axios.patch(`/settings/sessions/${props.session.id}`, formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Academic session updated successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((error) => {
                errors.value = error.response?.data?.errors ?? {};
                if (Object.keys(errors.value).length > 0) {
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Failed to update session. Please check the errors.');
                }
            }).finally(() => {
                processing.value = false;
            });
    } else {
        // Create
        axios.post('/settings/sessions', formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Academic session created successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((error) => {
                errors.value = error.response?.data?.errors ?? {};
                if (Object.keys(errors.value).length > 0) {
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Failed to create session. Please check the errors.');
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
            <RowAction v-if="props.session" kind="edit" />
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
                    <DialogTitle>{{ session ? 'Edit Session' : 'Add Session' }}</DialogTitle>
                    <DialogDescription>
                        {{ session ? 'Update the academic session details.' : 'Create a new academic session.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_year">Start Year <span class="text-destructive">*</span></Label>
                            <select
                                id="start_year"
                                v-model.number="form.start_year"
                                class="h-10 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                                :class="{ 'border-destructive': errors.start_year }"
                            >
                                <option v-for="year in startYearOptions" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                            <InputError :message="errors.start_year" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_year">End Year <span class="text-destructive">*</span></Label>
                            <select
                                id="end_year"
                                v-model.number="form.end_year"
                                class="h-10 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                                :class="{ 'border-destructive': errors.end_year }"
                            >
                                <option v-for="year in endYearOptions" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                            <InputError :message="errors.end_year" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            :model-value="form.name"
                            readonly
                            placeholder="Auto-generated from Start Year and End Year"
                            :class="{ 'border-destructive': errors.name }"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_date">Start Date <span class="text-destructive">*</span></Label>
                            <Input
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                :min="startDateMin"
                                :max="startDateMax"
                                :class="{ 'border-destructive': errors.start_date }"
                            />
                            <InputError :message="errors.start_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_date">End Date <span class="text-destructive">*</span></Label>
                            <Input
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                :min="endDateMin"
                                :max="endDateMax"
                                :class="{ 'border-destructive': errors.end_date }"
                            />
                            <InputError :message="errors.end_date" />
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-border text-primary shadow-sm focus:ring-primary"
                        />
                        <Label for="is_active">Set as Active Session</Label>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="processing || !isValid">
                        {{ processing ? 'Saving...' : (session ? 'Update' : 'Create') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
