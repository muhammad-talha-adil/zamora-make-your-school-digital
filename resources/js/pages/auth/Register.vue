<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthSplitLayout from '@/components/auth/AuthSplitLayout.vue';
import AuthFormWrapper from '@/components/auth/AuthFormWrapper.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';

const loginRoute = () => route('login');
</script>

<template>
    <AuthSplitLayout
        title="Create an account"
        description="Enter your details below to create your account"
        illustration="register"
    >
        <Head title="Register" />

        <AuthFormWrapper
            :action="route('register')"
            method="post"
            :reset-on-success="['password', 'password_confirmation']"
            submit-label="Create account"
            class="space-y-6"
            v-slot="{ errors }"
        >
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="name" class="text-sm font-medium">Full name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                        class="h-10"
                        :class="{ 'aria-invalid:border-destructive': errors.name }"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="email" class="text-sm font-medium">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                        class="h-10"
                        :class="{ 'aria-invalid:border-destructive': errors.email }"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="space-y-2">
                    <Label for="password" class="text-sm font-medium">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                        class="h-10"
                        :class="{ 'aria-invalid:border-destructive': errors.password }"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="space-y-2">
                    <Label for="password_confirmation" class="text-sm font-medium">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                        class="h-10"
                        :class="{ 'aria-invalid:border-destructive': errors.password_confirmation }"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="flex items-start space-x-2">
                    <Checkbox
                        id="terms"
                        name="terms"
                        required
                        :tabindex="5"
                        class="mt-0.5 transition-all duration-200"
                    />
                    <Label for="terms" class="text-sm cursor-pointer">
                        I agree to the
                        <TextLink href="/terms" class="text-primary hover:text-primary/80 underline underline-offset-2 ml-1">Terms of Service</TextLink>
                        and
                        <TextLink href="/privacy" class="text-primary hover:text-primary/80 underline underline-offset-2 ml-1">Privacy Policy</TextLink>
                    </Label>
                </div>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="loginRoute()" :tabindex="7" class="ml-1">Log in</TextLink>
            </div>
        </AuthFormWrapper>
    </AuthSplitLayout>
</template>