<script setup lang="ts">
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { alert } from '@/utils';

// Components
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

// Props
interface Props {
    schoolClasses?: { id: number; name: string }[];
}

const props = withDefaults(defineProps<Props>(), {
    schoolClasses: () => [],
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

const open = ref(false);
const processing = ref(false);
const errorMessage = ref('');

const selectedClassIds = ref<number[]>([]);
// Starts at two rows - the common "Boys"/"Girls" case - but any number can
// be added or removed before submitting.
const sectionNames = ref<string[]>(['', '']);
const sectionCount = ref(2);

// Resizes the name fields to match the typed count - growing adds blank
// rows, shrinking drops from the end (never touches what's already typed).
watch(sectionCount, (count) => {
    const n = Math.max(1, Math.min(50, count || 1));
    if (n > sectionNames.value.length) {
        sectionNames.value.push(...Array(n - sectionNames.value.length).fill(''));
    } else if (n < sectionNames.value.length) {
        sectionNames.value.length = n;
    }
});

const allSelected = computed(
    () => selectedClassIds.value.length === props.schoolClasses.length && props.schoolClasses.length > 0,
);

const toggleAll = (checked: boolean) => {
    selectedClassIds.value = checked ? props.schoolClasses.map((c) => c.id) : [];
};

const addNameRow = () => {
    sectionNames.value.push('');
};

const removeNameRow = (index: number) => {
    sectionNames.value.splice(index, 1);
};

const isValid = computed(() => {
    const names = sectionNames.value.map((n) => n.trim()).filter(Boolean);

    return selectedClassIds.value.length > 0 && names.length > 0;
});

watch(open, (isOpen) => {
    if (isOpen) {
        selectedClassIds.value = [];
        sectionCount.value = 2;
        sectionNames.value = ['', ''];
        errorMessage.value = '';
    }
});

const submit = () => {
    processing.value = true;
    errorMessage.value = '';

    const names = sectionNames.value.map((n) => n.trim()).filter(Boolean);

    axios.post('/settings/sections/bulk', {
        class_ids: selectedClassIds.value,
        section_names: names,
    }, {
        headers: { Accept: 'application/json' },
    }).then((response) => {
            alert.success(response.data.message ?? 'Sections created successfully!');
            open.value = false;
            emit('saved');
        }).catch((error) => {
            const errors = error.response?.data?.errors;
            errorMessage.value = errors
                ? Object.values(errors).flat().join(' ')
                : (error.response?.data?.message ?? 'Failed to create sections. Please try again.');
            alert.error(errorMessage.value);
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
                    <DialogTitle>Bulk Create Sections</DialogTitle>
                    <DialogDescription>
                        Pick classes and section names once - every class gets every name, in one go.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-2">
                    <div class="grid gap-2">
                        <Label>Classes <span class="text-destructive">*</span></Label>
                        <div class="flex items-center gap-2 border-b border-border pb-2">
                            <input
                                id="bulk_section_classes_all"
                                type="checkbox"
                                :checked="allSelected"
                                class="rounded border-border text-primary shadow-sm focus:ring-primary"
                                @change="toggleAll(($event.target as HTMLInputElement).checked)"
                            />
                            <Label for="bulk_section_classes_all" class="font-normal">All Classes</Label>
                        </div>
                        <div class="grid max-h-40 grid-cols-2 gap-2 overflow-y-auto">
                            <div v-for="schoolClass in schoolClasses" :key="schoolClass.id" class="flex items-center gap-2">
                                <input
                                    :id="`bulk_section_class_${schoolClass.id}`"
                                    v-model="selectedClassIds"
                                    type="checkbox"
                                    :value="schoolClass.id"
                                    class="rounded border-border text-primary shadow-sm focus:ring-primary"
                                />
                                <Label :for="`bulk_section_class_${schoolClass.id}`" class="font-normal">{{ schoolClass.name }}</Label>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label>Section Names <span class="text-destructive">*</span></Label>
                            <div class="flex items-center gap-2">
                                <Label for="section_count" class="text-xs font-normal text-muted-foreground">How many?</Label>
                                <Input id="section_count" v-model.number="sectionCount" type="number" min="1" max="50" class="h-8 w-16" />
                            </div>
                        </div>
                        <div v-for="(name, index) in sectionNames" :key="index" class="flex items-center gap-2">
                            <Input
                                v-model="sectionNames[index]"
                                :placeholder="index === 0 ? 'e.g., Boys' : index === 1 ? 'e.g., Girls' : 'Section name'"
                            />
                            <Button
                                v-if="sectionNames.length > 1"
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label="Remove section name"
                                @click="removeNameRow(index)"
                            >
                                <Icon icon="x" class="h-4 w-4" />
                            </Button>
                        </div>
                        <Button type="button" variant="outline" size="sm" class="justify-self-start" @click="addNameRow">
                            <Icon icon="plus" class="mr-1 h-3.5 w-3.5" />
                            Add another name
                        </Button>
                        <p class="text-xs text-muted-foreground">Each name is created once per selected class - already-existing ones are skipped automatically.</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="processing || !isValid">
                        {{ processing ? 'Creating...' : 'Create Sections' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
