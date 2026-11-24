<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
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
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
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
    trigger: 'Add Session',
    variant: 'default',
    size: 'default',
    isSuperAdmin: false,
    campuses: () => [],
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Current academic year runs April to March: Jan-Mar still belongs to last year's session.
const now = new Date();
const academicYearStart = now.getMonth() < 3 ? now.getFullYear() - 1 : now.getFullYear();

// Form data
const defaultEndDate = (startYear: number, endYear: number) => (endYear > startYear ? `${endYear}-03-31` : `${endYear}-12-31`);

const latestSessionEnd = (usePage().props as { latestSessionEnd?: string | null }).latestSessionEnd ?? null;

/** A new session starts the day after the latest one ends and runs one year. */
const nextSessionDates = () => {
    if (props.session || !latestSessionEnd) {
        return null;
    }
    const start = new Date(`${String(latestSessionEnd).slice(0, 10)}T00:00:00Z`);
    start.setUTCDate(start.getUTCDate() + 1);
    const end = new Date(start);
    end.setUTCFullYear(end.getUTCFullYear() + 1);
    end.setUTCDate(end.getUTCDate() - 1);
    const iso = (d: Date) => d.toISOString().slice(0, 10);

    return { start: iso(start), end: iso(end), startYear: start.getUTCFullYear(), endYear: end.getUTCFullYear() };
};

const getInitialForm = () => ({
    name: props.session?.name || '',
    start_year: props.session?.start_year || nextSessionDates()?.startYear || academicYearStart,
    end_year: props.session?.end_year || nextSessionDates()?.endYear || academicYearStart + 1,
    is_active: props.session?.is_active ?? false,
    start_date: props.session?.start_date || nextSessionDates()?.start || '',
    end_date: props.session?.end_date || nextSessionDates()?.end || '',
    // Editing an existing session starts from the campuses it's already
    // attached to; a new one defaults to every campus selected (the common
    // case), since nothing has been chosen yet.
    campus_ids: props.session
        ? (props.session.campuses ?? []).map((c) => c.id)
        : props.campuses.map((c) => c.id),
});

const form = ref(getInitialForm());

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const { isValid } = useFormValidity(form, ['name', 'start_year', 'end_year', 'start_date', 'end_date']);
useClearErrorsOnEdit(form, errors as Ref<Record<string, string>>);

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
        if (!form.value.start_date && form.value.start_year) {
            form.value.start_date = `${form.value.start_year}-04-01`;
        }
        if (!form.value.end_date && form.value.end_year) {
            form.value.end_date = defaultEndDate(form.value.start_year, form.value.end_year);
        }
        errors.value = {};
    }
});

// Selectable years for the start/end year dropdowns (issue #25): 2000 up to
// next year, so a session starting or ending next year can still be planned.
const currentYear = new Date().getFullYear();
const lastSelectableYear = Math.max(currentYear + 10, form.value.start_year || 0, form.value.end_year || 0);
const yearRange = Array.from({ length: lastSelectableYear - 2000 + 1 }, (_, i) => 2000 + i);
const startYearOptions = yearRange;
const endYearOptions = yearRange;
// Newest year first - the most likely pick for a new session, so it is
// what the office sees without scrolling.
const startYearSelectOptions = startYearOptions.map((year) => ({ value: year, label: String(year) })).reverse();
const endYearSelectOptions = endYearOptions.map((year) => ({ value: year, label: String(year) })).reverse();

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
        if (!form.value.start_date) {
            form.value.start_date = `${year}-04-01`;
        }
    },
    { immediate: true },
);

watch(
    () => form.value.end_year,
    (year) => {
        if (!year) return;
        if (form.value.end_date && !form.value.end_date.startsWith(String(year))) {
            form.value.end_date = '';
        }
        if (!form.value.end_date) {
            form.value.end_date = defaultEndDate(form.value.start_year, year);
        }
    },
    { immediate: true },
);

const startDateMin = computed(() => form.value.start_year ? `${form.value.start_year}-01-01` : undefined);
const startDateMax = computed(() => form.value.start_year ? `${form.value.start_year}-12-31` : undefined);
const endDateMin = computed(() => form.value.end_year ? `${form.value.end_year}-01-01` : undefined);
const endDateMax = computed(() => form.value.end_year ? `${form.value.end_year}-12-31` : undefined);

// The native date input's MM/DD/YYYY display is the browser's own locale
// formatting and can't be changed - this readable English-month restatement
// underneath it is so the office doesn't have to decode the numeric format.
const formatReadableDate = (isoDate: string): string => {
    if (!isoDate) return '';
    const date = new Date(`${isoDate}T00:00:00`);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};
const startDateReadable = computed(() => formatReadableDate(form.value.start_date));
const endDateReadable = computed(() => formatReadableDate(form.value.end_date));

const submit = () => {
    const missing = requiredErrors(form, ['name', 'start_year', 'end_year', 'start_date', 'end_date']);
    if (Object.keys(missing).length > 0) {
        errors.value = missing as typeof errors.value;
        return;
    }

    processing.value = true;
    errors.value = {};

    const formData: Record<string, unknown> = {
        name: form.value.name,
        start_year: form.value.start_year,
        end_year: form.value.end_year,
        is_active: form.value.is_active ? 1 : 0,
        start_date: form.value.start_date || null,
        end_date: form.value.end_date || null,
    };

    // A campus-restricted user never sees the picker, so never sends this -
    // the backend scopes their session to their own campus regardless.
    if (props.isSuperAdmin) {
        formData.campus_ids = form.value.campus_ids;
    }

    if (props.session) {
        // Update
        axios.patch(`/settings/sessions/${props.session.id}`, formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Academic session saved.');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((error) => {
                errors.value = error.response?.data?.errors ?? {};
                if (Object.keys(errors.value).length > 0) {
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Could not save session. Check the form.');
                }
            }).finally(() => {
                processing.value = false;
            });
    } else {
        // Create
        axios.post('/settings/sessions', formData, {
            headers: { Accept: 'application/json' },
        }).then(() => {
                alert.success('Academic session added.');
                open.value = false;
                resetForm();
                emit('saved');
            }).catch((error) => {
                errors.value = error.response?.data?.errors ?? {};
                if (Object.keys(errors.value).length > 0) {
                    const firstError = Object.values(errors.value)[0];
                    alert.error(firstError);
                } else {
                    alert.error('Could not save session. Check the form.');
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
            <form novalidate
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
                            <SearchableSelect
                                id="start_year"
                                v-model="form.start_year"
                                :options="startYearSelectOptions"
                                placeholder="Select start year"
                                :class="errors.start_year ? 'border-destructive' : ''"
                            />
                            <InputError :message="errors.start_year" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_year">End Year <span class="text-destructive">*</span></Label>
                            <SearchableSelect
                                id="end_year"
                                v-model="form.end_year"
                                :options="endYearSelectOptions"
                                placeholder="Select end year"
                                :class="errors.end_year ? 'border-destructive' : ''"
                            />
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
                            <div class="relative">
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    :min="startDateMin"
                                    :max="startDateMax"
                                    class="[&::-webkit-datetime-edit-fields-wrapper]:text-transparent [&::-webkit-datetime-edit-text]:text-transparent"
                                    :class="{ 'border-destructive': errors.start_date }"
                                />
                                <span v-if="startDateReadable" class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-foreground">{{ startDateReadable }}</span>
                            </div>
                            <InputError :message="errors.start_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_date">End Date <span class="text-destructive">*</span></Label>
                            <div class="relative">
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="date"
                                    :min="endDateMin"
                                    :max="endDateMax"
                                    class="[&::-webkit-datetime-edit-fields-wrapper]:text-transparent [&::-webkit-datetime-edit-text]:text-transparent"
                                    :class="{ 'border-destructive': errors.end_date }"
                                />
                                <span v-if="endDateReadable" class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-foreground">{{ endDateReadable }}</span>
                            </div>
                            <InputError :message="errors.end_date" />
                        </div>
                    </div>

                    <div v-if="isSuperAdmin" class="grid gap-2">
                        <Label>Campuses <span class="text-destructive">*</span></Label>
                        <div class="flex items-center gap-2 border-b border-border pb-2">
                            <input
                                id="campus_ids_all"
                                type="checkbox"
                                :checked="form.campus_ids.length === campuses.length && campuses.length > 0"
                                class="rounded border-border text-primary shadow-sm focus:ring-primary"
                                @change="form.campus_ids = ($event.target as HTMLInputElement).checked ? campuses.map((c) => c.id) : []"
                            />
                            <Label for="campus_ids_all" class="font-normal">All Campuses</Label>
                        </div>
                        <div class="grid max-h-40 grid-cols-2 gap-2 overflow-y-auto">
                            <div v-for="campus in campuses" :key="campus.id" class="flex items-center gap-2">
                                <input
                                    :id="`campus_ids_${campus.id}`"
                                    v-model="form.campus_ids"
                                    type="checkbox"
                                    :value="campus.id"
                                    class="rounded border-border text-primary shadow-sm focus:ring-primary"
                                />
                                <Label :for="`campus_ids_${campus.id}`" class="font-normal">{{ campus.name }}</Label>
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
                        <Label for="is_active">Set as Active Session</Label>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : (session ? 'Update' : 'Create') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
