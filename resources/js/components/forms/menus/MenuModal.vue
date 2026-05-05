<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { ComboboxInput } from '@/components/ui/combobox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    open: boolean;
    mode: 'create' | 'edit';
    menu?: {
        id: number;
        title: string;
        icon?: string;
        path: string;
        url?: string;
        parent_id?: number;
        order: number;
        type: string;
        is_active: boolean;
    };
    parentMenus: Array<{
        id: number;
        title: string;
    }>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    saved: [];
}>();

const typeOptions = [
    { id: 'main', name: 'Main' },
    { id: 'footer', name: 'Footer' },
];

const action = computed(() => {
    if (props.mode === 'create') {
        return '/settings/menus';
    } else {
        return `/settings/menus/${props.menu!.id}`;
    }
});

const title = computed(() => props.mode === 'create' ? 'Create Menu' : 'Edit Menu');

const description = computed(() =>
    props.mode === 'create'
        ? 'Add a new menu item to the navigation.'
        : 'Update the menu item details.'
);

const buttonText = computed(() => props.mode === 'create' ? 'Create Menu' : 'Update Menu');

const form = useForm({
    title: '',
    icon: '',
    url: '',
    parent_id: null as number | null,
    order: 0,
    type: 'main',
    is_active: true,
});

const syncForm = () => {
    form.defaults({
        title: props.mode === 'edit' ? props.menu?.title || '' : '',
        icon: props.mode === 'edit' ? props.menu?.icon || '' : '',
        url: props.mode === 'edit' ? props.menu?.url || '' : '',
        parent_id: props.mode === 'edit' ? props.menu?.parent_id || null : null,
        order: props.mode === 'edit' ? props.menu?.order || 0 : 0,
        type: props.mode === 'edit' ? props.menu?.type || 'main' : 'main',
        is_active: props.mode === 'edit' ? props.menu?.is_active ?? true : true,
    });
    form.reset();
    form.clearErrors();
};

watch(
    () => [props.open, props.mode, props.menu?.id],
    () => {
        if (props.open) {
            syncForm();
        }
    },
    { immediate: true },
);

const closeModal = () => {
    emit('update:open', false);
};

const submitForm = () => {
    const requestOptions = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            closeModal();
        },
    };

    if (props.mode === 'create') {
        form.post(action.value, requestOptions);
        return;
    }

    form.patch(action.value, requestOptions);
};
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-106.25">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submitForm">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input
                            v-model="form.title"
                            id="title"
                            name="title"
                            :default-value="''"
                            required
                            placeholder="Menu title"
                        />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="icon">Icon</Label>
                        <Input
                            v-model="form.icon"
                            id="icon"
                            name="icon"
                            :default-value="''"
                            placeholder="Icon name (optional)"
                        />
                        <InputError class="mt-2" :message="form.errors.icon" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="url">URL</Label>
                        <Input
                            v-model="form.url"
                            id="url"
                            name="url"
                            :default-value="''"
                            placeholder="External URL (optional)"
                        />
                        <InputError class="mt-2" :message="form.errors.url" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="parent_id">Parent Menu</Label>
                        <ComboboxInput
                            v-model="form.parent_id"
                            id="parent_id"
                            name="parent_id"
                            placeholder="Select parent menu (optional)"
                            :initial-items="props.parentMenus.map(menu => ({ id: menu.id, name: menu.title }))"
                            :default-value="null"
                            value-type="id"
                        />
                        <InputError class="mt-2" :message="form.errors.parent_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="order">Order</Label>
                        <Input
                            v-model="form.order"
                            id="order"
                            name="order"
                            type="number"
                            :default-value="0"
                            placeholder="0"
                        />
                        <InputError class="mt-2" :message="form.errors.order" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="type">Type</Label>
                        <ComboboxInput
                            v-model="form.type"
                            id="type"
                            name="type"
                            placeholder="Select type"
                            :initial-items="typeOptions"
                            :default-value="'main'"
                            value-type="id"
                        />
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>

                    <div class="flex items-center space-x-2 col-span-full">
                        <Checkbox v-model:checked="form.is_active" id="is_active" name="is_active" :default-checked="true" />
                        <Label for="is_active">Active</Label>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="secondary" @click="closeModal">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ buttonText }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

