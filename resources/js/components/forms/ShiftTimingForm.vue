<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { alert } from '@/utils';
import { useFormValidity } from '@/composables/useFormValidity';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardFooter,
} from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import RowAction from '@/components/tables/RowAction.vue';

interface Props {
    shiftTiming?: {
        id: number;
        campus_id: number;
        session_id: number | null;
        class_ids: number[] | null;
        name: string;
        starts_on: string;
        ends_on: string;
        day_starts_at: string;
        late_after: string;
        break_starts_at: string | null;
        break_ends_at: string | null;
        day_ends_at: string | null;
        is_active: boolean;
        notes?: string | null;
    };
    campuses: Array<{ id: number; name: string }>;
    classes: Array<{ id: number; name: string }>;
    trigger?: string;
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Shift Timing',
});

const emit = defineEmits<{ saved: [] }>();

const isOpen = ref(false);
const errors = ref({});
const processing = ref(false);

const defaultForm = () => ({
    campus_id: props.shiftTiming?.campus_id ?? '',
    session_id: props.shiftTiming?.session_id ?? '',
    class_ids: (props.shiftTiming?.class_ids ?? []) as number[],
    name: props.shiftTiming?.name || '',
    starts_on: props.shiftTiming?.starts_on || '',
    ends_on: props.shiftTiming?.ends_on || '',
    day_starts_at: props.shiftTiming?.day_starts_at?.slice(0, 5) || '',
    late_after: props.shiftTiming?.late_after?.slice(0, 5) || '',
    break_starts_at: props.shiftTiming?.break_starts_at?.slice(0, 5) || '',
    break_ends_at: props.shiftTiming?.break_ends_at?.slice(0, 5) || '',
    day_ends_at: props.shiftTiming?.day_ends_at?.slice(0, 5) || '',
    is_active: props.shiftTiming?.is_active ?? true,
    notes: props.shiftTiming?.notes || '',
});

const form = ref(defaultForm());

const { isValid } = useFormValidity(form, ['campus_id', 'name', 'starts_on', 'ends_on', 'day_starts_at', 'late_after']);

const isEditing = computed(() => !!props.shiftTiming?.id);

const openModal = () => {
    form.value = defaultForm();
    errors.value = {};
    isOpen.value = true;
};

const closeModal = () => {
    isOpen.value = false;
};

const toggleClass = (classId: number) => {
    const index = form.value.class_ids.indexOf(classId);
    if (index === -1) {
        form.value.class_ids.push(classId);
    } else {
        form.value.class_ids.splice(index, 1);
    }
};

const submit = () => {
    processing.value = true;
    errors.value = {};

    const url = isEditing.value
        ? `/attendance/settings/shift-timings/${props.shiftTiming?.id}`
        : '/attendance/settings/shift-timings';
    const method = isEditing.value ? 'put' : 'post';

    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success(isEditing.value ? 'Shift timing updated successfully!' : 'Shift timing created successfully!');
            emit('saved');
            closeModal();
        },
        onError: (err) => {
            errors.value = err;
            alert.error(`Failed to ${isEditing.value ? 'update' : 'create'} shift timing. Please check the errors.`);
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <RowAction v-if="props.shiftTiming" kind="edit" @click="openModal" />
        <Button v-else size="sm" @click="openModal">
            <Icon icon="plus" class="mr-1" />
            <slot>{{ trigger }}</slot>
        </Button>

        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 overflow-y-auto py-8"
            @click.self="closeModal"
        >
            <Card class="w-full max-w-lg mx-4">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Icon icon="clock" class="mr-2 h-5 w-5" />
                        {{ isEditing ? 'Edit' : 'Add' }} Shift Timing
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <Label for="name">Name *</Label>
                            <Input id="name" v-model="form.name" placeholder="e.g., Junior Classes" :class="{ 'border-destructive': (errors as any).name }" />
                            <InputError :message="(errors as any).name" />
                        </div>

                        <div>
                            <Label for="campus_id">Campus *</Label>
                            <select id="campus_id" v-model="form.campus_id" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none">
                                <option value="">Select Campus</option>
                                <option v-for="campus in campuses" :key="campus.id" :value="campus.id">{{ campus.name }}</option>
                            </select>
                            <InputError :message="(errors as any).campus_id" />
                        </div>

                        <div>
                            <Label>Classes covered</Label>
                            <p class="text-xs text-muted-foreground mb-2">Leave all unchecked to apply this timing to every class on the campus.</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-1 max-h-32 overflow-y-auto border border-border rounded-md p-2">
                                <label v-for="cls in classes" :key="cls.id" class="flex items-center gap-1 text-sm">
                                    <input type="checkbox" :checked="form.class_ids.includes(cls.id)" @change="toggleClass(cls.id)" />
                                    {{ cls.name }}
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="starts_on">Starts On *</Label>
                                <Input id="starts_on" type="date" v-model="form.starts_on" :class="{ 'border-destructive': (errors as any).starts_on }" />
                                <InputError :message="(errors as any).starts_on" />
                            </div>
                            <div>
                                <Label for="ends_on">Ends On *</Label>
                                <Input id="ends_on" type="date" v-model="form.ends_on" :class="{ 'border-destructive': (errors as any).ends_on }" />
                                <InputError :message="(errors as any).ends_on" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="day_starts_at">Check-in Time *</Label>
                                <Input id="day_starts_at" type="time" v-model="form.day_starts_at" :class="{ 'border-destructive': (errors as any).day_starts_at }" />
                                <InputError :message="(errors as any).day_starts_at" />
                            </div>
                            <div>
                                <Label for="late_after">Late After *</Label>
                                <Input id="late_after" type="time" v-model="form.late_after" :class="{ 'border-destructive': (errors as any).late_after }" />
                                <p class="text-xs text-muted-foreground mt-1">The grace window — arrivals up to this time are not late.</p>
                                <InputError :message="(errors as any).late_after" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="break_starts_at">Break Starts</Label>
                                <Input id="break_starts_at" type="time" v-model="form.break_starts_at" />
                            </div>
                            <div>
                                <Label for="break_ends_at">Break Ends</Label>
                                <Input id="break_ends_at" type="time" v-model="form.break_ends_at" :class="{ 'border-destructive': (errors as any).break_ends_at }" />
                                <InputError :message="(errors as any).break_ends_at" />
                            </div>
                        </div>

                        <div>
                            <Label for="day_ends_at">Check-out Time</Label>
                            <Input id="day_ends_at" type="time" v-model="form.day_ends_at" />
                            <p class="text-xs text-muted-foreground mt-1">Check-out cannot be recorded before this time on the day.</p>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Checkbox id="is_active" v-model:checked="form.is_active" />
                            <Label for="is_active">Active</Label>
                        </div>

                        <div>
                            <Label for="notes">Notes</Label>
                            <textarea id="notes" v-model="form.notes" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                        </div>
                    </form>
                </CardContent>
                <CardFooter class="flex flex-wrap justify-end gap-2">
                    <Button variant="outline" @click="closeModal">Cancel</Button>
                    <Button type="submit" :disabled="processing || !isValid" @click="submit">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isEditing ? 'Update' : 'Create' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
