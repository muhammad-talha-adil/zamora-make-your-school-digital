<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { alert } from '@/utils';

// Components
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';

// Props
interface Props {
    school?: {
        id: number;
        name: string;
        slogan?: string;
        address?: string;
        phone?: string;
        logo_path?: string;
        is_active: boolean;
    };
}

const props = withDefaults(defineProps<Props>(), {});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Form data
const form = ref({
    name: props.school?.name || '',
    slogan: props.school?.slogan || '',
    address: props.school?.address || '',
    phone: props.school?.phone || '',
    logo: null as File | null,
    is_active: props.school?.is_active ?? true,
});

const errors = ref({});
const processing = ref(false);

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};

    router.post('/settings/school-profile', form.value, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('School information updated successfully!');
            emit('saved');
        },
        onError: (err) => {
            errors.value = err;
            alert.error('Failed to update school information. Please check the errors.');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-6">
        <!-- Basic Information -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="building" class="mr-2 h-5 w-5" />
                    Basic Information
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="name" class="flex items-center">
                            <Icon icon="tag" class="mr-1 h-4 w-4" />
                            School Name *
                        </Label>
                        <Input id="name" v-model="form.name" :class="{ 'border-red-500': (errors as any).name }" />
                        <InputError :message="(errors as any).name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="slogan" class="flex items-center">
                            <Icon icon="quote" class="mr-1 h-4 w-4" />
                            School Slogan/Motto
                        </Label>
                        <Input id="slogan" v-model="form.slogan" :class="{ 'border-red-500': (errors as any).slogan }" />
                        <p class="text-sm text-gray-500">
                            The school's motto or slogan (optional).
                        </p>
                        <InputError :message="(errors as any).slogan" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Contact Information -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="phone" class="mr-2 h-5 w-5" />
                    Contact Information
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="space-y-2">
                    <Label for="address" class="flex items-center">
                        <Icon icon="map-pin" class="mr-1 h-4 w-4" />
                        Address
                    </Label>
                    <textarea
                        id="address"
                        v-model="form.address"
                        rows="3"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        :class="{ 'border-red-500': (errors as any).address }"
                    ></textarea>
                    <InputError :message="(errors as any).address" />
                </div>

                <div class="space-y-2">
                    <Label for="phone" class="flex items-center">
                        <Icon icon="phone" class="mr-1 h-4 w-4" />
                        Phone
                    </Label>
                    <Input id="phone" v-model="form.phone" :class="{ 'border-red-500': (errors as any).phone }" />
                    <InputError :message="(errors as any).phone" />
                </div>
            </CardContent>
        </Card>

        <!-- Media and Status -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="image" class="mr-2 h-5 w-5" />
                    Media & Status
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="space-y-2">
                    <Label for="logo" class="flex items-center">
                        <Icon icon="upload" class="mr-1 h-4 w-4" />
                        Logo
                    </Label>
                    <Input
                        id="logo"
                        type="file"
                        @change="
                            (e: Event) =>
                                (form.logo =
                                    (e.target as HTMLInputElement)
                                        .files?.[0] || null)
                        "
                        accept="image/*"
                        :class="{ 'border-red-500': (errors as any).logo }"
                    />
                    <p class="text-sm text-gray-500">
                        Upload a new logo (optional, max 2MB)
                    </p>
                    <InputError :message="(errors as any).logo" />
                    <div v-if="props.school?.logo_path" class="mt-2">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Current logo:</p>
                        <img :src="props.school.logo_path" alt="School Logo" class="mt-1 h-16 w-16 object-cover rounded" />
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <Checkbox id="is_active" v-model:checked="form.is_active" />
                    <Label for="is_active" class="flex items-center">
                        <Icon icon="check-circle" class="mr-1 h-4 w-4" />
                        School is Active
                    </Label>
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end">
            <Button type="submit" :disabled="processing">
                <Icon v-if="processing" name="loader" class="mr-2 h-4 w-4 animate-spin" />
                Save Changes
            </Button>
        </div>
    </form>
</template>
