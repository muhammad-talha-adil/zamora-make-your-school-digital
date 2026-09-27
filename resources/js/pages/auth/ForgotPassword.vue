<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthSplitLayout from '@/components/auth/AuthSplitLayout.vue';
import AuthFormWrapper from '@/components/auth/AuthFormWrapper.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    status?: string;
}>();

const loginRoute = () => route('login');

// Use props to avoid unused warning
const { status } = props;
</script>

<template>
    <AuthSplitLayout
        title="Forgot password"
        description="Enter your email to receive a password reset link"
        illustration="forgot"
    >
        <Head title="Forgot password" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-success">
            {{ status }}
        </div>

        <AuthFormWrapper
            :action="route('password.email')"
            method="post"
            submit-label="Send reset link"
            class="space-y-6"
            v-slot="{ errors }"
        >
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="email" class="text-sm font-medium">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        autofocus
                        :tabindex="1"
                        placeholder="email@example.com"
                        class="h-10"
                        :class="{ 'aria-invalid:border-destructive': errors.email }"
                    />
                    <InputError :message="errors.email" />
                </div>
            </div>

            <div class="space-x-1 text-center text-sm text-muted-foreground mt-4">
                <span>Or, return to</span>
                <TextLink :href="loginRoute()">log in</TextLink>
            </div>
        </AuthFormWrapper>
    </AuthSplitLayout>
</template>