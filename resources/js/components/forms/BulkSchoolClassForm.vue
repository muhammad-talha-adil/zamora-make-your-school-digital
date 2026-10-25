<script setup lang="ts">
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { alert } from '@/utils';

import { Button } from '@/components/ui/button';
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

interface Props {
    isSuperAdmin?: boolean;
    campuses?: Array<{ id: number; name: string }>;
}

const props = withDefaults(defineProps<Props>(), {
    isSuperAdmin: false,
    campuses: () => [],
});

const emit = defineEmits<{ saved: [] }>();

const open = ref(false);
const processing = ref(false);

const selectedCampusIds = ref<number[]>([]);
const classCount = ref(2);
const classNames = ref<string[]>(['', '']);

watch(classCount, (count) => {
    const n = Math.max(1, Math.min(50, count || 1));
    if (n > classNames.value.length) {
        classNames.value.push(...Array(n - classNames.value.length).fill(''));
    } else if (n < classNames.value.length) {
        classNames.value.length = n;
    }
});

const allSelected = computed(
    () => selectedCampusIds.value.length === props.campuses.length && props.campuses.length > 0,
);

const toggleAll = (checked: boolean) => {
    selectedCampusIds.value = checked ? props.campuses.map((c) => c.id) : [];
};

const isValid = computed(() => {
    const names = classNames.value.map((n) => n.trim()).filter(Boolean);

    return (!props.isSuperAdmin || selectedCampusIds.value.length > 0) && names.length > 0;
});

watch(open, (isOpen) => {
    if (isOpen) {
        selectedCampusIds.value = [];
        classCount.value = 2;
        classNames.value = ['', ''];
    }
});

const submit = () => {
    processing.value = true;

    const names = classNames.value.map((n) => n.trim()).filter(Boolean);
    const payload: Record<string, unknown> = { class_names: names };
    if (props.isSuperAdmin) {
        payload.campus_ids = selectedCampusIds.value;
    }

    axios.post('/settings/school-classes/bulk', payload, {
        headers: { Accept: 'application/json' },
    }).then((response) => {
            alert.success(response.data.message ?? 'Classes created successfully!');
            open.value = false;
            emit('saved');
        }).catch((error) => {
            const errors = error.response?.data?.errors;
            const message = errors
                ? Object.values(errors).flat().join(' ')
                : (error.response?.data?.message ?? 'Failed to create classes. Please try again.');
            alert.error(message);
        }).finally(() => {
            processing.value = false;
        });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="outline">
                <Icon icon="layers" class="mr-1" />
                Bulk Create
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-130">
            <form @submit.prevent="submit" class="space-y-4">
                <DialogHeader>
                    <DialogTitle>Bulk Create Classes</DialogTitle>
                    <DialogDescription>
                        Pick class names and campuses once - every name gets created in every campus, in one go.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-2">
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label>Class Names <span class="text-destructive">*</span></Label>
                            <div class="flex items-center gap-2">
                                <Label for="class_count" class="text-xs font-normal text-muted-foreground">How many?</Label>
                                <Input id="class_count" v-model.number="classCount" type="number" min="1" max="50" class="h-8 w-16" />
                            </div>
                        </div>
                        <div v-for="(_, index) in classNames" :key="index">
                            <Input
                                v-model="classNames[index]"
                                :placeholder="`e.g., Class ${index + 1}`"
                            />
                        </div>
                    </div>

                    <div v-if="isSuperAdmin" class="grid gap-2">
                        <Label>Campuses <span class="text-destructive">*</span></Label>
                        <div class="flex items-center gap-2 border-b border-border pb-2">
                            <input
                                id="bulk_class_campuses_all"
                                type="checkbox"
                                :checked="allSelected"
                                class="rounded border-border text-primary shadow-sm focus:ring-primary"
                                @change="toggleAll(($event.target as HTMLInputElement).checked)"
                            />
                            <Label for="bulk_class_campuses_all" class="font-normal">All Campuses</Label>
                        </div>
                        <div class="grid max-h-40 grid-cols-2 gap-2 overflow-y-auto">
                            <div v-for="campus in campuses" :key="campus.id" class="flex items-center gap-2">
                                <input
                                    :id="`bulk_class_campus_${campus.id}`"
                                    v-model="selectedCampusIds"
                                    type="checkbox"
                                    :value="campus.id"
                                    class="rounded border-border text-primary shadow-sm focus:ring-primary"
                                />
                                <Label :for="`bulk_class_campus_${campus.id}`" class="font-normal">{{ campus.name }}</Label>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">A name already used in a picked campus is skipped automatically - you'll see exactly which ones.</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="processing || !isValid">
                        {{ processing ? 'Creating...' : 'Create Classes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
