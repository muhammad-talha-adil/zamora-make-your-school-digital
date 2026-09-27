<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthSplitLayout from '@/components/auth/AuthSplitLayout.vue';
import AuthFormWrapper from '@/components/auth/AuthFormWrapper.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);

const loginRoute = () => route('login');
</script>

<template>
    <AuthSplitLayout
        title="Reset password"
        description="Please enter your new password below"
        illustration="reset"
    >
        <Head title="Reset password" />

        <AuthFormWrapper
            :action="route('password.update')"
            method="post"
            :transform="(data) => ({ ...data, token: props.token, email: inputEmail })"
            :reset-on-success="['password', 'password_confirmation']"
            submit-label="Reset password"
            class="space-y-6"
            v-slot="{ errors }"
        >
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="email" class="text-sm font-medium">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        v-model="inputEmail"
                        class="h-10"
                        readonly
                        :class="{ 'aria-invalid:border-destructive': errors.email }"
                    />
                    <InputError :message="errors.email" class="mt-2" />
                </div>

                <div class="space-y-2">
                    <Label for="password" class="text-sm font-medium">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        class="h-10"
                        autofocus
                        :tabindex="1"
                        placeholder="Password"
                        :class="{ 'aria-invalid:border-destructive': errors.password }"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="space-y-2">
                    <Label for="password_confirmation" class="text-sm font-medium">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="h-10"
                        :tabindex="2"
                        placeholder="Confirm password"
                        :class="{ 'aria-invalid:border-destructive': errors.password_confirmation }"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>
            </div>

            <div class="text-center text-sm text-muted-foreground mt-4">
                <TextLink :href="loginRoute()">Back to log in</TextLink>
            </div>
        </AuthFormWrapper>
    </AuthSplitLayout>
</template>