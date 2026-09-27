<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthSplitLayout from '@/components/auth/AuthSplitLayout.vue';
import AuthFormWrapper from '@/components/auth/AuthFormWrapper.vue';
import TextLink from '@/components/TextLink.vue';

const props = defineProps<{
    status?: string;
}>();

const logoutRoute = () => route('logout');

// Use props to avoid unused warning
const { status } = props;
</script>

<template>
    <AuthSplitLayout
        title="Verify email"
        description="Please verify your email address by clicking on the link we just emailed to you."
        illustration="verify"
    >
        <Head title="Email verification" />

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-success">
            A new verification link has been sent to the email address you provided during registration.
        </div>

        <AuthFormWrapper
            :action="route('verification.send')"
            method="post"
            submit-label="Resend verification email"
            submit-variant="secondary"
            :show-submit="true"
            class="space-y-4 text-center"
            v-slot="{ }"
        >
            <div class="space-x-1 text-center text-sm text-muted-foreground mt-4">
                <TextLink :href="logoutRoute()" as="button" class="mx-auto block text-sm">
                    Log out
                </TextLink>
            </div>
        </AuthFormWrapper>
    </AuthSplitLayout>
</template>