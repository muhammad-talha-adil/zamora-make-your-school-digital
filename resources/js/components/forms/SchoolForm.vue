<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';
import { alert } from '@/utils';
import { generateThemeFromLogo } from '@/utils/logoTheme';

// Components
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
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
        website_enabled?: boolean;
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
    website_enabled: props.school?.website_enabled ?? true,
    theme_colors: null as string | null,
});

const errors = ref({});
const processing = ref(false);

// Live preview of a newly selected logo file, before it's saved.
const logoPreview = ref<string | null>(null);

const onLogoChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] || null;
    form.value.logo = file;
    form.value.theme_colors = null;

    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
    }
    logoPreview.value = file ? URL.createObjectURL(file) : null;

    if (file) {
        // Best-effort: an auto-generated theme is a bonus on top of the
        // upload, never a requirement for it. Runs alongside the existing
        // preview logic and never blocks or fails the form.
        generateThemeFromLogo(file)
            .then((theme) => {
                if (theme && form.value.logo === file) {
                    form.value.theme_colors = JSON.stringify(theme);
                }
            })
            .catch(() => {
                // Silently fall back to no auto-theme.
            });
    }
};

onBeforeUnmount(() => {
    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
    }
});

/**
 * Updates the browser tab's favicon immediately after a successful save,
 * so a changed logo doesn't require a full page reload to be reflected.
 */
const updateFavicon = (logoPath: string): void => {
    const links = document.querySelectorAll<HTMLLinkElement>('link[rel="icon"], link[rel="apple-touch-icon"]');
    links.forEach((link) => {
        link.href = logoPath;
    });
};

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};

    router.post('/settings/school-profile', form.value, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('School information updated successfully!');
            const updatedLogoPath = (usePage().props.school as { logo_path?: string } | undefined)?.logo_path;
            if (updatedLogoPath) {
                updateFavicon(updatedLogoPath);
            }
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
                        <Input id="name" v-model="form.name" :class="{ 'border-destructive': (errors as any).name }" />
                        <InputError :message="(errors as any).name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="slogan" class="flex items-center">
                            <Icon icon="quote" class="mr-1 h-4 w-4" />
                            School Slogan/Motto
                        </Label>
                        <Input id="slogan" v-model="form.slogan" :class="{ 'border-destructive': (errors as any).slogan }" />
                        <p class="text-sm text-muted-foreground">
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
                        class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"
                        :class="{ 'border-destructive': (errors as any).address }"
                    ></textarea>
                    <InputError :message="(errors as any).address" />
                </div>

                <div class="space-y-2">
                    <Label for="phone" class="flex items-center">
                        <Icon icon="phone" class="mr-1 h-4 w-4" />
                        Phone
                    </Label>
                    <Input id="phone" v-model="form.phone" maxlength="11" :class="{ 'border-destructive': (errors as any).phone }" />
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
                        @change="onLogoChange"
                        accept="image/*"
                        :class="{ 'border-destructive': (errors as any).logo }"
                    />
                    <p class="text-sm text-muted-foreground">
                        Upload a new logo (optional, max 2MB)
                    </p>
                    <InputError :message="(errors as any).logo" />
                    <div v-if="logoPreview" class="mt-2">
                        <p class="text-sm text-muted-foreground">New logo preview:</p>
                        <img :src="logoPreview" alt="New logo preview" class="mt-1 h-16 w-16 object-cover rounded" />
                    </div>
                    <div v-else-if="props.school?.logo_path" class="mt-2">
                        <p class="text-sm text-muted-foreground">Current logo:</p>
                        <img :src="props.school.logo_path" alt="School Logo" class="mt-1 h-16 w-16 object-cover rounded" />
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-border p-3">
                        <Label for="is_active" class="flex items-center">
                            <Icon icon="check-circle" class="mr-1 h-4 w-4" />
                            School is Active
                        </Label>
                        <Switch id="is_active" v-model:checked="form.is_active" />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        When off, only the developer and owner can log in to the system; all other users are blocked.
                    </p>
                    <InputError :message="(errors as any).is_active" />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-border p-3">
                        <Label for="website_enabled" class="flex items-center">
                            <Icon icon="globe" class="mr-1 h-4 w-4" />
                            Public Website Active
                        </Label>
                        <Switch id="website_enabled" v-model:checked="form.website_enabled" />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        When off, visitors to your school's web address are sent straight to the login page instead of seeing the public site.
                    </p>
                    <InputError :message="(errors as any).website_enabled" />
                </div>
            </CardContent>
        </Card>

        <div class="flex flex-wrap gap-2 justify-end">
            <Button type="submit" :disabled="processing">
                <Icon v-if="processing" name="loader" class="mr-2 h-4 w-4 animate-spin" />
                Save Changes
            </Button>
        </div>
    </form>
</template>
