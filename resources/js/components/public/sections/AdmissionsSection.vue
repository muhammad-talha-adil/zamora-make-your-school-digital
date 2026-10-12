<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '@/components/Icon.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

interface Step {
    icon: string;
    title: string;
    description: string;
}

defineProps<{ steps: Step[] }>();

const { target, revealed } = useScrollReveal();

const form = useForm({
    student_name: '',
    guardian_name: '',
    phone: '',
    email: '',
    notes: '',
});

const submit = () => {
    form.post(route('admissions.enquire'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <section id="admissions" ref="target" class="bg-muted/30 py-20 md:py-28">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
            <div :class="revealed ? 'reveal-visible' : 'reveal'">
                <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                    Begin your child's journey with us
                </h2>
                <p class="mt-4 max-w-md text-base leading-relaxed text-muted-foreground">
                    Submit an enquiry and our admissions team will reach out within two working days to arrange a campus visit.
                </p>

                <ol class="mt-10 space-y-6">
                    <li v-for="step in steps" :key="step.title" class="flex gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-border bg-card">
                            <Icon :icon="step.icon" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="font-semibold text-foreground">{{ step.title }}</p>
                            <p class="mt-1 text-sm text-muted-foreground">{{ step.description }}</p>
                        </div>
                    </li>
                </ol>
            </div>

            <div class="rounded-2xl border border-border bg-card p-6 md:p-8" :class="revealed ? 'reveal-visible' : 'reveal'">
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label for="student_name" class="mb-1.5 block text-sm font-medium text-foreground">Child's name *</label>
                        <input
                            id="student_name"
                            v-model="form.student_name"
                            type="text"
                            class="w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Full name"
                        />
                        <p v-if="form.errors.student_name" class="mt-1 text-xs text-destructive">{{ form.errors.student_name }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="guardian_name" class="mb-1.5 block text-sm font-medium text-foreground">Parent/guardian name</label>
                            <input
                                id="guardian_name"
                                v-model="form.guardian_name"
                                type="text"
                                class="w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Your name"
                            />
                        </div>
                        <div>
                            <label for="phone" class="mb-1.5 block text-sm font-medium text-foreground">Phone number *</label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                class="w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="03XX XXXXXXX"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-destructive">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <div>
                        <label for="enquiry_email" class="mb-1.5 block text-sm font-medium text-foreground">Email</label>
                        <input
                            id="enquiry_email"
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="you@example.com"
                        />
                    </div>

                    <div>
                        <label for="notes" class="mb-1.5 block text-sm font-medium text-foreground">Anything we should know?</label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Grade applying for, preferred campus, questions..."
                        />
                    </div>

                    <button type="submit" class="btn-primary h-11 w-full" :disabled="form.processing">
                        {{ form.processing ? 'Sending...' : 'Submit Enquiry' }}
                    </button>

                    <p v-if="form.recentlySuccessful" class="text-center text-sm font-medium text-primary">
                        Thank you. We will be in touch soon.
                    </p>
                </form>
            </div>
        </div>
    </section>
</template>
