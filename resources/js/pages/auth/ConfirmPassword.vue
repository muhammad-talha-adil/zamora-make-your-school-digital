<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthSplitLayout from '@/components/auth/AuthSplitLayout.vue';
import AuthFormWrapper from '@/components/auth/AuthFormWrapper.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
</script>

<template>
    <AuthSplitLayout
        title="Confirm your password"
        description="This is a secure area of the application. Please confirm your password before continuing."
        illustration="confirm"
    >
        <Head title="Confirm password" />

        <AuthFormWrapper
            :action="route('password.confirm')"
            method="post"
            reset-on-success
            submit-label="Confirm password"
            class="space-y-6"
            v-slot="{ errors }"
        >
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="password" class="text-sm font-medium">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        class="h-10"
                        required
                        autocomplete="current-password"
                        autofocus
                        :tabindex="1"
                        :class="{ 'aria-invalid:border-destructive': errors.password }"
                    />
                    <InputError :message="errors.password" />
                </div>
            </div>
        </AuthFormWrapper>
    </AuthSplitLayout>
</template>