<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { disable, enable, show } from '@/routes/two-factor';
import { type BreadcrumbItem } from '@/types';
import { ShieldBan, ShieldCheck } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;

const activeTab = ref('profile');

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => {
    clearTwoFactorAuthData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Profile Settings
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage your account settings and security.
                    </p>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            @click="activeTab = 'profile'"
                            :class="[
                                activeTab === 'profile'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Profile
                        </button>
                        <button
                            @click="activeTab = 'password'"
                            :class="[
                                activeTab === 'password'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Password
                        </button>
                        <button
                            @click="activeTab = 'two-factor'"
                            :class="[
                                activeTab === 'two-factor'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Two Factor
                        </button>
                    </nav>
                </div>

                <!-- Profile Tab -->
                <div v-if="activeTab === 'profile'">
                    <Form
                        v-bind="ProfileController.update.form()"
                        class="space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                class="mt-1 block w-full"
                                name="name"
                                :default-value="user.name"
                                required
                                autocomplete="name"
                                placeholder="Full name"
                            />
                            <InputError class="mt-2" :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                name="email"
                                :default-value="user.email"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                            />
                            <InputError class="mt-2" :message="errors.email" />
                        </div>

                        <div v-if="mustVerifyEmail && !user.email_verified_at">
                            <p class="-mt-4 text-sm text-muted-foreground">
                                Your email address is unverified.
                                <Link
                                    :href="send()"
                                    as="button"
                                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                >
                                    Click here to resend the verification email.
                                </Link>
                            </p>

                            <div
                                v-if="status === 'verification-link-sent'"
                                class="mt-2 text-sm font-medium text-green-600"
                            >
                                A new verification link has been sent to your email
                                address.
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <Button
                                :disabled="processing"
                                data-test="update-profile-button"
                                >Save</Button
                            >

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-sm text-neutral-600"
                                >
                                    Saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>

                    <DeleteUser />
                </div>

                <!-- Password Tab -->
                <div v-if="activeTab === 'password'">
                    <div class="space-y-6">
                        <HeadingSmall
                            title="Update password"
                            description="Ensure your account is using a long, random password to stay secure"
                        />

                        <Form
                            v-bind="PasswordController.update.form()"
                            :options="{
                                preserveScroll: true,
                            }"
                            reset-on-success
                            :reset-on-error="[
                                'password',
                                'password_confirmation',
                                'current_password',
                            ]"
                            class="space-y-6"
                            v-slot="{ errors, processing, recentlySuccessful }"
                        >
                            <div class="grid gap-2">
                                <Label for="current_password">Current password</Label>
                                <Input
                                    id="current_password"
                                    name="current_password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    autocomplete="current-password"
                                    placeholder="Current password"
                                />
                                <InputError :message="errors.current_password" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password">New password</Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    autocomplete="new-password"
                                    placeholder="New password"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password_confirmation"
                                    >Confirm password</Label
                                >
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full"
                                    autocomplete="new-password"
                                    placeholder="Confirm password"
                                />
                                <InputError :message="errors.password_confirmation" />
                            </div>

                            <div class="flex items-center gap-4">
                                <Button
                                    :disabled="processing"
                                    data-test="update-password-button"
                                    >Save password</Button
                                >

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p
                                        v-show="recentlySuccessful"
                                        class="text-sm text-neutral-600"
                                    >
                                        Saved.
                                    </p>
                                </Transition>
                            </div>
                        </Form>
                    </div>
                </div>

                <!-- Two Factor Tab -->
                <div v-if="activeTab === 'two-factor'">
                    <div class="space-y-6">
                        <HeadingSmall
                            title="Two-Factor Authentication"
                            description="Manage your two-factor authentication settings"
                        />

                        <div
                            v-if="!twoFactorEnabled"
                            class="flex flex-col items-start justify-start space-y-4"
                        >
                            <Badge variant="destructive">Disabled</Badge>

                            <p class="text-muted-foreground">
                                When you enable two-factor authentication, you will be
                                prompted for a secure pin during login. This pin can be
                                retrieved from a TOTP-supported application on your
                                phone.
                            </p>

                            <div>
                                <Button
                                    v-if="hasSetupData"
                                    @click="showSetupModal = true"
                                >
                                    <ShieldCheck />Continue Setup
                                </Button>
                                <Form
                                    v-else
                                    v-bind="enable.form()"
                                    @success="showSetupModal = true"
                                    #default="{ processing }"
                                >
                                    <Button type="submit" :disabled="processing">
                                        <ShieldCheck />Enable 2FA</Button
                                    ></Form
                                >
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex flex-col items-start justify-start space-y-4"
                        >
                            <Badge variant="default">Enabled</Badge>

                            <p class="text-muted-foreground">
                                With two-factor authentication enabled, you will be
                                prompted for a secure, random pin during login, which
                                you can retrieve from the TOTP-supported application on
                                your phone.
                            </p>

                            <TwoFactorRecoveryCodes />

                            <div class="relative inline">
                                <Form v-bind="disable.form()" #default="{ processing }">
                                    <Button
                                        variant="destructive"
                                        type="submit"
                                        :disabled="processing"
                                    >
                                        <ShieldBan />
                                        Disable 2FA
                                    </Button>
                                </Form>
                            </div>
                        </div>

                        <TwoFactorSetupModal
                            v-model:isOpen="showSetupModal"
                            :requiresConfirmation="requiresConfirmation"
                            :twoFactorEnabled="twoFactorEnabled"
                        />
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

