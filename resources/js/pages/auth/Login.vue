<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import Icon from '@/components/Icon.vue';
import { Alert, AlertDescription, Button, Checkbox } from '@/components/ui';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Form, Head } from '@inertiajs/vue3';
import { alert } from '@/utils';
import { ref } from 'vue';
import { route } from 'ziggy-js';

interface School {
    name: string;
    logo_path?: string;
    tagline?: string;
}

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
    school?: School;
}>();

const showPassword = ref(false);

// Route helpers
const register = () => route('register');
const request = () => route('password.request');
</script>

<template>
    <AuthBase
        title="Welcome Back"
        description="Sign in to access your dashboard"
        :school="school"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            :form="{
                email: '',
                password: '',
                remember: false
            }"
            method="post"
            :reset-on-success="['password']"
            :on-success="() => alert.success('Login successful! Welcome back.')"
            v-slot="{ errors, processing }"
            class="space-y-6"
        >
            <!-- Error Banner -->
            <Alert v-if="errors.email || errors.password" variant="destructive" class="animate-in slide-in-from-top-2 duration-300">
                <AlertDescription>
                    Invalid credentials. Please check your email and password.
                </AlertDescription>
            </Alert>

            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="email" class="text-sm font-medium">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="Enter your email"
                        class="border-border/40 bg-background/60 dark:bg-background/70 focus:border-primary focus:ring-2 focus:ring-primary/25 focus:bg-background focus:shadow-sm focus:shadow-primary/10 transition-all duration-200"
                        :class="{ 'border-destructive focus:border-destructive focus:ring-destructive/25': errors.email }"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-sm font-medium">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm text-primary hover:text-primary/80 transition-colors"
                            :tabindex="5"
                        >
                            Forgot password?
                        </TextLink>
                    </div>
                    <div class="relative">
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            class="border-border/40 bg-background/60 dark:bg-background/70 focus:border-primary focus:ring-2 focus:ring-primary/25 focus:bg-background focus:shadow-sm focus:shadow-primary/10 transition-all duration-200 pr-10"
                            :class="{ 'border-destructive focus:border-destructive focus:ring-destructive/25': errors.password }"
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                            @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            :tabindex="-1"
                        >
                            <Icon :icon="showPassword ? 'eye-off' : 'eye'" class="h-4 w-4" />
                        </Button>
                    </div>
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center space-x-2">
                    <Checkbox
                        id="remember"
                        name="remember"
                        :tabindex="3"
                        class="transition-all duration-200"
                    />
                    <Label for="remember" class="text-sm cursor-pointer">Remember me</Label>
                </div>

                <Button
                    type="submit"
                    class="w-full h-11 text-base font-medium bg-linear-to-b from-primary to-primary/90 hover:from-primary/90 hover:to-primary transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 focus:ring-2 focus:ring-primary/25 disabled:opacity-50 disabled:cursor-not-allowed"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" class="mr-2" />
                    {{ processing ? 'Signing in...' : 'Sign in' }}
                </Button>
            </div>

            <div
                class="text-center text-sm text-muted-foreground"
                v-if="canRegister"
            >
                Don't have an account?
                <TextLink :href="register()" :tabindex="6" class="ml-1">Sign up</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>

