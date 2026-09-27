<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthSplitLayout from '@/components/auth/AuthSplitLayout.vue';
import AuthFormWrapper from '@/components/auth/AuthFormWrapper.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';

interface AuthConfigContent {
    title: string;
    description: string;
    toggleText: string;
}

const authConfigContent = computed<AuthConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Recovery Code',
            description: 'Please confirm access to your account by entering one of your emergency recovery codes.',
            toggleText: 'login using an authentication code',
        };
    }

    return {
        title: 'Authentication Code',
        description: 'Enter the authentication code provided by your authenticator application.',
        toggleText: 'login using a recovery code',
    };
});

const showRecoveryInput = ref<boolean>(false);
const code = ref<string>('');

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};
</script>

<template>
    <AuthSplitLayout
        :title="authConfigContent.title"
        :description="authConfigContent.description"
        illustration="2fa"
    >
        <Head title="Two-Factor Authentication" />

        <div class="space-y-6">
            <!-- Authenticator App Code -->
            <template v-if="!showRecoveryInput">
                <AuthFormWrapper
                    :action="route('two-factor.login')"
                    method="post"
                    reset-on-error
                    submit-label="Continue"
                    class="space-y-4"
                    v-slot="{ errors, clearErrors }"
                >
                    <input type="hidden" name="code" :value="code" />
                    <div class="flex flex-col items-center justify-center space-y-3 text-center">
                        <div class="flex w-full items-center justify-center">
                            <InputOTP
                                id="otp"
                                v-model="code"
                                :maxlength="6"
                                :disabled="processing"
                                autofocus
                                :tabindex="1"
                                autocomplete="one-time-code"
                            >
                                <InputOTPGroup>
                                    <InputOTPSlot
                                        v-for="index in 6"
                                        :key="index"
                                        :index="index - 1"
                                    />
                                </InputOTPGroup>
                            </InputOTP>
                        </div>
                        <InputError :message="errors.code" />
                    </div>

                    <div class="text-center text-sm text-muted-foreground">
                        <span>or you can </span>
                        <button
                            type="button"
                            class="text-foreground underline text-muted-foreground underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current"
                            @click="() => toggleRecoveryMode(clearErrors)"
                        >
                            {{ authConfigContent.toggleText }}
                        </button>
                    </div>
                </AuthFormWrapper>
            </template>

            <!-- Recovery Code -->
            <template v-else>
                <AuthFormWrapper
                    :action="route('two-factor.login')"
                    method="post"
                    reset-on-error
                    submit-label="Continue"
                    class="space-y-4"
                    v-slot="{ errors, clearErrors }"
                >
                    <Input
                        name="recovery_code"
                        type="text"
                        placeholder="Enter recovery code"
                        :autofocus="showRecoveryInput"
                        required
                        :tabindex="1"
                        autocomplete="one-time-code"
                        class="h-10 text-center text-lg tracking-widest"
                        :class="{ 'aria-invalid:border-destructive': errors.recovery_code }"
                    />
                    <InputError :message="errors.recovery_code" />

                    <div class="text-center text-sm text-muted-foreground">
                        <span>or you can </span>
                        <button
                            type="button"
                            class="text-foreground underline text-muted-foreground underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current"
                            @click="() => toggleRecoveryMode(clearErrors)"
                        >
                            {{ authConfigContent.toggleText }}
                        </button>
                    </div>
                </AuthFormWrapper>
            </template>
        </div>
    </AuthSplitLayout>
</template>