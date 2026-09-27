<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CheckIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

interface FormData {
    name: string;
    email: string;
    role: string;
    message: string;
    honeypot: string;
}

const props = defineProps<{
    onSuccess?: () => void;
}>();

const form = useForm<FormData>({
    name: '',
    email: '',
    role: '',
    message: '',
    honeypot: '',
});

const showToast = ref(false);
const toastMessage = ref('');

const roles = [
    { value: 'principal', label: 'Principal / Head of School' },
    { value: 'admin', label: 'School Administrator' },
    { value: 'teacher', label: 'Teacher' },
    { value: 'parent', label: 'Parent / Guardian' },
    { value: 'other', label: 'Other' },
];

const submit = () => {
    if (form.honeypot) return;

    form.post('/contact', {
        onSuccess: () => {
            toastMessage.value = 'Thank you for your message! We\'ll get back to you within 24 hours.';
            showToast.value = true;
            form.reset();
            props.onSuccess?.();
            setTimeout(() => { showToast.value = false; }, 5000);
        },
        onError: () => {
            toastMessage.value = 'Something went wrong. Please try again or email us directly.';
            showToast.value = true;
            setTimeout(() => { showToast.value = false; }, 5000);
        },
    });
};
</script>

<template>
    <section class="py-16 md:py-24" aria-labelledby="contact-form-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto">
                <header class="text-center mb-12">
                    <h2 id="contact-form-heading" class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                        Get in touch
                    </h2>
                    <p class="text-base text-muted-foreground leading-relaxed max-w-[65ch] mx-auto">
                        Have questions? We'd love to hear from you. Fill out the form and we'll respond within 24 hours.
                    </p>
                </header>

                <form @submit.prevent="submit" class="bg-card border border-border rounded-[var(--radius-xl)] p-6 md:p-8 space-y-6" noValidate>
                    <!-- Honeypot -->
                    <input type="text" name="honeypot" v-model="form.honeypot" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-medium text-foreground">Full Name</label>
                        <input type="text" id="name" v-model="form.name" required
                               class="w-full h-10 rounded-[var(--radius-md)] border border-border bg-background px-4 text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 transition-shadow"
                               :class="{ 'border-destructive': form.errors.name }"
                               aria-describedby="name-error" />
                        <p v-if="form.errors.name" id="name-error" class="text-sm text-destructive flex items-center gap-1" role="alert">
                            <ExclamationCircleIcon class="w-4 h-4" />
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-foreground">Email Address</label>
                        <input type="email" id="email" v-model="form.email" required
                               class="w-full h-10 rounded-[var(--radius-md)] border border-border bg-background px-4 text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 transition-shadow"
                               :class="{ 'border-destructive': form.errors.email }"
                               aria-describedby="email-error" />
                        <p v-if="form.errors.email" id="email-error" class="text-sm text-destructive flex items-center gap-1" role="alert">
                            <ExclamationCircleIcon class="w-4 h-4" />
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="role" class="block text-sm font-medium text-foreground">Your Role</label>
                        <select id="role" v-model="form.role" required
                                class="w-full h-10 rounded-[var(--radius-md)] border border-border bg-background px-4 text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 transition-shadow appearance-none"
                                :class="{ 'border-destructive': form.errors.role }"
                                aria-describedby="role-error">
                            <option value="">Select your role</option>
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                        <p v-if="form.errors.role" id="role-error" class="text-sm text-destructive flex items-center gap-1" role="alert">
                            <ExclamationCircleIcon class="w-4 h-4" />
                            {{ form.errors.role }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="message" class="block text-sm font-medium text-foreground">Message</label>
                        <textarea id="message" v-model="form.message" rows="5" required
                                  class="w-full rounded-[var(--radius-md)] border border-border bg-background px-4 py-3 text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 transition-shadow resize-y min-h-[120px]"
                                  :class="{ 'border-destructive': form.errors.message }"
                                  aria-describedby="message-error"></textarea>
                        <p v-if="form.errors.message" id="message-error" class="text-sm text-destructive flex items-center gap-1" role="alert">
                            <ExclamationCircleIcon class="w-4 h-4" />
                            {{ form.errors.message }}
                        </p>
                    </div>

                    <button type="submit" :disabled="form.processing" class="btn-primary w-full justify-center py-3 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="form.processing" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            Sending...
                        </span>
                        <span v-else class="flex items-center gap-2">
                            Send Message
                            <CheckIcon class="w-4 h-4" />
                        </span>
                    </button>

                    <p class="text-xs text-muted-foreground text-center">
                        By submitting, you agree to our <a href="/privacy" class="underline hover:text-primary">Privacy Policy</a> and <a href="/terms" class="underline hover:text-primary">Terms of Service</a>.
                    </p>
                </form>

                <!-- Toast -->
                <div v-if="showToast" class="fixed bottom-6 right-6 z-50 animate-slide-up" role="alert" aria-live="polite">
                    <div class="bg-card border border-border rounded-[var(--radius-lg)] shadow-lg p-4 max-w-sm flex items-start gap-3">
                        <CheckIcon class="flex-shrink-0 w-5 h-5 text-success mt-0.5" />
                        <p class="text-sm text-foreground">{{ toastMessage }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style>
@keyframes slideUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-slide-up { animation: slideUp 0.3s ease-out; }
</style>