<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { alert } from '@/utils';

// Components
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

// Props
interface Props {
    holiday?: {
        id: number;
        title: string;
        start_date: string;
        end_date: string;
        campus_id: number | null;
        is_national: boolean;
        description?: string;
    };
    campuses: Array<{
        id: number;
        name: string;
    }>;
    trigger?: string;
    variant?: string;
    size?: string;
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Holiday',
    variant: 'default',
    size: 'default',
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

const isOpen = ref(false);
const errors = ref({});
const processing = ref(false);

// Form data
const form = ref({
    title: props.holiday?.title || '',
    start_date: props.holiday?.start_date || '',
    end_date: props.holiday?.end_date || '',
    campus_id: props.holiday?.campus_id || '',
    is_national: props.holiday?.is_national ?? true,
    description: props.holiday?.description || '',
});

const isEditing = computed(() => !!props.holiday?.id);

const openModal = () => {
    if (props.holiday) {
        form.value = {
            title: props.holiday.title,
            start_date: props.holiday.start_date,
            end_date: props.holiday.end_date,
            campus_id: props.holiday.campus_id ?? '',
            is_national: props.holiday.is_national,
            description: props.holiday.description || '',
        };
    } else {
        form.value = {
            title: '',
            start_date: '',
            end_date: '',
            campus_id: '',
            is_national: true,
            description: '',
        };
    }
    errors.value = {};
    isOpen.value = true;
};

const closeModal = () => {
    isOpen.value = false;
};

const submit = () => {
    processing.value = true;
    errors.value = {};

    const formData = {
        ...form.value,
        campus_id: form.value.is_national ? null : form.value.campus_id,
    };

    const url = isEditing.value
        ? `/attendance/settings/holidays/${props.holiday?.id}`
        : '/attendance/settings/holidays';

    const method = isEditing.value ? 'put' : 'post';

    router[method](url, formData, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success(
                isEditing.value
                    ? 'Holiday updated successfully!'
                    : 'Holiday created successfully!',
            );
            emit('saved');
            closeModal();
        },
        onError: (err) => {
            errors.value = err;
            alert.error(
                `Failed to ${isEditing.value ? 'update' : 'create'} holiday. Please check the errors.`,
            );
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <div>
        <!-- Trigger Button -->
        <Button
            :variant="(variant || 'default') as any"
            :size="(size || 'default') as any"
            @click="openModal"
        >
            <Icon icon="plus" class="mr-1" />
            <slot>{{ trigger }}</slot>
        </Button>

        <!-- Modal -->
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="closeModal"
        >
            <Card class="w-full max-w-md mx-4">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Icon icon="calendar" class="mr-2 h-5 w-5" />
                        {{ isEditing ? 'Edit' : 'Add' }} Holiday
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <Label for="title" class="flex items-center">
                                <Icon icon="tag" class="mr-1 h-4 w-4" />
                                Title *
                            </Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                :class="{ 'border-red-500': (errors as any).title }"
                                placeholder="e.g., Independence Day"
                            />
                            <InputError :message="(errors as any).title" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <Label for="start_date" class="flex items-center">
                                    <Icon icon="calendar" class="mr-1 h-4 w-4" />
                                    Start Date *
                                </Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    v-model="form.start_date"
                                    :class="{ 'border-red-500': (errors as any).start_date }"
                                />
                                <InputError :message="(errors as any).start_date" />
                            </div>
                            <div>
                                <Label for="end_date" class="flex items-center">
                                    <Icon icon="calendar" class="mr-1 h-4 w-4" />
                                    End Date *
                                </Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    v-model="form.end_date"
                                    :class="{ 'border-red-500': (errors as any).end_date }"
                                />
                                <InputError :message="(errors as any).end_date" />
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_national"
                                v-model:checked="form.is_national"
                            />
                            <Label for="is_national" class="flex items-center">
                                <Icon icon="flag" class="mr-1 h-4 w-4" />
                                National Holiday (All Campuses)
                            </Label>
                        </div>

                        <div v-if="!form.is_national">
                            <Label for="campus_id" class="flex items-center">
                                <Icon icon="building" class="mr-1 h-4 w-4" />
                                Campus
                            </Label>
                            <select
                                id="campus_id"
                                v-model="form.campus_id"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                :class="{ 'border-red-500': (errors as any).campus_id }"
                            >
                                <option value="">Select Campus</option>
                                <option v-for="campus in campuses" :key="campus.id" :value="campus.id">
                                    {{ campus.name }}
                                </option>
                            </select>
                            <InputError :message="(errors as any).campus_id" />
                        </div>

                        <div>
                            <Label for="description" class="flex items-center">
                                <Icon icon="file-text" class="mr-1 h-4 w-4" />
                                Description
                            </Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                :class="{ 'border-red-500': (errors as any).description }"
                                placeholder="Optional description"
                            ></textarea>
                            <InputError :message="(errors as any).description" />
                        </div>
                    </form>
                </CardContent>
                <CardFooter class="flex justify-end space-x-2">
                    <Button variant="outline" @click="closeModal">
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="processing"
                        @click="submit"
                    >
                        <Icon
                            v-if="processing"
                            icon="loader"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        {{ isEditing ? 'Update' : 'Create' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
