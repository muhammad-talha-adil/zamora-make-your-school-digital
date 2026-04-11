<template>
    <div class="col-span-full space-y-2">
        <Label for="image">{{ label }}</Label>
        <div class="flex items-start gap-4">
            <div class="flex-1">
                <Input
                    id="image"
                    type="file"
                    :accept="accept"
                    @change="handleChange"
                    class="h-11"
                />
                <p v-if="hint" class="mt-1 text-xs text-gray-500">
                    {{ hint }}
                </p>
                <InputError :message="error" />
            </div>
            <!-- Image Preview with Remove Button -->
            <div v-if="previewUrl" class="shrink-0 relative group">
                <img
                    :src="previewUrl"
                    :alt="previewAlt"
                    class="h-20 w-20 rounded-lg border border-gray-200 object-cover dark:border-gray-700"
                />
                <Button
                    type="button"
                    variant="destructive"
                    size="icon"
                    class="absolute -top-2 -right-2 h-6 w-6 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                    @click="handleRemove"
                    aria-label="Remove image"
                >
                    <Icon icon="x" class="h-3 w-3" />
                </Button>
            </div>
            <!-- Current Image (for edit mode) -->
            <div v-else-if="currentImageUrl && !removeCurrent" class="shrink-0 relative group">
                <img
                    :src="currentImageUrl"
                    :alt="previewAlt"
                    class="h-20 w-20 rounded-lg border border-gray-200 object-cover dark:border-gray-700"
                />
                <Button
                    type="button"
                    variant="destructive"
                    size="icon"
                    class="absolute -top-2 -right-2 h-6 w-6 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                    @click="handleRemove"
                    aria-label="Remove current image"
                >
                    <Icon icon="x" class="h-3 w-3" />
                </Button>
            </div>
            <!-- Placeholder -->
            <div
                v-else
                class="flex h-20 w-20 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-700"
            >
                <Icon
                    icon="user"
                    class="h-10 w-10 text-gray-400"
                />
            </div>
        </div>
        <input v-if="removeCurrent" type="hidden" name="remove_image" value="1" />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

interface Props {
    modelValue?: File | null;
    currentImageUrl?: string | null;
    label?: string;
    hint?: string;
    accept?: string;
    maxSize?: number; // in bytes
    error?: string;
    previewAlt?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    currentImageUrl: null,
    label: 'Photo',
    hint: 'Upload JPG, PNG, GIF, or WebP (max 2MB)',
    accept: 'image/jpeg,image/png,image/gif,image/webp',
    maxSize: 2 * 1024 * 1024, // 2MB default
    error: '',
    previewAlt: 'Preview',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: File | null): void;
    (e: 'update:removeCurrent', value: boolean): void;
    (e: 'update:error', value: string): void;
}>();

// State
const previewUrl = ref<string | null>(null);
const removeCurrent = ref(false);
const imageError = ref('');

// Computed
const currentImageUrl = computed(() => props.currentImageUrl);
const previewAlt = computed(() => props.previewAlt);

// Methods
const handleChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    imageError.value = '';
    removeCurrent.value = false;

    if (file) {
        const allowedTypes = props.accept.split(',').map(t => t.trim());
        if (!allowedTypes.includes(file.type)) {
            imageError.value = 'Please select a valid image file (JPG, PNG, GIF, or WebP)';
            emit('update:modelValue', null);
            previewUrl.value = null;
            target.value = '';
            return;
        }

        if (file.size > props.maxSize) {
            imageError.value = `Image size must be less than ${Math.round(props.maxSize / 1024 / 1024)}MB`;
            emit('update:modelValue', null);
            previewUrl.value = null;
            target.value = '';
            return;
        }

        emit('update:modelValue', file);

        const reader = new FileReader();
        reader.onload = (e) => {
            previewUrl.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    } else {
        emit('update:modelValue', null);
        previewUrl.value = null;
    }
};

const handleRemove = () => {
    emit('update:modelValue', null);
    previewUrl.value = null;
    imageError.value = '';
    removeCurrent.value = true;
    const imageInput = document.getElementById('image') as HTMLInputElement;
    if (imageInput) imageInput.value = '';
};

// Expose method to set preview from parent (for edit mode with existing image)
const setPreview = (url: string) => {
    previewUrl.value = url;
};

// Expose method to clear state
const clear = () => {
    previewUrl.value = null;
    removeCurrent.value = false;
    imageError.value = '';
};

defineExpose({
    setPreview,
    clear,
});
</script>
